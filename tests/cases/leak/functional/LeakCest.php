<?php

declare(strict_types=1);

namespace tests;

use app\leak\App;
use Codeception\Lib\Connector\Yii2\Logger;

/**
 * Regression test for https://github.com/Codeception/module-yii2/issues/78.
 *
 * Each test's application is built inside `Connector\Yii2::startApp()` via
 * `Yii::createObject()`. While the application builds, it already calls
 * `Yii::getLogger()` (its log dispatcher / debug bootstrap). If the module logger
 * is not installed yet, a plain `yii\log\Logger` is created there, and its `init()`
 * runs `register_shutdown_function()`. PHP then keeps that closure until the process
 * ends, retaining the logger, its dispatcher, its log targets and the whole
 * application — so memory grows with every test and large suites OOM.
 *
 * The suite's test double application records the class of the logger present while
 * its constructor runs. It must be the module logger, which overrides `init()` to
 * skip the shutdown registration.
 */
final class LeakCest
{
    public function testModuleLoggerInstalledBeforeApplicationBuild(FunctionalTester $I): void
    {
        $I->assertInstanceOf(Logger::class, \Yii::getLogger());

        $app = \Yii::$app;
        $loggerAtCtorStart = $app instanceof App ? $app->loggerAtCtorStart : null;

        // The logger present while the application was constructed must already be the
        // module logger — otherwise a plain `yii\log\Logger` was created mid-build.
        $I->assertSame(Logger::class, $loggerAtCtorStart);
    }
}
