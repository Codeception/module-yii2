<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector\Yii2;

use yii\test\FixtureTrait;
use yii\test\InitDbFixture;

final class FixturesStore
{
    use FixtureTrait;

    /**
     * Expects fixtures config
     */
    public function __construct(
        protected mixed $data
    ) {
    }

    public function fixtures(): mixed
    {
        return $this->data;
    }

    /**
     * @return array{initDbFixture: array{class: class-string}}
     */
    public function globalFixtures(): array
    {
        return [
            'initDbFixture' => [
                'class' => InitDbFixture::class,
            ],
        ];
    }
}
