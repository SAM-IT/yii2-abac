<?php
declare(strict_types=1);

namespace tests;
use testapp\models\Car;
use SamIT\abac\values\Authorizable;
use SamIT\Yii2\abac\ActiveRecordResolver;
use tests\FunctionalTester;

class ActiveRecordResolverCest
{
    public function _before(FunctionalTester $I)
    {
    }

    // tests
    public function testToSubject(FunctionalTester $I)
    {
        $resolver = new ActiveRecordResolver();


        \Yii::$app->db->createCommand()->createTable(Car::tableName(), [
            'id' => \Yii::$app->db->schema->createColumnSchemaBuilder('pk')
        ])->execute();

        $model = new Car();
        $I->assertNull($resolver->fromSubject($model));

        $model->save();

        $authorizable = $resolver->fromSubject($model);

        $I->assertInstanceOf(Authorizable::class, $authorizable);
        $I->assertSame((string) $model->primaryKey, $authorizable->getId());


        $car = $resolver->toSubject($authorizable);

        $I->assertInstanceOf(Car::class, $car);
        $I->assertSame($model->primaryKey, $car->primaryKey);
    }

    public function testNonExistentClass(FunctionalTester $I)
    {
        $authorizable = new Authorizable('13', 'abc');
        $resolver = new ActiveRecordResolver();
        $I->assertNull($resolver->toSubject($authorizable));
    }
    public function testNonExistentRecord(FunctionalTester $I)
    {
        $resolver = new ActiveRecordResolver();
        \Yii::$app->db->createCommand()->createTable(Car::tableName(), [
            'id' => \Yii::$app->db->schema->createColumnSchemaBuilder('pk')
        ])->execute();
        $model = new Car();
        $model->save();
        $authorizable = new Authorizable('-1314', $resolver->fromSubject($model)->getAuthName());

        $I->assertNull($resolver->toSubject($authorizable));
    }

}
