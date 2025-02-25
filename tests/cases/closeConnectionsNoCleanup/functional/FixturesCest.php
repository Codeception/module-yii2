<?php

declare(strict_types=1);

namespace tests\closeConnectionsNoCleanup;

use tests\fixtures\EmptyFixture;
use tests\FunctionalTester;
use tests\helpers\SqlliteHelper;

final class FixturesCest
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
        $I->assertSame(0, $count);
    }

    public function NoConnections2(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertSame(0, $count);
    }

    public function NoConnections3(FunctionalTester $I)
    {
        $count = SqlliteHelper::connectionCount();
        $I->assertSame(0, $count);
    }
}
