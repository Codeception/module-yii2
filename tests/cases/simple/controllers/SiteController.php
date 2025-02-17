<?php

declare(strict_types=1);

namespace app\simple\controllers;
use app\simple\helpers\EmptyString;
use yii\base\Action;
use yii\helpers\Url;
use yii\web\Controller;

class SiteController extends Controller
{

    public function actionForm()
    {
        $action = Url::to(['site/post']);
        return <<<HTML
<html>
<body>
<form method="post" action="$action">
<input name="test" type="text" id="test">
<input type="submit" id="submit">Submit</input>
</form>
</body>
</html>


HTML;

    }

    public function actionPost()
    {
        \Yii::$app->response->statusCode = 201;
        return print_r(\Yii::$app->request->bodyParams, true);
    }

    public function actionException()
    {
        throw new \Exception('This is not an HttpException');
    }

    public function actionEnd()
    {
        \Yii::$app->response->statusCode = 500;
        \Yii::$app->end();
    }


    /**
     * @param Action $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function  beforeAction($action)
    {
        if ($action->id === 'empty-response') {
            \Yii::$app->response->stream = fopen('php://memory', 'r+');
            \Yii::$app->response->content = new EmptyString('Empty!');
            return false;
        }
        return parent::beforeAction($action);
    }

    public function actionEmptyResponse()
    {
        // Dummy
    }

    public function actionIndex()
    {
        return '';
    }
}