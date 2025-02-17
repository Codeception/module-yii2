<?php

declare(strict_types=1);

namespace tests;

use yii\web\Application;

final class MockMailerCest
{
    public function testInstantiation(FunctionalTester $I)
    {
        $I->assertInstanceOf(Application::class, \Yii::$app);
    }

    public function testCountMailSentWithoutRedirect(FunctionalTester $I)
    {
        $I->amOnRoute('site/send-mail-without-redirect');

        $I->seeEmailIsSent(1);
    }

    public function testCountMailSentWithRedirect(FunctionalTester $I)
    {
        $I->amOnRoute('site/send-mail-with-redirect');

        $I->seeEmailIsSent(1);
    }
}
