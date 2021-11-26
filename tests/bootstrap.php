<?php
defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'test');

Yii::$container = new \yii\di\Container();

$link = __DIR__ . '/../vendor/yiisoft/yii2-app-advanced/vendor';
if (!file_exists($link) && !symlink(__DIR__ . '/../vendor', $link)) {
    die('failed to create symlink');
}
