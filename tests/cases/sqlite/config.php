<?php

declare(strict_types=1);

return [
    'id' => 'Simple',
    'basePath' => __DIR__,

    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . tempnam('', '/file0'),
        ],
        'db1' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . tempnam('', '/file1'),
        ],
        'db21' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . ($name = tempnam('', '/file2')),
        ],
        'db22' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . $name,
        ],
    ],
];
