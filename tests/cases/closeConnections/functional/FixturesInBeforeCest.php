<?php

declare(strict_types=1);

namespace tests\closeConnections;

use Codeception\Example;
use tests\fixtures\EmptyFixture;
use tests\FunctionalTester;
use tests\helpers\SqlliteHelper;

final class FixturesInBeforeCest
{
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            [
                'class' => EmptyFixture::class,
            ],
        ]);
    }

    protected function numberProvider()
    {
        return array_pad([], 5, ['count' => 1]);
    }

    /**
     * @dataProvider numberProvider
     */
    public function NoConnections(FunctionalTester $I, Example $example)
    {
        $I->assertSame(SqlliteHelper::connectionCount(), $example['count']);
    }
}
