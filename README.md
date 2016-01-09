## Installation ##

Install with composer:

```
"massaman/php-mongo": "*"
```

After that, setup the connection.

```php
Connection::getInstance()->setOptions(array(
    'db' => 'mydb',
    'classmap' => array(
        'users' => '\\App\\Model\\User',
    ),
));
```

## Getting started ##

Create models by extending the Mongo class, be sure to define $collection and $whitelist:

```php
<?php

use Massaman\Mongo;

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

Saving

```php
$user->name = 'Jim';
$user->save();
```

```php
$user->save(array(
    'name' => 'Jim',
));
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

*
