<?php
namespace Codeception\Lib\Connector\Yii2;

use Codeception\Util\Debug;
use yii\helpers\VarDumper;

class Logger extends \yii\log\Logger
{
    private \SplQueue $logQueue;

    public function __construct(private int $maxLogItems = 5, $config = [])
    {
        parent::__construct($config);
        $this->logQueue = new \SplQueue();
    }

    public function init(): void
    {
        // overridden to prevent register_shutdown_function
    }

    /**
     * @param string|array<mixed>|\yii\base\Exception $message
     * @param $level
     * @param $category
     * @return void
     */
    public function log($message, $level, $category = 'application'): void
    {
        if (!in_array($level, [
            \yii\log\Logger::LEVEL_INFO,
            \yii\log\Logger::LEVEL_WARNING,
            \yii\log\Logger::LEVEL_ERROR,
        ])) {
            return;
        }
        if (str_starts_with($category, 'yii\db\Command')) {
            return; // don't log queries
        }

        // https://github.com/Codeception/Codeception/issues/3696
        if ($message instanceof \yii\base\Exception) {
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
        $completeStr = '';
        foreach ($this->logQueue as $item) {
            $completeStr .= $item . PHP_EOL;
        }
        $this->logQueue = new \SplQueue();

        return $completeStr;
    }
}
