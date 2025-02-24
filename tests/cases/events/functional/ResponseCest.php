<?php

declare(strict_types=1);

namespace tests;

use Codeception\Lib\Connector\Yii2;
use yii\base\Event;

class ResponseCest
{
    public function testAfterSend(FunctionalTester $I)
    {
        $sources = [];
        \Yii::$app->on('responseBeforeSendConfig', function (Event $event) use (&$sources) {
            $sources[] = 'config';
        });
        \Yii::$app->on('responseBeforeSendBootstrap', function (Event $event) use (&$sources) {
            $sources[] = 'bootstrap';
        });
        $I->assertEmpty($sources);
        $I->amOnRoute('/');
        $I->assertSame(['config', 'bootstrap'], $sources);

        $sources = [];
        $I->amOnRoute('/');
        $I->assertSame(['config', 'bootstrap'], $sources);

    }

    public function testAfterSendWithRecreate(FunctionalTester $I, \Codeception\Module\Yii2 $module)
    {
        $module->_reconfigure([
            'responseCleanMethod' => Yii2::CLEAN_RECREATE,
        ]);
        $module->client->startApp();
        $sources = [];
        \Yii::$app->on('responseBeforeSendConfig', function (Event $event) use (&$sources) {
            $sources[] = 'config';
        });
        \Yii::$app->on('responseBeforeSendBootstrap', function (Event $event) use (&$sources) {
            $sources[] = 'bootstrap';
        });
        $I->assertEmpty($sources);
        $I->amOnRoute('/');
        $I->assertSame(['config', 'bootstrap'], $sources);

        $sources = [];
        $I->amOnRoute('/');

        // The module should fall back to the CLEAN_CLEAR method and keep event handlers intact.
        $I->assertSame(['config', 'bootstrap'], $sources);

    }

    public function testAfterSendWithForcedRecreate(FunctionalTester $I, \Codeception\Module\Yii2 $module)
    {
        $module->_reconfigure([
            'responseCleanMethod' => Yii2::CLEAN_FORCE_RECREATE,
        ]);
        $module->client->startApp();
        $sources = [];
        \Yii::$app->on('responseBeforeSendConfig', function (Event $event) use (&$sources) {
            $sources[] = 'config';
        });
        \Yii::$app->on('responseBeforeSendBootstrap', function (Event $event) use (&$sources) {
            $sources[] = 'bootstrap';
        });

        $I->assertEmpty($sources);
        $I->amOnRoute('/');

        // We recreated the response component, since it has an event handler in its config
        // that event handler will still work.
        $I->assertSame(['config'], $sources);

        $sources = [];
        $I->amOnRoute('/');

        // We recreated the response component, since it has an event handler in its config
        // that event handler will still work.
        $I->assertSame(['config'], $sources);

    }
}
