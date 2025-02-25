<?php

declare(strict_types=1);

namespace tests\fixtures;

use yii\test\DbFixture;

final class EmptyFixture extends DbFixture
{
    public function load()
    {
    }

    public function unload()
    {
    }
}
