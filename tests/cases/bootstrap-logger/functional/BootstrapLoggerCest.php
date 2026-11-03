<?php

declare(strict_types=1);

namespace tests;

use Codeception\Lib\Connector\Yii2\Logger;
use Codeception\Lib\Di;
use Codeception\Lib\ModuleContainer;
use Codeception\Module\Yii2 as Yii2Module;
use Codeception\Test\Unit;

/**
 * Regression test for https://github.com/Codeception/module-yii2/issues/78.
 *
 * When an application's `bootstrap` callback replaces the global logger while the
 * module builds the application, the module must keep reporting the messages
 * emitted during the test. The connector installs the module logger before the
 * application is built (so no plain `yii\log\Logger` is created mid-build, which
 * is the retention root of issue78) and, after the build, restores it, because the
 * application may have replaced the global logger during bootstrap (a `bootstrap`
 * callback calling `Yii::setLogger()`, or a `logger` config entry). Without that
 * restore the replacement stays global, the messages of the test go to the
 * replacement, and `Module\Yii2::_failed()` — which reads its separately held
 * logger — loses the `yii-log` failure report.
 *
 * This test drives the real module through its public `_before`/`_failed`/`_after`
 * lifecycle with an app whose bootstrap installs a custom global logger: a
 * `Yii::warning()` is emitted during the test and the `yii-log` report attached
 * on failure must contain it. The global logger must be the module logger both
 * while the application builds and afterwards.
 */
final class BootstrapLoggerCest
{
    public function testFailedReportKeepsLogsEmittedAfterBootstrapLoggerReplacement(FunctionalTester $I): void
    {
        $modules = new ModuleContainer(new Di(), []);
        $module = new Yii2Module($modules, [
            'configFile' => __DIR__ . '/../config.php',
            'cleanup' => false,
            'transaction' => false,
        ]);

        $test = new Unit('testBootstrapLogger505');

        $module->_initialize();
        $module->_before($test);

        // The global logger must be the module logger both while the application
        // is being built and once it is done — i.e. the bootstrap did not leave its
        // custom global logger in place.
        $I->assertInstanceOf(Logger::class, \Yii::getLogger());

        \Yii::warning('bootstrap-logger-505', 'bootstrap-logger');
        $module->_failed($test, new \RuntimeException('intentional test failure'));

        $report = $test->getMetadata()->getReports()['yii-log'] ?? '';
        $I->assertStringContainsString('bootstrap-logger-505', $report);

        $module->_after($test);
    }
}
