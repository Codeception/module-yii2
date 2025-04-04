<?php

declare(strict_types=1);

return [
    'id' => 'Simple',
    'basePath' => __DIR__,
    'controllerNamespace' => 'app\mockmailer\controllers',
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'test',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
        ],
    ],
];
