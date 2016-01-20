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

## Getting started ##

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
