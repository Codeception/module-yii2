<?php
namespace tests;

use tests\fixtures\TestFixture;
use yii\db\Connection;

class SqLiteCest
{
    /**
     * This is called before the database transaction is started.
     */
    public function _fixtures()
    {
        return [
            [
                'class' => TestFixture::class,
                'dbComponents' => ['db1', 'db21']
            ],
        ];
    }

    public function testSharedPDO(FunctionalTester $I)
    {
        /** @var Connection $db1 */
        $db1 = $I->grabComponent('db1');
        $I->assertEquals(['test'], $db1->schema->getTableNames('', true));

        /** @var Connection $db21 */
        $db21 = $I->grabComponent('db21');
        $I->assertEquals(['test'], $db21->schema->getTableNames('', true));

        /** @var Connection $db22 */
        $db22 = $I->grabComponent('db22');

        $I->assertEquals(['test'], $db22->schema->getTableNames('', true));
    }

    public function testTransaction(FunctionalTester $I)
    {
        /** @var Connection $db1 */
        $db1 = $I->grabComponent('db1');
        $I->assertFalse($db1->isActive);
        $db1->open();
        $I->assertNotNull($db1->getTransaction());
    }
}
