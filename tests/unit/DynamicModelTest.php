<?php

/**
 * Class DynamicModelTest
 * @group models
 */
class DynamicModelTest extends \CTestCase
{
    public function testInit()
    {
        $model = new DynamicModel(['name', 'email']);
        $this->assertEquals(2, count($model->attributes));
        $this->assertTrue(array_key_exists('name', $model->attributes));
        $this->assertTrue(array_key_exists('email', $model->attributes));
    }

    /**
     * @dataProvider providerValidate
     * @param $data
     * @param $rules
     * @param $isValid
     */
    public function testValidate($data, $rules, $isValid)
    {
        $model = new DynamicModel($data, $rules);
        $this->assertEquals($isValid, $model->validate());
    }

    public function providerValidate()
    {
        return [
            [['name' => 'Length more than 10'], [['name', 'length', 'max' => 10]], false],
            [['name' => 'Length less than 100'], [['name', 'length', 'max' => 100]], true],
        ];
    }

    public function testGetAttributesFromRules()
    {
        $rules = [
            ['name, email', 'length', 'max' => 10],
            ['city', 'length', 'max' => 10, 'on' => 'registration'],
            ['code', 'length', 'max' => 10, 'except' => 'registration'],
        ];

        $attributes = DynamicModel::getAttributesFromRules($rules);
        sort($attributes);
        $this->assertTrue(['code', 'email', 'name'] === $attributes);

        $attributes = DynamicModel::getAttributesFromRules($rules, 'registration');
        sort($attributes);
        $this->assertTrue(['city', 'email', 'name'] === $attributes);
    }
}
