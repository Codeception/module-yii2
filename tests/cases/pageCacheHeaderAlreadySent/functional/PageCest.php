<?php

class PageCest {

    public function testCache(\tests\FunctionalTester $I)
    {
        $I->amOnPage('user/index');
        $I->canSeeResponseCodeIs(200);

        $I->amOnPage('user/index');
        $I->canSeeResponseCodeIs(200);
    }
}