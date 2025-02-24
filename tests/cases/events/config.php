<?php

return [
    'id' => 'Response Events',
    'basePath' => __DIR__,
    'bootstrap' => [
        function (\yii\web\Application $application) {
            $application->response->on(\yii\web\Response::EVENT_BEFORE_SEND, function ($event) use ($application) {
                $application->trigger('responseBeforeSendBootstrap');
            });
        },
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'secret',
        ],
        'response' => [
            'on beforeSend' => function () {
                \Yii::$app->trigger('responseBeforeSendConfig');
            },
        ],
    ],
];
