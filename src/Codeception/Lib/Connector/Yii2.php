<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector;

use Codeception\Exception\ConfigurationException;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Connector\Yii2\Logger;
use Codeception\Lib\Connector\Yii2\TestMailer;
use Codeception\Util\Debug;
use InvalidArgumentException;
use Symfony\Component\BrowserKit\AbstractBrowser as Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Request as BrowserkitRequest;
use yii\web\Request as YiiRequest;
use Symfony\Component\BrowserKit\Response;
use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\base\ExitException;
use yii\base\Security;
use yii\base\UserException;
use yii\mail\BaseMessage;
use yii\mail\MessageInterface;
use yii\web\Application;
use yii\web\ErrorHandler;
use yii\web\IdentityInterface;
use yii\web\Request;
use yii\web\Response as YiiResponse;
use yii\web\User;


/**
 * @extends Client<BrowserkitRequest, Response>
 */
class Yii2 extends Client
{
    use Shared\PhpSuperGlobalsConverter;

    const CLEAN_METHODS = [
        self::CLEAN_RECREATE,
        self::CLEAN_CLEAR,
        self::CLEAN_FORCE_RECREATE,
        self::CLEAN_MANUAL
    ];
    /**
     * Clean the response object by recreating it.
     * This might lose behaviors / event handlers / other changes that are done in the application bootstrap phase.
     */
    const CLEAN_RECREATE = 'recreate';
    /**
     * Same as recreate but will not warn when behaviors / event handlers are lost.
     */
    const CLEAN_FORCE_RECREATE = 'force_recreate';
    /**
     * Clean the response object by resetting specific properties via its' `clear()` method.
     * This will keep behaviors / event handlers, but could inadvertently leave some changes intact.
     * @see \yii\web\Response::clear()
     */
    const CLEAN_CLEAR = 'clear';

    /**
     * Do not clean the response, instead the test writer will be responsible for manually resetting the response in
     * between requests during one test
     */
    const CLEAN_MANUAL = 'manual';


    /**
     * @var string application config file
     */
    public $configFile;

    /**
     * @var string method for cleaning the response object before each request
     */
    public $responseCleanMethod;

    /**
     * @var string method for cleaning the request object before each request
     */
    public $requestCleanMethod;

    /**
     * @var string[] List of component names that must be recreated before each request
     */
    public $recreateComponents = [];

    /**
     * This option is there primarily for backwards compatibility.
     * It means you cannot make any modification to application state inside your app, since they will get discarded.
     * @var bool whether to recreate the whole application before each request
     */
    public $recreateApplication = false;

    /**
     * @var bool whether to close the session in between requests inside a single test, if recreateApplication is set to true
     */
    public bool $closeSessionOnRecreateApplication = true;

    /**
     * @var class-string The FQN of the application class to use. In a default Yii setup, should be either `yii\web\Application`
     *             or `yii\console\Application`
     */
    public string|null $applicationClass = null;


    /**
     * @var list<BaseMessage>
     */
    private array $emails = [];

    /**
     * @internal
     */
    protected function getApplication(): \yii\base\Application
    {
        if (!isset(Yii::$app)) {
            $this->startApp();
        }
        return Yii::$app ?? throw new \RuntimeException('Failed to create Yii2 application');
    }

    private function getWebRequest(): YiiRequest
    {
        $request = $this->getApplication()->request;
        if (!$request instanceof YiiRequest) {
            throw new \RuntimeException('Request component is not of type ' . YiiRequest::class);
        }
        return $request;
    }

    public function resetApplication(bool $closeSession = true): void
    {
        codecept_debug('Destroying application');
        if ($closeSession) {
            $this->closeSession();
        }
        Yii::$app = null;
        \yii\web\UploadedFile::reset();
        Event::offAll();
        Yii::setLogger(null);
        // This resolves an issue with database connections not closing properly.
        gc_collect_cycles();
    }

    /**
     * Finds and logs in a user
     * @internal
     * @throws ConfigurationException
     * @throws \RuntimeException
     */
    public function findAndLoginUser(int|string|IdentityInterface $user): void
    {
        $app = $this->getApplication();
        $userComponent = $app->get('user');
        if (!$userComponent instanceof User) {
            throw new ConfigurationException('The user component is not configured');
        }

        if ($user instanceof IdentityInterface) {
            $identity = $user;
        } else {
            // class name implementing IdentityInterface
            $identityClass = $userComponent->identityClass;
            $identity = $identityClass::findIdentity($user);
            if ($identity === null) {
                throw new \RuntimeException('User not found');
            }
        }
        $userComponent->login($identity);
    }

