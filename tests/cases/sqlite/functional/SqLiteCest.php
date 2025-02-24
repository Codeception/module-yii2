<?php

declare(strict_types=1);

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
                'dbComponents' => ['db1', 'db21'],
            ],
        ];
    }

    public function testSharedPDO(FunctionalTester $I)
    {
        /** @var Connection $db1 */
        $db1 = \Yii::$app->get('db1');
        $I->assertSame(['test'], $db1->schema->getTableNames('', true));

        /** @var Connection $db21 */
        $db21 = \Yii::$app->get('db21');
        $I->assertSame(['test'], $db21->schema->getTableNames('', true));

        /** @var Connection $db22 */
        $db22 = \Yii::$app->get('db22');

        $I->assertSame(['test'], $db22->schema->getTableNames('', true));
    }

    public function testTransaction(FunctionalTester $I)
    {
        /** @var Connection $db1 */
        $db1 = \Yii::$app->get('db1');
        $I->assertFalse($db1->isActive);
        $db1->open();
        $I->assertNotNull($db1->getTransaction());
    }
}
