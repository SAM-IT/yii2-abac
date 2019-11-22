<?php
declare(strict_types=1);

namespace tests;

use testapp\models\User;
use SamIT\abac\engines\SimpleEngine;
use SamIT\abac\interfaces\Environment;
use SamIT\abac\repositories\MemoryRepository;
use SamIT\Yii2\abac\ActiveRecordResolver;
use SamIT\Yii2\abac\AuthManager;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\rbac\Assignment;
use yii\rbac\Permission;
use yii\rbac\Role;

final class AuthManagerCest
{
    /**
     * @var  AuthManager
     */
    private $authManager;

    public function _before(FunctionalTester $I)
    {
        $manager = new \SamIT\abac\AuthManager(
            new SimpleEngine([]),
            new MemoryRepository(),
            new ActiveRecordResolver(),
            new class extends \ArrayObject implements Environment {}
        );

        $schema = \Yii::$app->db->schema;
        \Yii::$app->db->createCommand()->createTable(User::tableName(), [
            'id' => $schema->createColumnSchemaBuilder('pk'),
            'name' => $schema->createColumnSchemaBuilder('string'),
        ])->execute();

        $user = new User();
        $user->name = 'bob';
        $user->save(false);

        $this->authManager = new AuthManager($manager, [
            'userClass' => User::class
        ]);
    }

    public function testGetRole(FunctionalTester $I)
    {
        $roleName = 'test';
        $I->assertInstanceOf(Role::class, $role = $this->authManager->createRole($roleName));
        $I->assertSame($roleName, $role->name);
        $I->assertEmpty($role->ruleName);

        $I->assertNull($this->authManager->getRole($roleName));
        $I->assertEmpty($this->authManager->getRoles());
        $this->authManager->add($role);
        $I->assertInstanceOf(Role::class, $this->authManager->getRole($roleName));

        $I->assertEquals([$role], $this->authManager->getRoles());
    }

    public function testAddRoleWithRule(FunctionalTester $I)
    {
        $I->expectThrowable(NotSupportedException::class, function() {
            $role = new Role();
            $role->name = 'abc';
            $role->ruleName = 'test';
            $this->authManager->add($role);
        });
    }

    public function testAddPermission(FunctionalTester $I)
    {
        $I->expectThrowable(NotSupportedException::class, function() {
            $permission = new Permission();
            $permission->name = 'abc';
            $this->authManager->add($permission);
        });
    }

    public function testAssign(FunctionalTester $I)
    {
        $role = new Role();
        $role->name = 'abc';
        $this->authManager->add($role);

        $I->assertFalse($this->authManager->checkAccess(1, $role->name));
        $assignment = $this->authManager->assign($role, 1);
        $I->assertInstanceOf(Assignment::class, $assignment);
        $I->assertTrue($this->authManager->checkAccess(1, $role->name));


        $I->expectThrowable(InvalidArgumentException::class, function() use ($role) {
            $this->authManager->assign($role, -6);
        });
    }

    /**
     * @param FunctionalTester $I
     */
    public function testRevoke(FunctionalTester $I)
    {
        $role = new Role();
        $role->name = 'abc';
        $this->authManager->add($role);
        $this->authManager->assign($role, 1);

        $I->assertTrue($this->authManager->checkAccess(1, $role->name));
        $I->assertEquals([1], $this->authManager->getUserIdsByRole($role->name));
        $I->assertEquals([$role], $this->authManager->getRolesByUser(1));
        $this->authManager->revoke($role, 1);
        $I->assertFalse($this->authManager->checkAccess(1, $role->name));

        $I->assertEmpty($this->authManager->getUserIdsByRole($role->name));
    }


}