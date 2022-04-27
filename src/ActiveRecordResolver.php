<?php

declare(strict_types=1);

namespace SamIT\Yii2\abac;

use SamIT\abac\exceptions\UnresolvableException;
use SamIT\abac\interfaces\Authorizable;
use SamIT\abac\interfaces\Resolver;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;

class ActiveRecordResolver implements Resolver
{
    public function fromSubject(object $object): Authorizable
    {
        if ($object instanceof ActiveRecord && !$object->getIsNewRecord()) {
            $primaryKey = $object->getPrimaryKey(false);
            if (!is_scalar($primaryKey)) {
                throw UnresolvableException::forSubject($object);
            }
            $name = get_class($object);
            return new \SamIT\abac\values\Authorizable((string) $primaryKey, $name);
        } elseif ($object instanceof Authorizable) {
            return $object;
        }
        throw UnresolvableException::forSubject($object);
    }

    public function toSubject(Authorizable $authorizable): object
    {
        $name = $authorizable->getAuthName();
        if (class_exists($name) && is_subclass_of($name, ActiveRecordInterface::class, true)) {
            $result = $name::findOne(explode('|', $authorizable->getId()));
            if (isset($result)) {
                return $result;
            }
        }
        throw UnresolvableException::forSubject($authorizable);
    }
}
