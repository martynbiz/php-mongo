<?php

use MartynBiz\Mongo\Mongo;

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
