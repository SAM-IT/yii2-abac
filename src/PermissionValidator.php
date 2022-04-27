<?php

declare(strict_types=1);

namespace SamIT\Yii2\abac;

use SamIT\abac\interfaces\AccessChecker as AccessChecker;
use SamIT\abac\interfaces\Authorizable;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\validators\Validator;
use function iter\all;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PermissionValidator extends Validator
{
    private string $field = 'id';
    public bool $allowArray = false;


    public string $permissionName = 'admin';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private ActiveQueryInterface $query,
        private AccessChecker $accessChecker,
        private Authorizable $source,
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
        $arrayOfValues = (array)$value;

        $query = clone $this->query;
        $models = $query->andWhere([$this->field => $arrayOfValues])->all();

        if (count($models) < count($arrayOfValues)) {
            return ["One or more models not found", []];
        }

        if (!all(fn (Authorizable $model) => $this->accessChecker->check($this->source, $model, $this->permissionName), $models)) {
            return ["You do not have the appropriate permissions", []];
        }

        return null;
    }
}
