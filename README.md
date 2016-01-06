##Installation

Install with composer: 

```
"martynbiz/php-validator": "dev-master"
```

After that, create a new instance of the class.

```php
$validator = MartynBiz\Validator::getInstance();
```

### Chaining

The set() method instructs the object that this is a new value to validate. Additional methods are chained from this. Below shows an example of the object checking a value is not empty, then checking it is a valid email address.

```php
$email = '';
$validator->set($email)
  ->isNotEmpty('Email address is blank')
  ->isEmail('Invalid email address');
```

To fetch the errors  from a validation check, use getErrors():

```php
$errors = $validator->getErrors();
```

This will return an array of containing the error that occured (Email address is blank). Please note that although the isEmail method was called too, because it has already gathered an error it does not record another. Remember this when ordering your methods in the chain.

It is also possible to do this in one go, and return errors:

```php
$email = '';
$errors = $validator->set($email)
  ->isNotEmpty('Email address is blank')
  ->isEmail('Invalid email address')
  ->getErrors();
```

### Other methods for validating

The above example shows how to validate an email address but the following methods can be used to numeric, date and time stings too. Below is the full list of validation methods available.

Not empty or only whitespaces

```php
$validator->set($value)
  ->isNotEmpty('Value must not be blank');
  
$validator->set($value)
  ->isEmail('Email address must be valid');
  
$validator->set($value)
  ->isLetters('Value must be letters');
  
$validator->set($value)
  ->isNumber('Value must be a number');
  
$validator->set($value)
  ->isPositiveNumber('Value must be a positive number');
  
$validator->set($value)
  ->isNotPositiveNumber('Value must not be a positive number, negatives and zeros OK');
  
$validator->set($value)
  ->isNegativeNumber('Value must be a negative number');
  
$validator->set($value)
  ->isNotNegativeNumber('Value must not be a negative number, positives and zeros OK');
  
$validator->set($value)
  ->isDateTime('Value must be date/time format yyyy-mm-dd hh:mm:ss');
  
$validator->set($value)
  ->isDate('Value must be date format yyyy-mm-dd');
  
$validator->set($value)
  ->isTime('Value must be time hh:mm:ss');
```

### Passing error code

Perhaps you want to return a numeric value too so your app can identify the error. You can pass a numeric value with the error message and it will be part of the errors array.

```php
$validator->set($value)
  ->isNotEmpty('Value must not be blank', 2020);
```

### Other error logging

You can use the logError() method too to log a custom error:

```php
$validator->logError('Could not load api', 5000);
```
