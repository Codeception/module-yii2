<?php

namespace tests\closeConnectionsNoCleanup;

use tests\FunctionalTester;
use tests\fixtures\EmptyFixture;
use tests\helpers\SqlliteHelper;

class FixturesCest
{
    public function _fixtures()
    {
        return [
            [
                'class' => EmptyFixture::class,
            ],
        ];
    }

    public function NoConnections1(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertEquals(0, $count);
    }

    public function NoConnections2(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertEquals(0, $count);
    }

    public function NoConnections3(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertEquals(0, $count);
    }

}