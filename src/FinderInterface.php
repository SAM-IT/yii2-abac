<?php

declare(strict_types=1);

namespace SamIT\Yii2\abac;

interface FinderInterface
{
    /**
     * @param list<mixed> $values
     * @return iterable<object>
     */
    public function find(array $values): iterable;
}
