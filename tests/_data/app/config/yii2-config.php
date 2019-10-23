<?php
declare(strict_types=1);
return [
    'id' => 'yii2-abac-test',
    'basePath' => dirname(dirname(__DIR__)),
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite::memory:'
        ],
        'authManager' => [
            'class' => \SamIT\Yii2\abac\AccessChecker::class
        ],
    ]
];