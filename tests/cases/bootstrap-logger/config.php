<?php

declare(strict_types=1);

require_once __DIR__ . '/app/App.php';
require_once __DIR__ . '/app/CustomLogger.php';

return [
    'class' => app\bootstrapLogger\App::class,
    'id' => 'bootstrap-logger',
    'basePath' => __DIR__,
    'bootstrap' => [
        static function (): void {
            \Yii::setLogger(new \app\bootstrapLogger\CustomLogger());
        },
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'bootstrap-logger',
            'enableCsrfValidation' => false,
        ],
    ],
];
