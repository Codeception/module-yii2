<?php

declare(strict_types=1);

namespace app\leak;

use ReflectionProperty;
use yii\web\Application as BaseApplication;

/**
 * Test double that records the class of the logger installed while the application
 * is being constructed. Used to verify that the module logger is in place before the
 * application builds itself, so no plain `yii\log\Logger` (whose `init()` registers a
 * shutdown function that keeps it alive after each test) is created during boot.
 */
final class App extends BaseApplication
{
    public ?string $loggerAtCtorStart = null;

    public function __construct(array $config = [])
    {
        $this->loggerAtCtorStart = $this->loggerClass();
        parent::__construct($config);
    }

    private function loggerClass(): ?string
    {
        static $property = null;
        $property ??= new ReflectionProperty(\yii\BaseYii::class, '_logger');
        $logger = $property->getValue();
        return $logger === null ? null : $logger::class;
    }
}
