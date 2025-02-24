<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector\Yii2;

use Codeception\Util\Debug;
use yii\base\Exception as YiiException;
use yii\helpers\VarDumper;
use yii\log\Logger as YiiLogger;

class Logger extends YiiLogger
{
    private \SplQueue $logQueue;

    public function __construct(private int $maxLogItems = 5, array $config = [])
    {
        parent::__construct($config);
        $this->logQueue = new \SplQueue();
    }

    public function init(): void
    {
        // overridden to prevent register_shutdown_function
    }

    /**
     * @param string|array|YiiException $message
     * @param self::LEVEL_INFO|self::LEVEL_WARNING|self::LEVEL_ERROR $level
     * @param string $category
     */
    public function log($message, $level, $category = 'application'): void
    {
        if (!in_array($level, [
            self::LEVEL_INFO,
            self::LEVEL_WARNING,
            self::LEVEL_ERROR,
        ], true)) {
            return;
        }
        if (str_starts_with($category, 'yii\db\Command')) {
            return; // don't log queries
        }
        // https://github.com/Codeception/Codeception/issues/3696
        if ($message instanceof YiiException) {
            $message = $message->__toString();
        }
        $logMessage = "[$category] " . VarDumper::export($message);
        Debug::debug($logMessage);
        $this->logQueue->enqueue($logMessage);
        if ($this->logQueue->count() > $this->maxLogItems) {
            $this->logQueue->dequeue();
        }
    }

    public function getAndClearLog(): string
    {
        $logs = iterator_to_array($this->logQueue);
        $this->logQueue = new \SplQueue();
        return implode(PHP_EOL, $logs) . PHP_EOL;
    }
}
