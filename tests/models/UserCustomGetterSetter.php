<?php

use MartynBiz\Mongo\Mongo;

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
