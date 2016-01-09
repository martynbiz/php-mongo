## Installation ##

Install with composer:

```
"massaman/php-mongo": "*"
```

After that, setup the connection.

```php
Connection::getInstance()->setOptions(array(
    'dbname' => 'mydb',
    'classmap' => array(
        'users' => '\\App\\Model\\User',
    ),
));
```

## Getting started ##

Create models by extending the Mongo class:

<?php

use Massaman\Mongo;

class User extends mongo
{
    protected $collection = 'users';
}

Find by mongo query

$users = User::find(array(
    'status' => 1,
));

Find one by mongo query

$user = User::findOne(array(
    'email' => 'info@examle.com',
));

Setting variables

$user->name = 'Jim';
$user->save();

$user->save(array(
    'name' => 'Jim',
));

$user->delete();

## Todo ##

* test: methods called statically should also work
* save
* sequences
* mass assignment
