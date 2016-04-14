<?php

use MartynBiz\Mongo\Mongo;

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
