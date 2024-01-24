<?php


namespace Codeception\Lib\Connector\Yii2;

use yii\base\Event;
use yii\db\Connection;

/**
 * Class ConnectionWatcher
 * This class will watch for new database connection and store a reference to the connection object.
 * @package Codeception\Lib\Connector\Yii2
 */
class ConnectionWatcher
{
    private \Closure $handler;

    /** @var Connection[] */
    private array $connections = [];

    public function __construct()
    {
        $this->handler = function (Event $event) {
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

    protected function debug($message): void
    {
        $title = (new \ReflectionClass($this))->getShortName();
        if (is_array($message) or is_object($message)) {
            $message = stripslashes(json_encode($message));
        }
        codecept_debug("[$title] $message");
    }
}
