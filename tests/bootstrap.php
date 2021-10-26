<?php
defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'test');

call_user_func(function() {
    $loader = require __DIR__ . '/vendor/autoload.php';

    $container = new \yii\di\Container();
//    call_user_func(function() use ($container) {
//        require __DIR__ .'/../src/config/di.php';
//    });

//    Yii::$loader = $loader;
    Yii::$container = $container;
});

$link = __DIR__ . '/vendor/yiisoft/yii2-app-advanced/vendor';
if (!file_exists($link) && !symlink(__DIR__ . '/vendor', $link)) {
    die('failed to create symlink');
}