<?php
namespace Codeception\Lib\Connector\Yii2;

use yii\test\FixtureTrait;
use yii\test\InitDbFixture;

class FixturesStore
{
    use FixtureTrait;

    protected $data;

    /**
     * @var string Class-owner of this store
     */
    protected $owner;

    /**
     * Expects fixtures config
     *
     * FixturesStore constructor.
     * @param $data
     */
    public function __construct($data, $owner)
    {
        $this->data = $data;
        $this->owner = $owner;
    }

    public function fixtures()
    {
        return $this->data;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function globalFixtures()
    {
        return [
            InitDbFixture::className()
        ];
    }
}
