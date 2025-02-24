<?php

declare(strict_types=1);

namespace tests\closeConnectionsNoCleanup;

use tests\FunctionalTester;
use tests\fixtures\EmptyFixture;
use tests\helpers\SqlliteHelper;

class FixturesInBeforeCest
{
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            [
                'class' => EmptyFixture::class,
            ],
        ]);
    }

    public function OnlyOneConnection1(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertSame(1, $count);
    }

    public function OnlyOneConnection2(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertSame(1, $count);
    }

    public function OnlyOneConnection3(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertSame(1, $count);
    }
}
