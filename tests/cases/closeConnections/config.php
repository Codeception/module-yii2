<?php

declare(strict_types=1);

return [
    'id' => 'Simple',
    'basePath' => __DIR__,

    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . \tests\helpers\SqlliteHelper::getTmpFile(),
        ],
    ],
];
