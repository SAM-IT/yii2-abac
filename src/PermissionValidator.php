<?php
declare(strict_types=1);

namespace SamIT\Yii2\abac;

use SamIT\abac\interfaces\Authorizable;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\validators\Validator;
use function iter\all;

class PermissionValidator extends Validator
{

    private string $field = 'id';
    public bool $allowArray = false;

    public ActiveQueryInterface $query;
    public \SamIT\abac\interfaces\AccessChecker $accessChecker;
    public Authorizable $source;
    public string $permissionName = 'admin';

    public function __construct($config = [])
    {
        parent::__construct($config);
        if (!isset($this->query)) {
            throw new InvalidConfigException('Query is required');
        }

        if (!isset($this->user)) {
            throw new InvalidConfigException('User is required');
        }
    }

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

        if (!all(fn(Authorizable $model) => $this->accessChecker->check($this->source, $model, $this->permissionName), $models)) {
            return ["You do not have the appropriate permissions", []];
        }

        return null;
    }
}
