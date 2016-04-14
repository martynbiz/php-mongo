<?php

use MartynBiz\Mongo\Mongo;

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
