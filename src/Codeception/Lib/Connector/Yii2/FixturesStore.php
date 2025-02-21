<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector\Yii2;

use yii\test\FixtureTrait;
use yii\test\InitDbFixture;

class FixturesStore
{
    use FixtureTrait;

    protected mixed $data;

    /**
     * Expects fixtures config
     *
     * FixturesStore constructor.
     */
    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    public function fixtures(): mixed
    {
        return $this->data;
    }

    public function globalFixtures(): array
    {
        return [
            'initDbFixture' => [
                'class' => InitDbFixture::class,
            ],
        ];
    }
}
