<?php

declare(strict_types=1);

final class PageCest
{
    public function testCache(\tests\FunctionalTester $I)
    {
        $I->amOnRoute('user/index');
        $I->canSeeResponseCodeIs(200);

        $I->amOnRoute('user/index');
        $I->canSeeResponseCodeIs(200);
    }
}
