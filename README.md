## Installation ##

### Install with composer ###

```
"martynbiz/php-mongo": "dev-master"
```

## Getting started ##

### Create connection ###

```php
Connection::getInstance()->init(array(
    'db' => 'mydb',
    'username' => 'myuser',
    'password' => '89dD7HH7di!89',
    'classmap' => array(
        'users' => '\\App\\Model\\User',
    ),
));
```

### Create multiple connections ###

Useful if you need to connect to multiple databases:

```php
Connection::getInstance('conn1')->init(array(
    ...
));

Connection::getInstance('conn2')->init(array(
    ...
));
```

### Create model ###

Create models by extending the Mongo class, be sure to define $collection and $whitelist:

```php
<?php

use MartynBiz\Mongo;

class User extends mongo
{
    // optional - if using multiple connections/databases
    protected static $conn = 'conn1';

    // required - collection this model refers to
    protected static $collection = 'users';

    // required - define on the fields that can be saved
    protected static $whitelist = array(
        'name',
        'email',
        'username',
        'password',
    );
}
```

## Queries ##

### Find by mongo query ###

```php
// statically
$users = User::find(array(
    'status' => 1,
));

// dynamically
$col = new User();
$users = $col->find(array(
    'status' => 1,
));
```

### Find one by mongo query ###

```php
// statically
$user = User::findOne(array(
    'email' => 'info@examle.com',
));

// dynamically
$col = new User();
$user = $col->findOne(array(
    'email' => 'info@examle.com',
));
```

### Finding by object ###

```php
$friend = User::findOne(array(
    //...
));

$user = User::find(array(
    'friend' => $friend,
));
```

### Getting and setting values ###

```php
// setting

// on instantiation -- will be filtered against $whitelist
$article = new Article(array(
    'title' => 'My title',
));

$author = User::findOne(array(
    //...
));

// single value properties -- no param filtering
$article->status = 2;
$article->author = $author;

// AND/OR set() method, with Mongo instance -- suited for unit testing, no param filtering
$user = User::findOne(array(
    //...
));
$article->set('author', $author);

// set value as query result (will be stored as an array of dbrefs)
$tags = Tag::find(array(
    //...
));
$article->tags = $tags;

// lastly, params can be passed with save() -- will be filtered against $whitelist
$article->save(array(
    'content' => $text,
))
```

```php
// getting

$article = Article::findOne(array(
    //...
));

// single value properties -- no param filtering
echo $article->status;
echo $article->author->name;
echo $article->tags[0]->name;
echo $article->get('author');
```


### Inserting ###

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
// statically
$user = User::create(array(
    'name' => 'Jim',
));

// dynamically (e.g. service locator pattern, or DI)
$col = new User();
$user = $col->create(array(
    'name' => 'Jim',
));
```

Factory method doesn't actually insert, but will generate an instance with values.
It can then be altered and inserted with it's save method:

```php
// statically
$user = User::factory(array(
    'name' => 'Jim',
));

// dynamically
$col = new User();
$user = $col->factory(array(
    'name' => 'Jim',
));

$user->save();
```

Although having multiple methods may seem a little much, it does give the option to
keep you code tidy and more flexible for mocking methods during testing.

### Push ###

Note: push with $each is the default behaviour here, although this can be overridden with
'each'=>false in the options array (see below)

```php

// push one object, will convert to DBRef
$user->push(array(
    'friends' => $friend,
));

// push multi object, will convert to DBRef
$user->push(array(
    'friends' => array(
        $friend,
        $friend2,
    ),
));

// push MongoIterator object (from find() call)
$user->push(array(
    'friends' => $friends,
));

// push multiple properties at once
$user->push(array(
    'friends' => $friends,
    'enemies' => $enemies,
));

// push without $each setting, will push the whole array as a single element
$user->push(array(
    'friends' => array(
        $friend,
        $friend2,
    ),
), array('each' => false));
```

### Deleting ###

An instance can delete itself from the database with the delete method:

```php
$user->delete();
```

To delete multiple documents from a collection by query, use the remove method:

```php
User::remove(array(
    'type' => 'boring',
), $options);
```


### Convert to array ###

```php
$user->toArray(3); // convert nested 3 deep to array (optional)
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
        $this->resetErrors();

        if (empty($this->data['name'])) {
            $this->setError('Name is missing.');
        }

        return empty( $this->getErrors() ); // true if none
    }
}
```

The following example uses [martynbiz/php-validator](https://github.com/martynbiz/php-validator) library here.

```php
<?php

use MartynBiz\Validator;

class User extends mongo
{
    .
    .
    .
    public function validate()
    {
        $this->resetErrors();

        $validator = new Validator($this->data);

        $validator->check('name')
            ->isNotEmpty('Name is missing');

        $validator->check('email')
            ->isNotEmpty('Email address is missing')
            ->isEmail('Invalid email address');

        $message = 'Password must contain upper and lower case characters, and have more than 8 characters';
        $validator->check('password')
            ->isNotEmpty($message)
            ->hasLowerCase($message)
            ->hasUpperCase($message)
            ->hasNumber($message)
            ->isMinimumLength($message, 8);

        // update the model's errors with the validators
        $this->setError( $validator->getErrors() );

        return empty($this->getErrors());
    }
}
```

### Custom Getter/ Setter ###

To automatically convert values when getting, you can define custom methods
to automatically convert the value of a property. This may be useful when formatting
strings such as dates to human readable.

```php
<?php

use MartynBiz\Mongo;

class User extends Mongo
{
    .
    .
    .

    public function getCreatedAt($value)
    {
        return date('Y-M-d h:i:s', $this->data['created_at']->sec);
    }

    public function setPassword($value)
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}
```


TODO
* more testing of multiple connections
* nested properties (e.g. model.name)
*

tests
* move test models into models/
