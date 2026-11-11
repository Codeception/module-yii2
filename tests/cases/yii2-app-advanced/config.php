<?php

declare(strict_types=1);

file_put_contents(__DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/common/config/params-local.php', '<?php return [];');
file_put_contents(__DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/frontend/config/params-local.php', '<?php return [];');

// Copy the database to the output dir so we don't get changes in version control.
copy(__DIR__ . '/_data/db.sqlite', codecept_output_dir('/db.sqlite'));

$config = \yii\helpers\ArrayHelper::merge(require __DIR__ . '/../../../vendor/yiisoft/yii2-app-advanced/frontend/config/main.php', [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'sqlite:' . codecept_output_dir('/db.sqlite'),
        ],
        'request' => [
            'cookieValidationKey' => 'test',
        ],
        'assetManager' => [
            'basePath' => sys_get_temp_dir(),
        ],
        'mailer' => [
            'viewPath' => '@common/mail',
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'vendorPath' => __DIR__ . '/../../../vendor',
]);

// The modern template's SiteController constructor-injects `yii\mail\MailerInterface`, which
// Yii's DI cannot instantiate on its own (it is an interface). Bind it to the application
// mailer, which the Connector replaces with its in-memory mailer.
\Yii::$container->set(\yii\mail\MailerInterface::class, static fn (): \yii\mail\MailerInterface => \Yii::$app->mailer);

return $config;
