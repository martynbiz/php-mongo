<?php

use MartynBiz\Mongo\Mongo;

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
		'friends',
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
