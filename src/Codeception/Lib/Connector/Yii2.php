<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector;

use Codeception\Exception\ConfigurationException;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Connector\Yii2\Logger;
use Codeception\Lib\Connector\Yii2\TestMailer;
use Codeception\Util\Debug;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\BrowserKit\AbstractBrowser as Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Request as BrowserkitRequest;
use Symfony\Component\BrowserKit\Response;
use Yii;
use yii\base\Event;
use yii\base\ExitException;
use yii\base\Security;
use yii\base\UserException;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;
use yii\mail\MessageInterface;
use yii\web\Application;
use yii\web\IdentityInterface;
use yii\web\Request as YiiRequest;
use yii\web\Response as YiiResponse;
use yii\web\User;

/**
 * @extends Client<BrowserkitRequest, Response>
 * @internal This class is not part of the public API
 */
final class Yii2 extends Client
{
    use Shared\PhpSuperGlobalsConverter;

    public const array MAIL_METHODS = [
        self::MAIL_CATCH,
        self::MAIL_EVENT_AFTER,
        self::MAIL_EVENT_BEFORE,
        self::MAIL_IGNORE
    ];

    public const string MAIL_CATCH = 'catch';

    public const string MAIL_EVENT_AFTER = 'after';

    public const string MAIL_EVENT_BEFORE = 'before';

    public const string MAIL_IGNORE = 'ignore';

    public const array CLEAN_METHODS = [
        self::CLEAN_RECREATE,
        self::CLEAN_CLEAR,
        self::CLEAN_FORCE_RECREATE,
        self::CLEAN_MANUAL,
    ];

    /**
     * Clean the response object by recreating it.
     * This might lose behaviors / event handlers / other changes that are done in the application bootstrap phase.
     */
    public const string CLEAN_RECREATE = 'recreate';

    /**
     * Same as recreate but will not warn when behaviors / event handlers are lost.
     */
    public const string CLEAN_FORCE_RECREATE = 'force_recreate';

    /**
     * Clean the response object by resetting specific properties via its' `clear()` method.
     * This will keep behaviors / event handlers, but could inadvertently leave some changes intact.
     *
     * @see \yii\web\Response::clear()
     */
    public const string CLEAN_CLEAR = 'clear';

    /**
     * Do not clean the response, instead the test writer will be responsible for manually resetting the response in
     * between requests during one test
     */
    public const string CLEAN_MANUAL = 'manual';

    /**
     * @var string application config file
     */
    public string $configFile;

    /**
     * @var self::MAIL_CATCH|self::MAIL_IGNORE|self::MAIL_EVENT_AFTER|self::MAIL_EVENT_BEFORE method for handling mails
     */
    public string $mailMethod;

    /**
     * @var string method for cleaning the response object before each request
     */
    public string $responseCleanMethod;

    /**
     * @var string method for cleaning the request object before each request
     */
    public string $requestCleanMethod;

    /**
     * @var string[] List of component names that must be recreated before each request
     */
    public array $recreateComponents = [];

    /**
     * This option is there primarily for backwards compatibility.
     * It means you cannot make any modification to application state inside your app, since they will get discarded.
     *
     * @var bool whether to recreate the whole application before each request
     */
    public bool $recreateApplication = false;

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
     * @var list<MessageInterface>
     */
    private array $emails = [];

    /**
     * Whether an application is currently being started or already started and not yet reset.
     * While this is true, `yii\log` shutdown registrations are stored (see
     * `registerShutdown()`) instead of being registered for real: a real shutdown closure
     * bound to a logger keeps the logger — and with it its dispatcher, log targets and the
     * application they hold — alive until process exit, even though the application is
     * discarded after every test.
     *
     * @internal
     */
    private static bool $appStarting = false;

    /**
     * Shutdown registrations captured in the `yii\log` namespace while an application is
     * being started. `yii\log\Logger::init()` registers two of them per logger (a closure
     * bound to the logger and `[$this, 'flush']` with `true`); they are run by
     * `resetApplication()` instead of surviving to process exit.
     *
     * @internal
     *
     * @var list<array{0: callable, 1: list<mixed>}>
     */
    private static array $deferredShutdowns = [];

