# DynamicModel для Yii1

Класс, который позволяет создавать динамичные модели.

Пример использования:


```php
     $model = new DynamicModel(
         ['name', 'email'],
         [
            ['name, email', 'length', 'max' => 50],
         ]
    );
    if ($model->hasErrors()) {
         // ...
    } else {
         // ...
    }
```