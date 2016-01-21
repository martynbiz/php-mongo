## Installation ##

Install with composer:

```
"martynbiz/php-mongo": "dev-master"
```

After that, setup the connection.

```php
Connection::getInstance()->setOptions(array(
    'db' => 'mydb',
    'username' => 'myuser',
    'password' => '89dD7HH7di!89',
    'classmap' => array(
        'users' => '\\App\\Model\\User',
    ),
));
```

## Usage ##

Create models by extending the Mongo class, be sure to define $collection and $whitelist:

```php
<?php

use MartynBiz\Mongo;

class User extends mongo
{
    // collection this model refers to
    protected $collection = 'users';

    // define on the fields that can be saved
    protected $whitelist = array(
        'name',
        'email',
        'username',
        'password',
    );
}
```

Find by mongo query

```php
$users = User::find(array(
    'status' => 1,
));
```

Find one by mongo query

```php
$user = User::findOne(array(
    'email' => 'info@examle.com',
));
```

Inserting

Save method is used on an instantiated object of the model class. It can be called
after assigning values to properties, or by passing name/values as arguments.
Note: when passing name/values, values will be whitelisted

```php
$user->name = 'Jim';
$user->save();
```

```php
$user->save(array(
    'name' => 'Jim',
));
```

Create method doesn't require an instance, by will accept name\values upon which it
will insert into the collection. It will return an instance of the created document.
Note: when passing name/values, values will be whitelisted

```php
$user = User::create(array(
    'name' => 'Jim',
));
```

Factory method doesn't actually insert, but will generate an instance with values.
It can then be altered and inserted with it's save method:

```php
$user = User::factory(array(
    'name' => 'Jim',
));
$user->save();
```

Although having multiple methods may seem a little much, it does give the option to
keep you code tidy and more flexible for mocking methods during testing.


### Validation ###

Before saving to the database, the validate method is called. If it returns false,
the data will not be saved.

```php
class User extends mongo
{
    .
    .
    .
    public function validate()
    {
        if (empty($this->data['name'])) {
            $this->setError('Name is missing.');
        }

        return empty( $this->getErrors() ); // true if none
    }
}
```

Deleting

```php
$user->delete();
```

Convert to array

```php
$user->toArray();
```

## Todo ##

* call Model::findOne(); without instance
