<?php

namespace Massaman;

/**
*
* Designed for string validation (e.g. form submissions). Allows a single value to be checked through a series of conditions through method chaining.
*
* @category Mongo
* @package Mongo
* @author Martyn Bissett <martynbissett@yahoo.co.uk>
* @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
* @version 1.0
**/
abstract class Mongo {

	/**
	* @var MongoClient
	*/
	private $client;

	/**
	 * @param MongoClient $client
	 * @return void
	 */
	public function __construct(MongoClient $client)
	{
		$this->client = $client;
	}

	/**
	 * @param array $query
	 * @return array $options
	 * @return array Array of Document objects
	 */
	public function find($query=array(), $options=array())
	{

	}
}

?>
