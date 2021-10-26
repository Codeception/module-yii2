<?php

namespace tests\closeConnections;

use Codeception\Example;
use tests\fixtures\EmptyFixture;
use tests\FunctionalTester;
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

    protected function numberProvider()
    {
        return array_pad([], 5, ['count' => 1]);
    }

    /**
     * @param FunctionalTester $I
     * @dataProvider numberProvider
     */
    public function NoConnections(FunctionalTester $I, Example $example)
    {
        $I->assertEquals(SqlliteHelper::connectionCount(), $example['count']);
    }

}