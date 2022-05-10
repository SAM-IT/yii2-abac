<?php

declare(strict_types=1);

namespace SamIT\Yii2\abac;

use Closure;
use SamIT\abac\interfaces\AccessChecker as AccessChecker;
use SamIT\abac\interfaces\Authorizable;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\validators\Validator;
use yii\web\User;
use function iter\all;

/**
 * Validates permissions on one or more related objects in the database.
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PermissionValidator extends Validator
{
    public bool $allowArray = false;

    /**
     * @var string name of the required permission
     */
    public string $permissionName = 'admin';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private FinderInterface $finder,
        private AccessChecker $accessChecker,
        private object $source,
        array $config = []
    ) {
        parent::__construct($config);
    }

    /**
     * @param mixed $value
     * @return array{0: string, 1:array<string, string>}|null
     */
    protected function validateValue($value): ?array
    {
        if (!$this->allowArray && is_array($value)) {
            return ["Multiple values are not allowed", []];
        }
        $arrayOfValues = is_array($value) ? array_values($value) : [$value];

        $modelIterator = $this->finder->find($arrayOfValues);

        if (!all(fn (object $model): bool => $this->accessChecker->check($this->source, $model, $this->permissionName), $modelIterator)) {
            return ["You do not have the appropriate permissions", []];
        }

        return null;
    }

    /**
     * Static constructor for simpler syntax in models
     * @param list<string> $attributes
     */
    public static function create(
        array $attributes,
        ActiveQueryInterface $query,
        string $accessCheckerComponent = 'authManager',
        string $userComponent = 'user',
        string $field = 'id'
    ): self {
        /**
         * @psalm-assert AccessChecker $accessChecker
         */
        $accessChecker = \Yii::$app->get($accessCheckerComponent);
        if (!$accessChecker instanceof AccessChecker) {
            throw new InvalidConfigException("Component {$accessCheckerComponent} is not an AccessChecker");
        }
        /**
         * @psalm-assert \User $user
         */
        $user = \Yii::$app->get($userComponent);
        if (!$user instanceof User) {
            throw new InvalidConfigException("Component {$userComponent} is not an instance of User");
        }
        $identity = $user->identity;
        /** @psalm-assert object $identity */
        if ($identity === null) {
            throw new InvalidConfigException("User has no identity");
        }


        $result = new self(new ActiveQueryFinder($query, $field), $accessChecker, $identity);
        $result->attributes = $attributes;
        return $result;
    }
}
