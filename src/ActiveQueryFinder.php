<?php

declare(strict_types=1);

namespace SamIT\Yii2\abac;

use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecordInterface;

class ActiveQueryFinder implements FinderInterface
{
    public function __construct(
        private readonly ActiveQueryInterface $query,
        private readonly string $field = 'id'
    ) {
    }

    public function find(array $values): iterable
    {
        $clone = clone $this->query;
        /** @var ActiveRecordInterface $item */
        foreach ($clone->asArray(false)->andWhere([$this->field => $values])->all() as $item) {
            yield $item;
        }
    }
}
