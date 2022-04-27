<?php

declare(strict_types=1);

namespace SamIT\Yii2\abac;

use SamIT\abac\interfaces\Authorizable;
use SamIT\abac\interfaces\Grant;
use SamIT\abac\interfaces\PermissionRepository;
use SamIT\abac\values\Authorizable as AuthorizableObject;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class ActiveRecordRepository
 * Store permissions in an ActiveRecord model
 */
class ActiveRecordRepository implements PermissionRepository
{
    public const SOURCE_ID = 'source_id';
    public const SOURCE_NAME = 'source_name';
    public const TARGET_ID = 'target_id';
    public const TARGET_NAME = 'target_name';
    public const PERMISSION = 'permission';

    /**
     * @var class-string<ActiveRecord> Class of the AR model
     */
    private string $modelClass;

    /**
     * @var array<string> Maps grant properties to AR attributes
     */
    private array $attributeMap;

    /**
     * PermissionRepository constructor.
     * @param class-string<ActiveRecord> $modelClass Class name of the model
     * @param array<string, string> $attributeMap Map mapping grant properties to model attributes
     */
    public function __construct(
        string $modelClass,
        array $attributeMap = []
    ) {
        $this->modelClass = $modelClass;

        $this->attributeMap = array_merge([
            self::SOURCE_ID => self::SOURCE_ID,
            self::SOURCE_NAME => self::SOURCE_NAME,
            self::TARGET_ID => self::TARGET_ID,
            self::TARGET_NAME => self::TARGET_NAME,
            self::PERMISSION => self::PERMISSION
        ], $attributeMap);
    }

    public function grant(Grant $grant): void
    {
        try {
            $source = $grant->getSource();
            $target = $grant->getTarget();
            $permission = $grant->getPermission();

            /**
             * @psalm-suppress UnsafeInstantiation
             */
            $permissionModel = new $this->modelClass();
            $permissionModel->setAttributes([
                $this->attributeMap[self::SOURCE_ID] => $source->getId(),
                $this->attributeMap[self::SOURCE_NAME] => $source->getAuthName(),
                $this->attributeMap[self::TARGET_ID] => $target->getId(),
                $this->attributeMap[self::TARGET_NAME] => $target->getAuthName(),
                $this->attributeMap[self::PERMISSION] => $permission
            ], false);
            if (!$permissionModel->save()) {
                throw new \RuntimeException('Failed to save permission due to validation errors');
            }
        } catch (\Throwable $t) {
            // Check if it already exists.
            if (!$this->check($grant)) {
                throw new \RuntimeException('Failed to save permission', 0, $t);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function revoke(Grant $grant): void
    {
        $source = $grant->getSource();
        $target = $grant->getTarget();
        $permission = $grant->getPermission();

        $this->modelClass::deleteAll([
            $this->attributeMap[self::SOURCE_ID] => $source->getId(),
            $this->attributeMap[self::SOURCE_NAME] => $source->getAuthName(),
            $this->attributeMap[self::TARGET_ID] => $target->getId(),
            $this->attributeMap[self::TARGET_NAME] => $target->getAuthName(),
            $this->attributeMap[self::PERMISSION] => $permission
        ]);
    }

    /**
     * @inheritDoc
     */
    public function check(Grant $grant): bool
    {
        $source = $grant->getSource();
        $target = $grant->getTarget();
        $permission = $grant->getPermission();

        return $this->modelClass::find()
            ->andWhere([
                $this->attributeMap[self::SOURCE_ID] => $source->getId(),
                $this->attributeMap[self::SOURCE_NAME] => $source->getAuthName(),
                $this->attributeMap[self::TARGET_ID] => $target->getId(),
                $this->attributeMap[self::TARGET_NAME] => $target->getAuthName(),
                $this->attributeMap[self::PERMISSION] => $permission
            ])
            ->exists();
    }

    public function search(?Authorizable $source, ?Authorizable $target, ?string $permission): iterable
    {
        $query = $this->modelClass::find();

        if (isset($source)) {
            $query->andFilterWhere([
                $this->attributeMap[self::SOURCE_ID] => $source->getId(),
                $this->attributeMap[self::SOURCE_NAME] => $source->getAuthName(),
            ]);
        }

        if (isset($target)) {
            $query->andFilterWhere([
                $this->attributeMap[self::TARGET_ID] => $target->getId(),
                $this->attributeMap[self::TARGET_NAME] => $target->getAuthName(),
            ]);
        }

        $query->andFilterWhere([$this->attributeMap[self::PERMISSION] => $permission]);

        /** @var ActiveRecord $permissionRecord */
        foreach ($query->each() as $permissionRecord) {
            $source = new AuthorizableObject(
                /** @phpstan-ignore-next-line */
                (string) $permissionRecord->getAttribute($this->attributeMap[self::SOURCE_ID]),
                /** @phpstan-ignore-next-line */
                (string) $permissionRecord->getAttribute($this->attributeMap[self::SOURCE_NAME])
            );

            $target = new AuthorizableObject(
                /** @phpstan-ignore-next-line */
                (string) $permissionRecord->getAttribute($this->attributeMap[self::TARGET_ID]),
                /** @phpstan-ignore-next-line */
                (string) $permissionRecord->getAttribute($this->attributeMap[self::TARGET_NAME])
            );

            yield new \SamIT\abac\values\Grant(
                $source,
                $target,
                /** @phpstan-ignore-next-line */
                (string) $permissionRecord->getAttribute($this->attributeMap[self::PERMISSION])
            );
        }
    }
}
