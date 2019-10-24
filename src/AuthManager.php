<?php
declare(strict_types=1);

namespace SamIT\Yii2\abac;


use SamIT\abac\exceptions\UnresolvableSourceException;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\rbac\Assignment;
use yii\rbac\ManagerInterface;
use yii\rbac\Permission;
use yii\rbac\Role;

/**
 * Class AuthManager
 * @package SamIT\Yii2\abac
 *
 * This class implements RBAC on top of ABAC.
 * With ABAC we have source, target and permission for each grant
 * and rules that may decide on the same source, target and permission, combined with properties they it can obtain from
 * the environment.
 *
 * In RBAC users are assigned roles. This class maps a role to an explicit grant with permission == role name.
 * In Yii2 roles and permissions are a directed graph, each role can contain other roles and permissions.
 * Each permission may contain other permissions.
 * Permissions may also have a rule attached, this implementation does not support rules.
 * To ensure security during when switching to ABAC, permissions and roles with a rule always result in exceptions.
 *
 * Currently nested roles / permissions are not supported, they will be supported in the future via ImpliedPermissionRule
 *
 *
 */
class AuthManager extends AccessChecker implements ManagerInterface
{
    /**
     * @var \SamIT\abac\AuthManager
     */
    private $manager;

    public function __construct(\SamIT\abac\AuthManager $manager, $config = [])
    {
        parent::__construct($manager, $config);
        $this->manager = $manager;

    }


    /**
     * @inheritDoc
     */
    public function createRole($name)
    {
        $role = new Role();
        $role->name = $name;
        return $role;
    }

    /**
     * @inheritDoc
     */
    public function createPermission($name)
    {
        $role = new Permission();
        $role->name = $name;
        return $role;
    }

    /**
     * @inheritDoc
     */
    public function add($object)
    {
        if ($object instanceof Role) {
            if (isset($object->ruleName)) {
                throw new NotSupportedException('Rules are not supported');
            }
            $this->manager->grant($this->getGlobalAuthorizable(), $this->getGlobalAuthorizable(), $object->name);
        }
        throw new NotSupportedException();
    }

    /**
     * @inheritDoc
     */
    public function remove($object): bool
    {
        if ($object instanceof Role) {
            $this->removeRole($object);
            return true;
        }
        throw new NotSupportedException();
    }

    private function removeRole(Role $role): void
    {
        $repository = $this->manager->getRepository();
        foreach($repository->search(null, $this->getGlobalAuthorizable(), $role->name) as $grant)
        {
            $this->manager->getRepository()->revoke($grant);
        }
    }
    /**
     * @inheritDoc
     */
    public function update($name, $object)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRole($name)
    {
        $global = $this->getGlobalAuthorizable();
        if ($this->manager->check($global, $global, $name)) {
            $result = new Role();
            $result->name = $name;
            $result->createdAt = 0;
            return $result;
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        $global = $this->getGlobalAuthorizable();
        $result = [];
        foreach($this->manager->getRepository()->search($global, $global, null) as $grant) {
            $result[] = $role = new Role();
            $role->name = $grant->getPermission();
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getRolesByUser($userId)
    {
        $global = $this->getGlobalAuthorizable();
        $user = $this->getUser($userId);
        if (!isset($user)) {
            throw new InvalidArgumentException("User with id $userId not found");
        }

        $source = $this->manager->resolveSubject($user);
        if (!isset($source)) {
            throw new UnresolvableSourceException($source);
        }
        $result = [];
        foreach($this->manager->getRepository()->search($source, $global, null) as $grant) {
            $result[] = $role = new Role();
            $role->name = $grant->getPermission();
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getChildRoles($roleName)
    {
        // Implied permission rule?
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getPermission($name)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getPermissions()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getPermissionsByRole($roleName)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getPermissionsByUser($userId)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRule($name)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getRules()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function canAddChild($parent, $child)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function addChild($parent, $child)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function removeChild($parent, $child)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function removeChildren($parent)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasChild($parent, $child)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getChildren($name)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function assign($role, $userId)
    {
        $source = $this->getUser($userId);
        if (!isset($source)) {
            throw new InvalidArgumentException("User with id $userId not found");
        }

        $this->manager->grant($source, $this->getGlobalAuthorizable(), $role->name);

        $result = new Assignment();
        $result->userId = $userId;
        $result->roleName = $role->name;
        $result->createdAt = time();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function revoke($role, $userId)
    {
        $source = $this->getUser($userId);
        if (!isset($source)) {
            throw new InvalidArgumentException("User with id $userId not found");
        }

        try {
            $this->manager->revoke($source, $this->getGlobalAuthorizable(), $role->name);
        } catch (\RuntimeException $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function revokeAll($userId)
    {
        $source = $this->getUser($userId);
        if (!isset($source)) {
            throw new InvalidArgumentException("User with id $userId not found");
        }


        $authorizable = $this->manager->resolveSubject($source);
        if (!isset($authorizable)) {
            throw new UnresolvableSourceException($source);
        }

        $repository = $this->manager->getRepository();
        foreach($repository->search($authorizable, $this->getGlobalAuthorizable(), null) as $grant)
        {
            $this->manager->getRepository()->revoke($grant);
        }
    }

    /**
     * @inheritDoc
     */
    public function getAssignment($roleName, $userId)
    {
        if ($this->checkAccess($userId, $roleName, [self::TARGET_PARAM => $this->getGlobalAuthorizable()])) {
            $result = new Assignment();
            $result->userId = $userId;
            $result->roleName = $roleName;
            $result->createdAt = 0;
            return $result;
        } else {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAssignments($userId)
    {
        $source = $this->getUser($userId);
        if (!isset($source)) {
            throw new InvalidArgumentException("User with id $userId not found");
        }


        $authorizable = $this->manager->resolveSubject($source);
        if (!isset($authorizable)) {
            throw new UnresolvableSourceException($source);
        }

        $repository = $this->manager->getRepository();
        $result = [];
        foreach($repository->search($authorizable, $this->getGlobalAuthorizable(), null) as $grant)
        {
            $assignment = new Assignment();
            $assignment->userId = $userId;
            $assignment->createdAt = 0;
            $assignment->roleName = $grant->getPermission();
            $result[] = $assignment;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getUserIdsByRole($roleName)
    {
        $repository = $this->manager->getRepository();
        $result = [];
        foreach($repository->search(null, $this->getGlobalAuthorizable(), $roleName) as $grant)
        {
            $assignment = new Assignment();
            $assignment->userId = $grant->getSource()->getId();
            $assignment->createdAt = 0;
            $assignment->roleName = $grant->getPermission();
            $result[] = $assignment;
        }
        return $result;

    }

    /**
     * @inheritDoc
     */
    public function removeAll()
    {
        throw new NotSupportedException();
    }

    /**
     * @inheritDoc
     */
    public function removeAllPermissions()
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function removeAllRoles()
    {
        $this->removeAllAssignments();
    }

    /**
     * @inheritDoc
     */
    public function removeAllRules()
    {
        // Empty rule engine?
    }

    /**
     * @inheritDoc
     */
    public function removeAllAssignments()
    {
        $repository = $this->manager->getRepository();
        foreach($repository->search(null, $this->getGlobalAuthorizable(), null) as $grant)
        {
            $this->manager->getRepository()->revoke($grant);
        }
    }
}