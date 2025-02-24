<?php

declare(strict_types=1);

namespace tests\fixtures;

use yii\test\DbFixture;

class EmptyFixture extends DbFixture
{
    public function load() {}

    public function unload() {}
}
