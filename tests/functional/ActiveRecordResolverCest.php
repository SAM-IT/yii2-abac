<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Assert;
use SamIT\abac\exceptions\UnresolvableException;
use SamIT\abac\values\Authorizable;
use SamIT\Yii2\abac\ActiveRecordResolver;
use testapp\models\Car;

final class ActiveRecordResolverCest
{
    public function testToSubjectWithUnsavedModel(FunctionalTester $I): void
    {
        $resolver = new ActiveRecordResolver();


        \Yii::$app->db->createCommand()->createTable(Car::tableName(), [
            'id' => \Yii::$app->db->schema->createColumnSchemaBuilder('pk')
        ])->execute();

        $model = new Car();

        $I->expectThrowable(UnresolvableException::class, fn () => $resolver->fromSubject($model));
    }


    public function testToSubject(FunctionalTester $I): void
    {
        $resolver = new ActiveRecordResolver();


        \Yii::$app->db->createCommand()->createTable(Car::tableName(), [
            'id' => \Yii::$app->db->schema->createColumnSchemaBuilder('pk')
        ])->execute();

        $model = new Car();

        $model->save();

        $authorizable = $resolver->fromSubject($model);

        $I->assertInstanceOf(Authorizable::class, $authorizable);
        $I->assertSame((string) $model->id, $authorizable->getId());


        $car = $resolver->toSubject($authorizable);

        Assert::assertInstanceOf(Car::class, $car);
        Assert::assertSame($model->id, $car->id);
    }

    public function testNonExistentClass(FunctionalTester $I): void
    {
        $authorizable = new Authorizable('13', 'abc');
        $resolver = new ActiveRecordResolver();
        $I->expectThrowable(UnresolvableException::class, fn () => $resolver->toSubject($authorizable));
    }

    public function testNonExistentRecord(FunctionalTester $I): void
    {
        $resolver = new ActiveRecordResolver();
        \Yii::$app->db->createCommand()->createTable(Car::tableName(), [
            'id' => \Yii::$app->db->schema->createColumnSchemaBuilder('pk')
        ])->execute();
        $model = new Car();
        $model->save();
        $authorizable = new Authorizable('-1314', $resolver->fromSubject($model)->getAuthName());

        $I->expectThrowable(UnresolvableException::class, fn () => $resolver->toSubject($authorizable));
    }
}
