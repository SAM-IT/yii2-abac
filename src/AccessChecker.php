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

class AccessChecker implements
    CheckAccessInterface
{
    public const TARGET_PARAM = 'target';
    public const GLOBAL = '__global__';
    public const GUEST = '__guest__';

    // Using special characters prevents resolvers from accidentally resolving this when looking for class names.
    public const BUILTIN = '{builtin}';

    private AuthManager $manager;

    /**
     * @var string Auth name to use for permission checks without target
     */
    private string $globalName = self::BUILTIN;

    /**
     * @var string Auth id to use for permission checks without target
     */
    private string $globalId = self::GLOBAL;

    private string $guestId = self::GUEST;

    private string $guestName = self::BUILTIN;

    /**
     * @param class-string<IdentityInterface> $userClass
     */
    public function __construct(
        AuthManager $manager,
        private string $userClass,
    ) {
        $this->manager = $manager;
        /** @phpstan-ignore-next-line  */
        if (!is_subclass_of($this->userClass, IdentityInterface::class, true)) {
            throw new InvalidConfigException("userClass must implement IdentityInterface");
        }
    }

    final protected function getGlobalAuthorizable(): Authorizable
    {
        return new Authorizable($this->globalId, $this->globalName);
    }

    /**
     * @var array<string, IdentityInterface>
     */
    private array $userCache = [];

    final protected function getUser(?string $id): ?IdentityInterface
    {
        if (!isset($id)) {
            return null;
        }
        // We cache positive lookups only.
        if (!isset($this->userCache[$id])) {
            $user = $this->userClass::findIdentity($id);
            if (isset($user)) {
                $this->userCache[$id] = $user;
            }
        }
        return $this->userCache[$id] ?? null;
    }

    /**
     * @param int|string|null $userId
     * @param string $permissionName
     * @param array<mixed> $params
     */
    public function checkAccess($userId, $permissionName, $params = []): bool
    {
        $user = $this->getUser(isset($userId) ? (string) $userId : null) ?? new Authorizable($this->guestId, $this->guestName);

        if (isset($params[self::TARGET_PARAM]) && !is_object($params[self::TARGET_PARAM])) {
            throw new InvalidArgumentException('Target, if passed, must be an object');
        }

        $target = $params[self::TARGET_PARAM] ?? $this->getGlobalAuthorizable();
        /** @phpstan-ignore-next-line */
        return $this->manager->check($user, $target, $permissionName);
    }
}
