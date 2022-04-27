<?php

declare(strict_types=1);

namespace tests;

use SamIT\abac\AuthManager;
use SamIT\abac\engines\SimpleEngine;
use SamIT\abac\interfaces\Environment;
use SamIT\abac\repositories\EmptyRepository;
use SamIT\Yii2\abac\AccessChecker;
use SamIT\Yii2\abac\ActiveRecordResolver;
use testapp\models\Car;
use testapp\models\User;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

final class AccessCheckerCest
{
    public function _before(FunctionalTester $I): void
    {
        $schema = \Yii::$app->db->schema;
        \Yii::$app->db->createCommand()->createTable(User::tableName(), [
            'id' => $schema->createColumnSchemaBuilder('pk'),
            'name' => $schema->createColumnSchemaBuilder('string'),
        ])->execute();
    }

    // tests
    public function testConfigValidation(FunctionalTester $I): void
    {
        $ruleEngine = new SimpleEngine();
        $environment = new class() extends \ArrayObject implements Environment {
        };

        $manager = new AuthManager($ruleEngine, new EmptyRepository(), new ActiveRecordResolver(), $environment);
        $I->expectThrowable(InvalidConfigException::class, function () use ($manager) {
            /** @phpstan-ignore-next-line */
            $accessChecker = new AccessChecker($manager, Car::class);
        });

        $accessChecker = new AccessChecker($manager, User::class);
    }

    public function testCheckAccess(FunctionalTester $I): void
    {
        $ruleEngine = new SimpleEngine();
        $environment = new class() extends \ArrayObject implements Environment {
        };

        $manager = new AuthManager($ruleEngine, new EmptyRepository(), new ActiveRecordResolver(), $environment);
        $accessChecker = new AccessChecker($manager, User::class);

        $I->assertFalse($accessChecker->checkAccess(1, 'test'));
    }

    public function testCheckAccessInvalidTargets(FunctionalTester $I): void
    {
        $ruleEngine = new SimpleEngine();
        $environment = new class() extends \ArrayObject implements Environment {
        };

        $manager = new AuthManager($ruleEngine, new EmptyRepository(), new ActiveRecordResolver(), $environment);
        $accessChecker = new AccessChecker($manager, User::class);

        $I->expectThrowable(InvalidArgumentException::class, function () use ($accessChecker) {
            $accessChecker->checkAccess(1, 'test', [AccessChecker::TARGET_PARAM => 'cool']);
        });
    }
}