    /**
     * @internal
     * @param string $name The name of the cookie
     * @param string $value The value of the cookie
     * @return string The value to send to the browser
     */
    public function hashCookieData(string $name, string $value): string
    {
        $request = $this->getWebRequest();
        if (!$request->enableCookieValidation) {
            return $value;
        }
        return $this->getApplication()->security->hashData(serialize([$name, $value]), $request->cookieValidationKey);
    }

    /**
     * @internal
     * @return non-empty-list<string> List of regex patterns for recognized domain names
     */
    public function getInternalDomains(): array
    {
        $urlManager = $this->getApplication()->urlManager;

        $domains = [$this->getDomainRegex($urlManager->hostInfo)];
        if ($urlManager->enablePrettyUrl) {
            foreach ($urlManager->rules as $rule) {
                /** @var \yii\web\UrlRule $rule */
                if ($rule->host !== null) {
                    $domains[] = $this->getDomainRegex($rule->host);
                }
            }
        }
        return array_values(array_unique($domains));
    }

    /**
     * @internal
     * @return list<BaseMessage> List of sent emails
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * Deletes all stored emails.
     * @internal
     */
    public function clearEmails(): void
    {
        $this->emails = [];
    }

    /**
     * @internal
     */
    public function getComponent(string $name): object|null
    {
        $app = $this->getApplication();
        $result = $app->get($name, false);
        if (!isset($result)) {
            throw new ConfigurationException("Component $name is not available in current application");
        }
        return $result;
    }

    /**
     * Getting domain regex from rule host template
     */
    private function getDomainRegex(string $template): string
    {
        if (preg_match('#https?://(.*)#', $template, $matches)) {
            $template = $matches[1];
        }
        $parameters = [];
        if (str_contains($template, '<')) {
            $template = preg_replace_callback(
                '/<(?:\w+):?([^>]+)?>/u',
                function ($matches) use (&$parameters): string {
                    $key = '__' . count($parameters) . '__';
                    $parameters[$key] = $matches[1] ?? '\w+';
                    return $key;
                },
                $template
            );
        }
        if ($template === null) {
            throw new \RuntimeException("Failed to parse domain regex");
        }
        $template = preg_quote($template);
        $template = strtr($template, $parameters);
        return '/^' . $template . '$/u';
    }

    /**
     * Gets the name of the CSRF param.
     * @internal
     */
    public function getCsrfParamName(): string
    {
        return $this->getWebRequest()->csrfParam;
    }

    public function startApp(?\yii\log\Logger $logger = null): void
    {
        codecept_debug('Starting application');
        $config = require($this->configFile);
        if (!isset($config['class'])) {
            $config['class'] = $this->applicationClass ?? \yii\web\Application::class;
        }

        if (isset($config['container'])) {
            Yii::configure(Yii::$container, $config['container']);
            unset($config['container']);
        }

        $config = $this->mockMailer($config);
        $app = Yii::createObject($config);
        if (!$app instanceof \yii\base\Application) {
            throw new ModuleConfigException($this, "Failed to initialize Yii2 app");
        }
        \Yii::$app = $app;

        if ($logger instanceof \yii\log\Logger) {
            Yii::setLogger($logger);
        } else {
            Yii::setLogger(new Logger());
        }
    }

    /**
     * @param BrowserkitRequest $request
     */
    public function doRequest(object $request): Response
    {
        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $this->remapFiles($request->getFiles());
        $_REQUEST = $this->remapRequestParameters($request->getParameters());
        $_POST = $_GET = [];

        if (strtoupper($request->getMethod()) === 'GET') {
            $_GET = $_REQUEST;
        } else {
            $_POST = $_REQUEST;
        }

        $uri = $request->getUri();

        $pathString = parse_url($uri, PHP_URL_PATH);
        $queryString = parse_url($uri, PHP_URL_QUERY);
        $_SERVER['REQUEST_URI'] = $queryString === null ? $pathString : $pathString . '?' . $queryString;
        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['QUERY_STRING'] = (string)$queryString;

        parse_str($queryString ?: '', $params);
        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
        }

        ob_start();

        $this->beforeRequest();

        $app = $this->getApplication();
        if (!$app instanceof Application) {
            throw new ConfigurationException("Application is not a web application");
        }

