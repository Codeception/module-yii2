<?php

declare(strict_types=1);

namespace app\pageCacheHeaderAlreadySent\controllers;

use yii\filters\PageCache;

class UserController extends \yii\web\Controller
{
    public function behaviors()
    {
        return [
            'cache' => [
                'class' => PageCache::class
            ]
        ];
    }

    public function actionIndex()
    {
        return 'test';
    }
}
