<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector\Yii2;

use yii\test\FixtureTrait;
use yii\test\InitDbFixture;

class FixturesStore
{
    use FixtureTrait;

    protected $data;

    /**
     * Expects fixtures config
     *
     * FixturesStore constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function fixtures()
    {
        return $this->data;
    }

    public function globalFixtures()
    {
        return [
            'initDbFixture' => [
                'class' => InitDbFixture::class,
            ],
        ];
    }
}