        // disabling logging. Logs are slowing test execution down
        foreach ($app->log->targets as $target) {
            $target->enabled = false;
        }

        $yiiRequest = $app->getRequest();
        if ($request->getContent() !== null) {
            $yiiRequest->setRawBody($request->getContent());
            $yiiRequest->setBodyParams(null);
        } else {
            $yiiRequest->setRawBody(null);
            $yiiRequest->setBodyParams($_POST);
        }
        $yiiRequest->setQueryParams($_GET);

        try {
            /*
             * This is basically equivalent to $app->run() without sending the response.
             * Sending the response is problematic because it tries to send headers.
             */
            $app->trigger($app::EVENT_BEFORE_REQUEST);
            $yiiResponse = $app->handleRequest($yiiRequest);
            $app->trigger($app::EVENT_AFTER_REQUEST);
            $yiiResponse->send();
        } catch (\Exception $e) {
            if ($e instanceof UserException) {
                // Don't discard output and pass exception handling to Yii to be able
                // to expect error response codes in tests.
                $app->errorHandler->discardExistingOutput = false;
                $app->errorHandler->handleException($e);
            } elseif (!$e instanceof ExitException) {
                // for exceptions not related to Http, we pass them to Codeception
                throw $e;
            }
            $response = $app->response;
        }

        $this->encodeCookies($yiiResponse, $yiiRequest, $app->security);

        if ($yiiResponse->isRedirection) {
            Debug::debug("[Redirect with headers]" . print_r($yiiResponse->getHeaders()->toArray(), true));
        }

        $content = ob_get_clean();
        if (empty($content) && !empty($yiiResponse->content) && !isset($yiiResponse->stream)) {
            throw new \RuntimeException('No content was sent from Yii application');
        } elseif ($content === false) {
            throw new \RuntimeException('Failed to get output buffer');
        }

