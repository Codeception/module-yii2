<?php

declare(strict_types=1);

require_once __DIR__ . '/app/App.php';

return [
    'class' => app\leak\App::class,
    'id' => 'leak',
    'basePath' => __DIR__,
];