    /**
     * @internal
     *
     * @return bool whether an application is currently being started or not yet reset
     */
    public static function appStarting(): bool
    {
        return self::$appStarting;
    }

    /**
     * Stores a shutdown registration (with its arguments) instead of registering it,
     * to be run later by `resetApplication()`.
     *
     * The arguments are kept because `yii\log\Logger::init()` registers
     * `[$this, 'flush']` with `true` — the final-flush flag that gates target export —
     * and it must survive the store/invoke round-trip.
     *
     * @internal
     */
    public static function registerShutdown(callable $callable, mixed ...$args): void
    {
        self::$deferredShutdowns[] = [$callable, $args];
    }

    /**
     * Runs the stored shutdown registrations.
     *
     * The loop continues until the queue is empty because invoking the stored outer closure
     * re-registers the nested `[$this, 'flush']` one, which must in turn be invoked; the cap
     * is a safety guard against pathological re-registration.
     *
     * @internal
     */
    private static function runDeferredShutdowns(): void
    {
        $cap = 100;
        while (self::$deferredShutdowns !== [] && $cap-- > 0) {
            [$callable, $args] = array_shift(self::$deferredShutdowns);
            \call_user_func($callable, ...$args);
        }
    }

    /**
     * @internal
     */
    protected function getApplication(): \yii\base\Application
    {
        if (! isset(Yii::$app)) {
            $this->startApp();
        }
        return Yii::$app ?? throw new RuntimeException('Failed to create Yii2 application');
    }

    private function getWebRequest(): YiiRequest
    {
        $request = $this->getApplication()->request;
        if (! $request instanceof YiiRequest) {
            throw new RuntimeException('Request component is not of type ' . YiiRequest::class);
        }
        return $request;
    }

