# DynamicModel for Yii1

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