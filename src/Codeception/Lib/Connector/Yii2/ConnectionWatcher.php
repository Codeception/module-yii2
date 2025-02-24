<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector\Yii2;

use Closure;
use JsonSerializable;
use ReflectionClass;
use yii\base\Event;
use yii\db\Connection;

/**
 * Class ConnectionWatcher
 * This class will watch for new database connection and store a reference to the connection object.
 * @package Codeception\Lib\Connector\Yii2
 */
class ConnectionWatcher
{
    private Closure $handler;

    /** @var Connection[] */
    private array $connections = [];

    public function __construct()
    {
        $this->handler = function (Event $event): void {
            if ($event->sender instanceof Connection) {
                $this->connectionOpened($event->sender);
            }
        };
    }

    protected function connectionOpened(Connection $connection): void
    {
        $this->debug('Connection opened!');
        $this->connections[] = $connection;
    }

    public function start(): void
    {
        Event::on(Connection::class, Connection::EVENT_AFTER_OPEN, $this->handler);
        $this->debug('watching new connections');
    }

    public function stop(): void
    {
        Event::off(Connection::class, Connection::EVENT_AFTER_OPEN, $this->handler);
        $this->debug('no longer watching new connections');
    }

    public function closeAll(): void
    {
        $count = count($this->connections);
        $this->debug("closing all ($count) connections");
        foreach ($this->connections as $connection) {
            $connection->close();
        }
    }

    /**
     * @param string|array<mixed>|JsonSerializable $message
     * @return void
     */
    protected function debug(string|array|JsonSerializable $message): void
    {
        $title = (new ReflectionClass($this))->getShortName();
        if (is_array($message) || is_object($message)) {
            $message = stripslashes(json_encode($message, JSON_THROW_ON_ERROR));
        }
        codecept_debug("[$title] $message");
    }
}