        return new Response($content, $yiiResponse->statusCode, $yiiResponse->getHeaders()->toArray());
    }

    /**
     * Encodes the cookies and adds them to the headers.
     * @throws \yii\base\InvalidConfigException
     */
    protected function encodeCookies(
        YiiResponse $response,
        YiiRequest $request,
        Security $security
    ): void {
        if ($request->enableCookieValidation) {
            $validationKey = $request->cookieValidationKey;
        }

        foreach ($response->getCookies() as $cookie) {
            /** @var \yii\web\Cookie $cookie */
            $value = $cookie->value;
            // Expire = 1 means we're removing the cookie
            if ($cookie->expire !== 1 && isset($validationKey)) {
                $data = version_compare(Yii::getVersion(), '2.0.2', '>')
                    ? [$cookie->name, $cookie->value]
                    : $cookie->value;
                $value = $security->hashData(serialize($data), $validationKey);
            }
            $expires = is_int($cookie->expire) ? (string)$cookie->expire : null;
            $c = new Cookie(
                $cookie->name,
                $value,
                $expires,
                $cookie->path,
                $cookie->domain,
                $cookie->secure,
                $cookie->httpOnly
            );
            $this->getCookieJar()->set($c);
        }
    }

    /**
     * Replace mailer with in memory mailer
     * @param array<string, mixed> $config Original configuration
     * @return array<string, mixed> New configuration
     */
    protected function mockMailer(array $config): array
    {
        // options that make sense for mailer mock
        $allowedOptions = [
            'htmlLayout',
            'textLayout',
            'messageConfig',
            'messageClass',
            'useFileTransport',
            'fileTransportPath',
            'fileTransportCallback',
            'view',
            'viewPath',
        ];

        $mailerConfig = [
            'class' => TestMailer::class,
            'callback' => function (BaseMessage $message): void {
                $this->emails[] = $message;
            }
        ];

        if (isset($config['components'])) {
            if (!is_array($config['components'])) {
                throw new ModuleConfigException($this,
                    "Yii2 config does not contain components key is not of type array");
            }
        } else {
            $config['components'] = [];
        }
        if (isset($config['components']['mailer']) && is_array($config['components']['mailer'])) {
            foreach ($config['components']['mailer'] as $name => $value) {
                if (in_array($name, $allowedOptions, true)) {
                    $mailerConfig[$name] = $value;
                }
            }
        }
        $config['components']['mailer'] = $mailerConfig;

        return $config;
    }

    public function restart(): void
    {
        parent::restart();
        $this->resetApplication();
    }

    /**
     * Return an assoc array with the client context: cookieJar, history.
     *
     * @internal
     * @return array{ cookieJar: CookieJar, history: History }
     */
    public function getContext(): array
    {
        return [
            'cookieJar' => $this->cookieJar,
            'history'   => $this->history,
        ];
    }

    /**
     * Set the context, see getContext().
     *
     * @param array{ cookieJar: CookieJar, history: History } $context
     */
    public function setContext(array $context): void
    {
        $this->cookieJar = $context['cookieJar'];
        $this->history = $context['history'];
    }

    /**
     * This functions closes the session of the application, if the application exists and has a session.
     * @internal
     */
    public function closeSession(): void
    {
        $app = $this->getApplication();
        if ($app instanceof \yii\web\Application && $app->has('session', true)) {
            $app->session->close();
        }
    }

    /**
     * Resets the applications' response object.
     * The method used depends on the module configuration.
     */
    protected function resetResponse(Application $app): void
    {
        $method = $this->responseCleanMethod;
        // First check the current response object.
        if (
            ($app->response->hasEventHandlers(YiiResponse::EVENT_BEFORE_SEND)
                || $app->response->hasEventHandlers(YiiResponse::EVENT_AFTER_SEND)
                || $app->response->hasEventHandlers(YiiResponse::EVENT_AFTER_PREPARE)
                || count($app->response->getBehaviors()) > 0)
            && $method === self::CLEAN_RECREATE
        ) {
            Debug::debug(<<<TEXT
[WARNING] You are attaching event handlers or behaviors to the response object. But the Yii2 module is configured to recreate
the response object, this means any behaviors or events that are not attached in the component config will be lost.
We will fall back to clearing the response. If you are certain you want to recreate it, please configure
responseCleanMethod = 'force_recreate' in the module.
TEXT
            );
            $method = self::CLEAN_CLEAR;
        }

        match ($method) {
            self::CLEAN_FORCE_RECREATE, self::CLEAN_RECREATE => $app->set('response', $app->getComponents()['response']),
            self::CLEAN_CLEAR => $app->response->clear(),
            self::CLEAN_MANUAL => null,
            default => throw new InvalidArgumentException("Unknown method: $method"),
        };
    }

    protected function resetRequest(Application $app): void
    {
        $method = $this->requestCleanMethod;
        $request = $app->request;

        // First check the current request object.
        if (count($request->getBehaviors()) > 0 && $method === self::CLEAN_RECREATE) {
            Debug::debug(<<<TEXT
[WARNING] You are attaching event handlers or behaviors to the request object. But the Yii2 module is configured to recreate
the request object, this means any behaviors or events that are not attached in the component config will be lost.
We will fall back to clearing the request. If you are certain you want to recreate it, please configure 
requestCleanMethod = 'force_recreate' in the module.  
TEXT
            );
            $method = self::CLEAN_CLEAR;
        }

        match ($method) {
            self::CLEAN_FORCE_RECREATE, self::CLEAN_RECREATE => $app->set('request', $app->getComponents()['request']),
            self::CLEAN_CLEAR => (function () use ($request): void {
                $request->getHeaders()->removeAll();
                $request->setBaseUrl(null);
                $request->setHostInfo(null);
                $request->setPathInfo(null);
                $request->setScriptFile(null);
                $request->setScriptUrl(null);
                $request->setUrl(null);
                $request->setPort(0);
                $request->setSecurePort(0);
                $request->setAcceptableContentTypes(null);
                $request->setAcceptableLanguages(null);
            })(),
            self::CLEAN_MANUAL => null,
            default => throw new InvalidArgumentException("Unknown method: $method"),
        };
    }

    /**
     * Called before each request, preparation happens here.
     */
    protected function beforeRequest(): void
    {
        if ($this->recreateApplication) {
            $this->resetApplication($this->closeSessionOnRecreateApplication);
            return;
        }

        $application = $this->getApplication();

        if (!$application instanceof Application) {
            throw new ConfigurationException('Application must be an instance of web application when doing requests');
        }
        $this->resetResponse($application);
        $this->resetRequest($application);

        $definitions = $application->getComponents(true);
        foreach ($this->recreateComponents as $component) {
            // Only recreate if it has actually been instantiated.
            if ($application->has($component, true)) {
                $application->set($component, $definitions[$component]);
            }
        }
    }
}
