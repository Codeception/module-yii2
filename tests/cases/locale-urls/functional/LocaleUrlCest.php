<?php

declare(strict_types=1);

namespace tests;

use yii\web\Application;

class LocaleUrlCest
{
    public function testInstantiation(FunctionalTester $I)
    {
        $I->assertInstanceOf(Application::class, \Yii::$app);
    }

    public function testMultipleGet(FunctionalTester $I)
    {
        $I->amOnRoute('/en/site/form');
        $I->amOnRoute('/en/site/form');
    }
    public function testFormSubmit(FunctionalTester $I)
    {
        $I->amOnRoute('site/form');
        $I->seeResponseCodeIs(200);

        $I->fillField('#test', 'test');
        $I->click('#submit');
        $I->canSeeResponseCodeIs(201);
    }

    public function testFormSubmit2(FunctionalTester $I)
    {
        $I->amOnRoute('/en/site/form');
        $I->seeResponseCodeIs(200);
        $I->submitForm('form', [
            'login-form[login]' => 'user',
            'login-form[password]' => 'test',
        ]);
        $I->canSeeResponseCodeIs(201);
    }

}
