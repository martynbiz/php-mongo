<?php

use MartynBiz\Mongo\Mongo;

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
