<?php

use MartynBiz\Mongo\Mongo;

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
