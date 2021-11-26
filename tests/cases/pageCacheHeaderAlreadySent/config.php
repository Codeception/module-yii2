<?php

return [
    'id' => 'PageCache',
    'basePath' => __DIR__,
    'controllerNamespace' => 'app\pageCacheHeaderAlreadySent\controllers',
    'components' => [
        'cache' => [
            'class' => \yii\caching\DummyCache::class,
        ],
    ],
];
