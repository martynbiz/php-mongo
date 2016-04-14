<?php

use MartynBiz\Mongo\Mongo;

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
