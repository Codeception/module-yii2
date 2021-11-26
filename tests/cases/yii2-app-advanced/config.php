<?php

file_put_contents(__DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/common/config/params-local.php', '<?php return [];');
file_put_contents(__DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/frontend/config/params-local.php', '<?php return [];');

// Copy the database to the output dir so we don't get changes in version control.
copy(__DIR__ . '/_data/db.sqlite', codecept_output_dir('/db.sqlite'));

$config = \yii\helpers\ArrayHelper::merge(require __DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/frontend/config/main.php', [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . codecept_output_dir('/db.sqlite')
        ],
        'request' => [
            'cookieValidationKey' => 'test'
        ],
        'assetManager' => [
            'basePath' => sys_get_temp_dir()
        ],
        'mailer' => [
            'viewPath' => '@common/mail',
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset'
    ],
    'vendorPath' => __DIR__ . '/../../../vendor'
]);

return $config;