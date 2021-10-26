<?php

namespace tests\closeConnections;

use Codeception\Example;
use tests\FunctionalTester;
use tests\helpers\SqlliteHelper;

class NoFixturesCest
{

    protected function numberProvider()
    {
        return array_pad([], 5, ['count' => 0]);
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