    public function resetApplication(bool $closeSession = true): void
    {
        codecept_debug('Destroying application');

        // Run the shutdown registrations stored during the previous `startApp()` before the
        // application is torn down: invoking them here (instead of letting them run at
        // process exit) is what stops the loggers — and the dispatchers, targets and
        // application they hold — from being retained for the whole test process. This is
        // idempotent: when called before the next application is started, the queue is
        // already empty.
        self::runDeferredShutdowns();
        self::$appStarting = false;

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
     *
     * @internal
     * @throws   ConfigurationException|RuntimeException
     */
    public function findAndLoginUser(int|string|IdentityInterface $user): void
    {
        $app = $this->getApplication();
        $userComponent = $app->get('user');
        if (! $userComponent instanceof User) {
            throw new ConfigurationException('The user component is not configured');
        }

        $identity = $user instanceof IdentityInterface ? $user : ($userComponent->identityClass)::findIdentity($user);
        if ($identity === null) {
            throw new RuntimeException('User not found');
        }
        $userComponent->login($identity);
    }

    /**
     * @internal
     * @param    string $name  The name of the cookie
     * @param    string $value The value of the cookie
     * @return   string The value to send to the browser
     */
    public function hashCookieData(string $name, string $value): string
    {
        $request = $this->getWebRequest();
        if (! $request->enableCookieValidation) {
            return $value;
        }
        return $this->getApplication()->security->hashData(serialize([$name, $value]), $request->cookieValidationKey);
    }

    /**
     * @internal
     * @return   non-empty-list<string> List of regex patterns for recognized domain names
     */
    public function getInternalDomains(): array
    {
        $urlManager = $this->getApplication()->urlManager;

        $domains = [$this->getDomainRegex($urlManager->hostInfo)];
        if ($urlManager->enablePrettyUrl) {
            foreach ($urlManager->rules as $rule) {
                if (isset($rule->host)) {
                    $domains[] = $this->getDomainRegex($rule->host);
                }
            }
        }
        return array_values(array_unique($domains));
    }

    /**
     * @internal
     * @return list<MessageInterface> List of sent emails
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * Deletes all stored emails.
     *
     * @internal
     */
    public function clearEmails(): void
    {
        $this->emails = [];
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
                $template,
            );
        }
        if ($template === null) {
            throw new RuntimeException("Failed to parse domain regex");
        }
        $template = preg_quote($template);
        $template = strtr($template, $parameters);
        return '/^' . $template . '$/u';
    }

    /**
     * Gets the name of the CSRF param.
     *
     * @internal
     */
    public function getCsrfParamName(): string
    {
        return $this->getWebRequest()->csrfParam;
    }

    public function startApp(?\yii\log\Logger $logger = null): void
    {
        codecept_debug('Starting application');
        $config = include $this->configFile;
        if (! isset($config['class'])) {
            $config['class'] = $this->applicationClass ?? \yii\web\Application::class;
        }

        if (isset($config['container'])) {
            Yii::configure(Yii::$container, $config['container']);
            unset($config['container']);
        }

        match ($this->mailMethod) {
            self::MAIL_CATCH => $config = $this->mockMailer($config),
            self::MAIL_EVENT_AFTER => $config['components']['mailer']['on ' . BaseMailer::EVENT_AFTER_SEND] = function (MailEvent $event): void {
                if ($event->isSuccessful) {
                    $this->emails[] = $event->message;
                }
            },
            self::MAIL_EVENT_BEFORE => $config['components']['mailer']['on ' . BaseMailer::EVENT_BEFORE_SEND] = fn (MailEvent $event) => $this->emails[] = $event->message,
            self::MAIL_IGNORE => null// Do nothing
        };

        // Intercept shutdown registrations made in the `yii\log` namespace for the rest of
        // this process. Yii does not import `register_shutdown_function`, so an unqualified
        // call in `yii\log\Logger::init()` resolves to a namespace-level function when one
        // is defined there; while `$appStarting` is on, those calls are stored instead of
        // being registered. Without this, every plain logger built during the application
        // bootstrap (e.g. one the application itself sets via a `bootstrap` callback) keeps
        // itself alive — with its dispatcher, log targets and the application — until
        // process exit, because PHP's shutdown closures outlive the discarded application.
        if (! function_exists('\yii\log\register_shutdown_function')) {
            eval(<<<'PHP'
                namespace yii\log {
                    function register_shutdown_function(callable $callable, mixed ...$args) {
                        if (\Codeception\Lib\Connector\Yii2::appStarting()) {
                            \Codeception\Lib\Connector\Yii2::registerShutdown($callable, ...$args);
                            return null;
                        }
                        return \register_shutdown_function($callable, ...$args);
                    }
                }
            PHP);
        }
        // Keep it on through the whole test so loggers created after the build are
        // captured too; `resetApplication()` runs the stored registrations and turns it off.
        self::$appStarting = true;

        // Install the module logger before the application is built: while `createObject()`
        // runs, the application already calls `Yii::getLogger()` (its log dispatcher and
        // debug bootstrap). If no logger is installed yet, a plain `yii\log\Logger` is
        // created, and its `init()` registers a shutdown function that keeps it alive —
        // along with its dispatcher, log targets and the application — after every test.
        // The module logger overrides `init()` to avoid that, so it must be in place first.
        $moduleLogger = $logger instanceof \yii\log\Logger
            ? $logger
            : new Logger();
        Yii::setLogger($moduleLogger);

        // @phpstan-ignore argument.templateType
        $app = Yii::createObject($config);
        if (! $app instanceof \yii\base\Application) {
            throw new ModuleConfigException($this, "Failed to initialize Yii2 app");
        }
        \Yii::$app = $app;

        // The application's bootstrap may replace the global logger while it is being
        // built (a `bootstrap` callback calling `Yii::setLogger()`, or a `logger` config
        // entry). Restore the module logger afterwards so that messages emitted during the
        // test reach it and `Module\Yii2::_failed()` can report them; otherwise such a
        // bootstrap replacement would swallow the module's failure diagnostics.
        Yii::setLogger($moduleLogger);
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
        $_SERVER['QUERY_STRING'] = (string) $queryString;

        parse_str($queryString ?: '', $params);
        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
        }

        ob_start();

        $this->beforeRequest();

        $app = $this->getApplication();
        if (! $app instanceof Application) {
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
            } elseif (! $e instanceof ExitException) {
                // for exceptions not related to Http, we pass them to Codeception
                throw $e;
            }
            $yiiResponse = $app->response;
        }

        $this->encodeCookies($yiiResponse, $yiiRequest, $app->security);

        if ($yiiResponse->isRedirection) {
            Debug::debug("[Redirect with headers]" . print_r($yiiResponse->getHeaders()->toArray(), true));
        }

        $content = ob_get_clean();
        if (empty($content) && ! empty($yiiResponse->content) && ! isset($yiiResponse->stream)) {
            throw new RuntimeException('No content was sent from Yii application');
        } elseif ($content === false) {
            throw new RuntimeException('Failed to get output buffer');
        }

        return new Response($content, $yiiResponse->statusCode, $yiiResponse->getHeaders()->toArray());
    }

    /**
     * Encodes the cookies and adds them to the headers.
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function encodeCookies(
        YiiResponse $response,
        YiiRequest $request,
        Security $security,
    ): void {
        if ($request->enableCookieValidation) {
            $validationKey = $request->cookieValidationKey;
        }

        foreach ($response->getCookies() as $cookie) {
            /**
             * @var \yii\web\Cookie $cookie
             */
            $value = $cookie->value;
            // Expire = 1 means we're removing the cookie
            if ($cookie->expire !== 1 && isset($validationKey)) {
                $data = version_compare(Yii::getVersion(), '2.0.2', '>')
                    ? [$cookie->name, $cookie->value]
                    : $cookie->value;
                $value = $security->hashData(serialize($data), $validationKey);
            }
            $expires = is_int($cookie->expire) ? (string) $cookie->expire : null;
            $c = new Cookie(
                $cookie->name,
                $value,
                $expires,
                $cookie->path,
                $cookie->domain,
                $cookie->secure,
                $cookie->httpOnly,
            );
            $this->getCookieJar()->set($c);
        }
    }

    /**
     * Replace mailer with in memory mailer
     *
     * @param  array<string, mixed> $config Original configuration
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
            'callback' => function (MessageInterface $message): void {
                $this->emails[] = $message;
            },
        ];

        if (isset($config['components'])) {
            if (! is_array($config['components'])) {
                throw new ModuleConfigException(
                    $this,
                    "Yii2 config does not contain components key is not of type array",
                );
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
     * @return   array{ cookieJar: CookieJar, history: History }
     */
    public function getContext(): array
    {
        return [
            'cookieJar' => $this->cookieJar,
            'history' => $this->history,
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
     *
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
        if (($app->response->hasEventHandlers(YiiResponse::EVENT_BEFORE_SEND)
            || $app->response->hasEventHandlers(YiiResponse::EVENT_AFTER_SEND)
            || $app->response->hasEventHandlers(YiiResponse::EVENT_AFTER_PREPARE)
            || count($app->response->getBehaviors()) > 0)
            && $method === self::CLEAN_RECREATE
        ) {
            Debug::debug(
                <<<TEXT
[WARNING] You are attaching event handlers or behaviors to the response object. But the Yii2 module is configured to recreate
the response object, this means any behaviors or events that are not attached in the component config will be lost.
We will fall back to clearing the response. If you are certain you want to recreate it, please configure
responseCleanMethod = 'force_recreate' in the module.
TEXT,
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
            Debug::debug(
                <<<TEXT
[WARNING] You are attaching event handlers or behaviors to the request object. But the Yii2 module is configured to recreate
the request object, this means any behaviors or events that are not attached in the component config will be lost.
We will fall back to clearing the request. If you are certain you want to recreate it, please configure 
requestCleanMethod = 'force_recreate' in the module.  
TEXT,
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

        if (! $application instanceof Application) {
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
