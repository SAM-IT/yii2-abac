<?php

declare(strict_types=1);

namespace testapp\models;

use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Class User
 * @property string $name
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function findIdentity($id)
    {
        return self::findOne(['id' => $id]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException();
    }

    public function getId(): string|int
    {
        return 15;
    }

    public function getAuthKey(): never
    {
        throw new NotSupportedException();
    }

    public function validateAuthKey($authKey): never
    {
        throw new NotSupportedException();
    }
}
