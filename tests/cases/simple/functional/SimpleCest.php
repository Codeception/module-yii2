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
        $I->amOnRoute('/site/form');
        $I->seeResponseCodeIs(200);
        $I->fillField('#test', 'test');
        $I->click('#submit');
        $I->canSeeResponseCodeIs(201);
    }

    public function testFormSubmit2(FunctionalTester $I)
    {
        $I->amOnRoute('/site/form');
        $I->seeResponseCodeIs(200);
        $I->submitForm('form', [
            'login-form[login]' => 'user',
            'login-form[password]' => 'test',
        ]);
        $I->canSeeResponseCodeIs(201);
    }

    public function testException(FunctionalTester $I)
    {
        $I->amOnRoute('/site/exception');
        $I->seeResponseCodeIsServerError();
        $content = $I->grabPageSource();
        $I->assertStringStartsWith("<pre>Exception &apos;Exception&apos; with message &apos;This is not an HttpException&apos;", $content);
    }

    public function testTypeError(FunctionalTester $I)
    {
        $I->amOnRoute('/site/type-error');
        $I->seeResponseCodeIsServerError();
        $content = $I->grabPageSource();
        $I->assertStringStartsWith('<pre>Exception &apos;TypeError&apos; with message &apos;Cannot assign string to property', $content);
    }

    public function testExitException(FunctionalTester $I)
    {
        $I->amOnRoute('/site/end');
        $I->seeResponseCodeIs(500);
    }

    public function testEmptyResponse(FunctionalTester $I)
    {
        $I->amOnRoute('/site/empty-response');
        $I->seeResponseCodeIs(200);
    }

    public function testMissingUser(FunctionalTester $I)
    {
        $I->expectThrowable(ModuleException::class, function() use ($I) {
            $I->amLoggedInAs('nobody');
        });
        $I->amOnRoute('/site/index');
        $I->assertTrue(\Yii::$app->user->isGuest);
    }
}
