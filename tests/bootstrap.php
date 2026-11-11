<?php

declare(strict_types=1);

defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'test');

require dirname(__DIR__) . '/vendor/autoload.php';

Yii::$container = new \yii\di\Container();

$link = dirname(__DIR__) . '/vendor/yiisoft/yii2-app-advanced/vendor';
if (is_link($link) && ! file_exists($link)) {
    unlink($link);
}
if (! file_exists($link) && ! symlink(dirname(__DIR__) . '/vendor', $link)) {
    die('failed to create symlink');
}

// The current yii2-app-advanced template ships an Acceptance suite that depends on
// codeception/module-phpbrowser, which this module does not require. The module's
// test scope is the functional and unit suites, so disable the suite before
// Codeception discovers it.
$acceptanceSuite = dirname(__DIR__) . '/vendor/yiisoft/yii2-app-advanced/frontend/tests/Acceptance.suite.yml';
if (is_file($acceptanceSuite) && ! is_file($acceptanceSuite . '.disabled')) {
    rename($acceptanceSuite, $acceptanceSuite . '.disabled');
}
