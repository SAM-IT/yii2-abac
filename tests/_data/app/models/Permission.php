<?php
declare(strict_types=1);

namespace app\models;


use yii\db\ActiveRecord;
use yii\validators\RangeValidator;
use function GuzzleHttp\Psr7\parse_header;

/**
 * Class Permission
 * @package app\models
 * @property string $source_id
 * @property string $source_name
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
                ['source_name'], RangeValidator::class, 'range' => ['a', 'b']
            ]
        ];
    }


}