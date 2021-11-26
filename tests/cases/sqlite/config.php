<?php
return [
    'id' => 'Simple',
    'basePath' => __DIR__,

    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . tempnam(null, '/file0')
        ],
        'db1' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . tempnam(null, '/file1')
        ],
        'db21' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . ($name = tempnam(null, '/file2'))
        ],
        'db22' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . $name
        ]
    ]
];