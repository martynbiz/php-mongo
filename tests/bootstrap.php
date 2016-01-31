<?php

require_once realpath(__DIR__ . '/../vendor/autoload.php');

use MartynBiz\Mongo;
// use MartynBiz\Mongo\Connection;

/**
 * UserUnit class to unit test abstract Mongo methods
 */
class UserUnit extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'users';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'name',
		'first_name',
		'last_name',
		'email',
		'friend',
		'article',
		'articles',
	);

	/**
	 * Custom method available to each instance
	 */
	public function doSomething()
	{
		return 'something';
	}
}

/**
 * Added this to properly test toArray with various model types
 */
class ArticleUnit extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'articles';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'title',
		'description',
	);
}

/**
 * See how Mongo handles a MongoIterator object being passed
 */
class TagUnit extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'tags';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'name',
		'slug',
	);
}

/**
 * UserUnit class to unit test abstract Mongo methods
 */
class UserValidator extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'users';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'name',
		'first_name',
		'last_name',
		'email',
		'friend',
	);

	/**
	 * Custom method available to each instance
	 */
	public function validate()
	{
		$this->resetErrors();

		if (empty($this->data['name'])) {
            $this->setError('name_missing_error');
        }

		return empty( $this->getErrors() );
	}
}

/**
 * This class has no whitelist - it shouldn't save
 */
class UserWhitelistEmpty extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'users';
}

/**
 * This class has no whitelist - it shouldn't instantiate
 */
class UserCollectionUndefined extends Mongo
{
	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'name',
		'email'
	);
}

/**
 * UserUnit class to unit test abstract Mongo methods
 */
class UserCustomGetterSetter extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'users';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'first_name',
		'last_name',
	);

	/**
	 * Custom getter
	 */
	public function getFirstName($value)
	{
		return strtolower($value);
	}
}

/**
 * User class to test abstract Mongo methods
 */
class UserIntegrated extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'users';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'name',
		'email',
		'friends',
		'compadres'
	);
}

/**
 * Best we don't use the same class for dbrefs conversions
 */
class ArticleIntegrated extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'articles';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
		'title',
		'slug',
		'author',
	);
}
