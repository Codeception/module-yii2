<?php

file_put_contents(__DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/common/config/params-local.php', '<?php return [];');
file_put_contents(__DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/console/config/params-local.php', '<?php return [];');

// Copy the database to the output dir so we don't get changes in version control.
$config = \yii\helpers\ArrayHelper::merge(require __DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/console/config/main.php', [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
	    'dsn' => 'sqlite:' . __DIR__ . '/_data/db.sqlite'
	],
    ],
    'vendorPath' => __DIR__ . '/../../../vendor'
]);

return $config;
