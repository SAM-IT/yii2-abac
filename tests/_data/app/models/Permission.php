<?php
declare(strict_types=1);

namespace testapp\models;

use yii\db\ActiveRecord;
use yii\validators\RangeValidator;

/**
 * Class Permission
 * @package testapp\models
 * @property string $source_id
 * @property string $source
 * @property string $target_id
 * @property string $target_name
 */
class Permission extends ActiveRecord
{
    public function rules()
    {
        return [
            [
                // Arbitrarily restrict source_name to some values
                ['source'], RangeValidator::class, 'range' => ['a', 'b']
            ]
        ];
    }
}
