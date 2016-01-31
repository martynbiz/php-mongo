<?php

use MartynBiz\Mongo\MongoIterator;
use MartynBiz\Mongo;

/**
 * Just a basic one to test toArray
 */
class User extends Mongo
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
	);
}

class MongoIteratorTest extends PHPUnit_Framework_TestCase
{
	public function setup()
	{

	}

	public function testInstantiation()
    {
		$data = new MongoIterator();

		$this->assertTrue($data instanceof MongoIterator);
    }

	public function testIterationValueInForeach()
    {
		$array = array(1,2,3,4,5);
		$data = new MongoIterator($array);

		foreach ($data as $i => $value) {
			$this->assertEquals($array[$i], $value);
		}
    }

	public function testIterationValueByOffset()
    {
		$array = array(1,2,3,4,5);
		$data = new MongoIterator($array);

		foreach ($data as $i => $value) {
			$this->assertEquals($array[$i], $data[$i]);
		}
    }

	public function testCount()
    {
		$array = array(1,2,3,4,5);
		$data = new MongoIterator($array);

		$this->assertEquals(count($array), count($data));
    }

	public function testToArray()
    {
		$userData = array(
			'name' => 'Martyn',
			'email' => 'martyn@example.com'
		);

		$expected = array(
			$userData,
		);

		$user = new User($userData);
		$dataset = new MongoIterator(array($user));


		$this->assertEquals($expected, $dataset->toArray());
    }
}
