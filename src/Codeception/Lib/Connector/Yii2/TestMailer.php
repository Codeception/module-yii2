<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector\Yii2;

use Closure;
use yii\mail\BaseMailer;

final class TestMailer extends BaseMailer
{
    public $messageClass = \yii\symfonymailer\Message::class;

    public Closure $callback;

    protected function sendMessage(mixed $message): bool
    {
        ($this->callback)($message);
        return true;
    }

    protected function saveMessage(mixed $message): bool
    {
        ($this->callback)($message);
        return true;
    }
}
