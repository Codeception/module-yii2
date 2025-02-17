<?php

declare(strict_types=1);

namespace tests\closeConnectionsNoCleanup;

use tests\FunctionalTester;
use tests\fixtures\EmptyFixture;
use tests\helpers\SqlliteHelper;

class ThirdCest
{
    public function NoConnections1(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertSame(0, $count);
    }

    public function OnlyOneConnection2(FunctionalTester $I)
    {
        $I->haveFixtures([
            [
                'class' => EmptyFixture::class,
            ],
        ]);

        $count = SqlliteHelper::connectionCount();
        $I->assertSame(1, $count);
    }

    public function OnlyOneConnection3(FunctionalTester $I)
    {
        $I->haveFixtures([
            [
                'class' => EmptyFixture::class,
            ],
        ]);

        $count = SqlliteHelper::connectionCount();
        $I->assertSame(1, $count);
    }
}