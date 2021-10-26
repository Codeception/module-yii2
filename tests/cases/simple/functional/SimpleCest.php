<?php
namespace tests;

use Codeception\Exception\ModuleException;
use yii\base\ExitException;
use yii\web\Application;

class SimpleCest
{

    public function testInstantiation(FunctionalTester $I)
    {
        $I->assertInstanceOf(Application::class, \Yii::$app);
    }

    public function testFormSubmit(FunctionalTester $I)
    {
        $I->amOnPage(['site/form']);
        $I->seeResponseCodeIs(200);

        $I->fillField('#test', 'test');
        $I->click('#submit');
        $I->canSeeResponseCodeIs(201);
    }

    public function testFormSubmit2(FunctionalTester $I)
    {
        $I->amOnPage(['site/form']);
        $I->seeResponseCodeIs(200);
        $I->submitForm('form', [
            'login-form[login]' => 'user',
            'login-form[password]' => 'test',
        ]);
        $I->canSeeResponseCodeIs(201);
    }

    public function testException(FunctionalTester $I)
    {
        $I->expectException(new \Exception('This is not an HttpException'), function() use ($I) {
            $I->amOnPage(['site/exception']);
        });
        $I->assertInstanceOf(Application::class, \Yii::$app);
    }

    public function testExceptionInBeforeRequest(FunctionalTester $I)
    {
        $e = new \Exception('This is not an HttpException');
        \Yii::$app->params['throw'] = $e;
        $I->expectException($e, function() use ($I) {
            $I->amOnPage(['site/exception']);
        });
    }

    public function testExitException(FunctionalTester $I)
    {
        $I->amOnPage(['site/end']);
        $I->seeResponseCodeIs(500);
    }

    public function testEmptyResponse(FunctionalTester $I)
    {
        $I->amOnPage(['site/empty-response']);
        $I->seeResponseCodeIs(200);
    }

    public function testMissingUser(FunctionalTester $I)
    {
        $I->expectException(ModuleException::class, function() use ($I) {
            $I->amLoggedInAs('nobody');
        });
        $I->amOnPage('site/index');
        $I->assertTrue(\Yii::$app->user->isGuest);
    }
}