<?php

declare(strict_types=1);

namespace app\mockmailer\controllers;

use yii\web\Controller;

final class SiteController extends Controller
{
    public function actionIndex()
    {
        return __METHOD__;
    }

    public function actionSendMailWithoutRedirect()
    {
        $this->doSendMail();

        return __METHOD__;
    }

    public function actionSendMailWithRedirect()
    {
        $this->doSendMail();

        return $this->redirect(['site/index']);
    }

    private function doSendMail()
    {
        return \Yii::$app->mailer->compose()
            ->setFrom('from@domain.com')
            ->setTo('to@domain.com')
            ->setSubject('Message subject')
            ->setTextBody('Plain text content')
            ->setHtmlBody('<b>HTML content</b>')
            ->send()
        ;
    }
}
