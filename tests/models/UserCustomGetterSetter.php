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
		return md5($value);
	}

	/**
	 * Custom getter
	 */
	public function setLastName($value)
	{
		return md5(md5($value));
	}
}
