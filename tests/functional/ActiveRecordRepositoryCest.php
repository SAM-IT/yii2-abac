<?php

declare(strict_types=1);

namespace tests;

use SamIT\abac\values\Authorizable;
use SamIT\abac\values\Grant;
use SamIT\Yii2\abac\ActiveRecordRepository;
use testapp\models\Permission;

class ActiveRecordRepositoryCest
{
    public function _before(FunctionalTester $I): void
    {
        $schema = \Yii::$app->db->schema;
        \Yii::$app->db->createCommand()->createTable(Permission::tableName(), [
            'id' => $schema->createColumnSchemaBuilder('pk'),
            'source' => $schema->createColumnSchemaBuilder('string'),
            'source_id' => $schema->createColumnSchemaBuilder('string'),
            'target_name' => $schema->createColumnSchemaBuilder('string'),
            'target_id' => $schema->createColumnSchemaBuilder('string'),
            'permission' => $schema->createColumnSchemaBuilder('string'),
        ])->execute();
    }

    // tests
    public function testGrant(FunctionalTester $I): void
    {
        $repository = new ActiveRecordRepository(Permission::class, [
            ActiveRecordRepository::SOURCE_NAME => 'source'
        ]);
        $auth1 = new Authorizable('1', 'a');
        $auth2 = new Authorizable('2', 'test');

        $grant = new Grant($auth1, $auth2, 'abc');
        $repository->grant($grant);

        $I->assertTrue($repository->check($grant));

        codecept_debug(Permission::find()->asArray()->all());
        $I->seeRecord(Permission::class, [
            'source_id' => $auth1->getId(),
            'source' => $auth1->getAuthName(),
            'target_id' => $auth2->getId(),
            'target_name' => $auth2->getAuthName(),
            'permission' => 'abc'
        ]);
    }

    public function testGrantValidationFailed(FunctionalTester $I): void
    {
        $repository = new ActiveRecordRepository(Permission::class, [
            ActiveRecordRepository::SOURCE_NAME => 'source'
        ]);
        $auth1 = new Authorizable('1', 'invalid');
        $auth2 = new Authorizable('2', 'test');

        $grant = new Grant($auth1, $auth2, 'abc');

        $I->expectThrowable(\RuntimeException::class, fn () => $repository->grant($grant));

        $I->dontSeeRecord(Permission::class, [
            'source_id' => $auth1->getId(),
            'source' => $auth1->getAuthName(),
            'target_id' => $auth2->getId(),
            'target_name' => $auth2->getAuthName(),
            'permission' => 'abc'
        ]);
    }

    public function testRevoke(FunctionalTester $I): void
    {
        $repository = new ActiveRecordRepository(Permission::class, [
            ActiveRecordRepository::SOURCE_NAME => 'source'
        ]);

        $auth1 = new Authorizable('1', 'invalid');
        $auth2 = new Authorizable('2', 'test');

        $grant = new Grant($auth1, $auth2, 'abc');

        $I->assertSame(0, (int) Permission::find()->count());
        $I->assertFalse($repository->check($grant));
        $repository->revoke($grant);
        $I->assertFalse($repository->check($grant));
        $I->assertSame(0, (int) Permission::find()->count());
    }

    public function testSearch(FunctionalTester $I): void
    {
        $repository = new ActiveRecordRepository(Permission::class, [
            ActiveRecordRepository::SOURCE_NAME => 'source'
        ]);

        $auth1 = new Authorizable('1', 'invalid');
        $auth2 = new Authorizable('2', 'test');

        $I->assertCount(0, $repository->search(null, null, null));
        $I->haveRecord(Permission::class, [
            'source_id' => $auth1->getId(),
            'source' => $auth1->getAuthName(),
            'target_id' => $auth2->getId(),
            'target_name' => $auth2->getAuthName(),
            'permission' => 'test'
        ]);

        $I->assertCount(1, $repository->search(null, null, null));
        $I->assertCount(1, $repository->search($auth1, null, null));
        $I->assertCount(1, $repository->search(null, $auth2, null));
        $I->assertCount(1, $repository->search($auth1, $auth2, null));
        $I->assertCount(1, $repository->search(null, null, 'test'));
        $I->assertCount(1, $repository->search($auth1, null, 'test'));
        $I->assertCount(1, $repository->search(null, $auth2, 'test'));
        $I->assertCount(1, $repository->search($auth1, $auth2, 'test'));

        $I->assertCount(0, $repository->search(null, null, 'a'));
        $I->assertCount(0, $repository->search($auth1, null, 'a'));
        $I->assertCount(0, $repository->search($auth2, null, null));
    }
}
