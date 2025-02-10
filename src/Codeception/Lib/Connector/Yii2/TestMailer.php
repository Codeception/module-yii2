<?php
namespace Codeception\Lib\Connector\Yii2;

use ReflectionClass;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\UnknownMethodException;
use yii\base\UnknownPropertyException;
use yii\di\Instance;
use yii\mail\BaseMailer;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

class TestMailer extends BaseMailer
{
    /**
     * @var \Closure
     */
    public $callback;

    /**
     * @var string|array|MailerInterface Mailer config or component to send mail out in the end
     */
    public $mailer = 'mailer';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        $this->mailer = Instance::ensure($this->mailer, MailerInterface::class);
    }

    /**
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        try {
            return parent::__call($name, $params);
        } catch (UnknownMethodException $e) {
            return call_user_func_array([$this->mailer, $name], $params);
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            return $this->mailer->{$name};
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            $this->mailer->{$name} = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function compose($view = null, array $params = []): MessageInterface
    {
        $message = $this->mailer->compose($view, $params);

        if ($message instanceof BaseObject && $message->canSetProperty('mailer')) {
            /** @phpstan-ignore property.notFound */
            $message->mailer = $this;
        } else {
            $reflection = new ReflectionClass($message);
            if ($reflection->hasProperty('mailer')) {
                /** @phpstan-ignore property.notFound */
                $message->mailer = $this;
            }
        }

        return $message;
    }

    protected function sendMessage($message): bool
    {
        call_user_func($this->callback, $message);
        return true;
    }

    protected function saveMessage($message): bool
    {
        call_user_func($this->callback, $message);
        return true;
    }
}
