<?php
declare(strict_types=1);

namespace SamIT\Yii2\abac;

use SamIT\abac\AuthManager;
use SamIT\abac\values\Authorizable;
use yii\base\Configurable;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\rbac\CheckAccessInterface;
use yii\web\IdentityInterface;

class AccessChecker  implements
    CheckAccessInterface,
    Configurable
{
    public const TARGET_PARAM = 'target';
    public const GLOBAL = '__global__';
    public const GUEST = '__guest__';

    // Using special characters prevents resolvers from accidentally resolving this when looking for class names.
    public const BUILTIN = '{builtin}';

    /**
     * @var AuthManager
     */
    private $manager;

    /**
     * @var string Auth name to use for permission checks without target
     */
    private $globalName = self::BUILTIN;

    /**
     * @var string Auth id to use for permission checks without target
     */
    private $globalId = self::GLOBAL;

    private $guestId = self::GUEST;

    private $guestName = self::BUILTIN;

    /**
     * @var string Name of a class that implements IdentityInterface
     */
    private $userClass;

      public function __construct(
        AuthManager $manager,
        $config = []
    ) {
        $this->manager = $manager;

        foreach($config as $key => $value) {
            $this->$key = $value;
        }

          if (!isset($this->userClass)) {
              throw new \yii\base\InvalidConfigException("userClass must be configured.");
          }
          if (!is_subclass_of($this->userClass, IdentityInterface::class, true)) {
              throw new InvalidConfigException("userClass must implement IdentityInterface");
          }
    }

    /**
     * @return Authorizable
     */
    final protected function getGlobalAuthorizable(): Authorizable
    {
        return new Authorizable($this->globalId, $this->globalName);
    }

    private $_userCache = [];
    /**
     * @param null|string $id
     * @return IdentityInterface|null
     */
    final protected function getUser(?string $id): ?IdentityInterface
    {
        if (!isset($id)) {
            return null;
        }
        // We cache positive lookups only.
        if (!isset($this->_userCache[$id])) {
            $this->_userCache[$id] = $this->userClass::findIdentity($id);
        }
        return $this->_userCache[$id];
    }

    /**
     * @param int|string $userId
     * @param string $permissionName
     * @param array $params
     * @return bool
     */
    public function checkAccess($userId, $permissionName, $params = [])
    {
        $user = $this->getUser((string) $userId) ?? new Authorizable($this->guestId, $this->guestName);

        if (isset($params[self::TARGET_PARAM]) && !is_object($params[self::TARGET_PARAM])) {
            throw new InvalidArgumentException('Target, if passed, must be an object');
        }

        $target = $params[self::TARGET_PARAM] ?? $this->getGlobalAuthorizable();

        return $this->manager->check($user, $target, $permissionName);
    }

}