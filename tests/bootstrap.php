<?php
defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'test');

call_user_func(function() {
    require dirname(__DIR__) . '/vendor/autoload.php';
    $container = new \yii\di\Container();
    Yii::$container = $container;
});

$link = dirname(__DIR__) . '/vendor/yiisoft/yii2-app-advanced/vendor';
if (!file_exists($link) && !symlink(dirname(__DIR__) . '/vendor', $link)) {
    die('failed to create symlink');
}