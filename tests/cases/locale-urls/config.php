<?php

declare(strict_types=1);

use codemix\localeurls\UrlManager;

return [
    'id' => 'Simple',
    'basePath' => __DIR__,
    'controllerNamespace' => 'app\localeurls\controllers',
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'test',
        ],
        'urlManager' => [
            'class' => UrlManager::class,
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableLanguagePersistence' => false,
            'enableLocaleUrls' => true,
            'languages' => ['nl', 'en'],
        ],
    ],
    'on beforeRequest' => function (\yii\base\Event $event) {
        $app = $event->sender;
        if ($app->has('urlManager', true)) {
            $config = $app->getComponents()['urlManager'];
            \Codeception\Util\Debug::debug('Resetting url manager: ' . print_r($config, true));
            $app->set('urlManager', $config);
        }
    },
];
