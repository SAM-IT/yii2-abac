<?php namespace tests;
use app\models\Car;
use app\models\User;
use SamIT\abac\AuthManager;
use SamIT\abac\engines\SimpleEngine;
use SamIT\abac\interfaces\Environment;
use SamIT\abac\repositories\EmptyRepository;
use SamIT\Yii2\abac\AccessChecker;
use SamIT\Yii2\abac\ActiveRecordResolver;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

class AccessCheckerCest
{
    public function _before(FunctionalTester $I)
    {
        $schema = \Yii::$app->db->schema;
        \Yii::$app->db->createCommand()->createTable(User::tableName(), [
            'id' => $schema->createColumnSchemaBuilder('pk'),
            'name' => $schema->createColumnSchemaBuilder('string'),
        ])->execute();
    }

    // tests
    public function testConfigValidation(FunctionalTester $I)
    {
        $ruleEngine = new SimpleEngine([]);
        $environment = new class extends \ArrayObject implements Environment {};

        $manager = new AuthManager($ruleEngine, new EmptyRepository(), new ActiveRecordResolver(), $environment);
        $I->expectThrowable(InvalidConfigException::class, function() use ($manager) {
            $accessChecker =  new AccessChecker($manager);
        });

        $I->expectThrowable(InvalidConfigException::class, function() use ($manager) {
            $accessChecker =  new AccessChecker($manager, [
                'userClass' => Car::class
            ]);
        });

        $accessChecker =  new AccessChecker($manager, [
            'userClass' => User::class
        ]);

    }

    public function testCheckAccess(FunctionalTester $I)
    {
        $ruleEngine = new SimpleEngine([]);
        $environment = new class extends \ArrayObject implements Environment {};

        $manager = new AuthManager($ruleEngine, new EmptyRepository(), new ActiveRecordResolver(), $environment);
        $accessChecker =  new AccessChecker($manager, [
            'userClass' => User::class
        ]);

        $I->assertFalse($accessChecker->checkAccess(1, 'test'));


    }

    public function testCheckAccessInvalidTargets(FunctionalTester $I)
    {
        $ruleEngine = new SimpleEngine([]);
        $environment = new class extends \ArrayObject implements Environment {};

        $manager = new AuthManager($ruleEngine, new EmptyRepository(), new ActiveRecordResolver(), $environment);
        $accessChecker =  new AccessChecker($manager, [
            'userClass' => User::class
        ]);

        $I->expectThrowable(InvalidArgumentException::class, function() use ($accessChecker) {
            $accessChecker->checkAccess(1, 'test', [AccessChecker::TARGET_PARAM => 'cool']);
        });



    }
}
