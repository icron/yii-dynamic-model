# DynamicModel for Yii1

[![Latest Version](https://img.shields.io/github/tag/icron/yii-dynamic-model.svg?style=flat-square&label=release)](https://github.com/icron/yii-dynamic-model/tags)
[![Software License](https://img.shields.io/badge/license-BSD-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/icron/yii-dynamic-model.svg?style=flat-square)](https://packagist.org/packages/icron/yii-dynamic-model)

DynamicModel is a model class primarily used to support ad hoc data validation.
The typical usage of DynamicModel is as follows,
  
```php
  $model = new DynamicModel(
      ['name', 'email'],
      [
         ['name, email', 'length', 'max' => 50],
      ]
  );
  if ($model->hasErrors()) {
      // validation fails
  } else {
      // validation succeeds
  }
```