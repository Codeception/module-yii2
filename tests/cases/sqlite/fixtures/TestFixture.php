<?php

declare(strict_types=1);

namespace tests\fixtures;


use yii\db\Connection;
use yii\db\Exception;
use yii\test\DbFixture;

class TestFixture extends DbFixture
{
    public $tableName = 'test';
    public $tableConfig = [
        'id' => 'int'
    ];

    public $dbComponents = [];

    public function load() {
        foreach($this->dbComponents as $name) {
            /** @var Connection $connection */
            $connection = \Yii::$app->get($name);
            $connection->createCommand()->createTable($this->tableName, $this->tableConfig)->execute();
        }
    }

    public function unload()
    {
        foreach($this->dbComponents as $name) {
            /** @var Connection $connection */
            $connection = \Yii::$app->get($name);
            if (in_array($this->tableName, $connection->getSchema()->getTableNames('', true))) {
                $connection->createCommand()->dropTable($this->tableName)->execute();
            }
        }
    }
}