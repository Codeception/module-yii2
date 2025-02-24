<?php

use app\simple\helpers\DummyUser;

return [
    'id' => 'Simple',
    'basePath' => __DIR__,
    'controllerNamespace' => 'app\simple\controllers',
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'test',
        ],
        'user' => [
            'identityClass' => DummyUser::class,
        ],
    ],
    'on beforeRequest' => function () {
        if (isset(\Yii::$app->params['throw'])) {
            throw \Yii::$app->params['throw'];
        }
    },
];
