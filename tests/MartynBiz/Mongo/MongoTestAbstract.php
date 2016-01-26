<?php

// TODO test set can accept an array too

// use MartynBiz\Mongo;
use MartynBiz\Mongo\Connection;

abstract class MongoTestAbstract extends PHPUnit_Framework_TestCase
{
	/**
	 * @var User
	 */
	protected $user;

	public function setup()
	{
		$this->connectionMock = $this->getMockBuilder('MartynBiz\\Mongo\\Connection')
			->disableOriginalConstructor()
			->getMock();

		// mock method to return mock collection
		$this->connectionMock
		 	->method('getNextSequence')
			->willReturn(1);

		// reset Connection as it's being used across multiple tests (unit, int)
		Connection::getInstance()->resetInstance();

		// swap the instance as it's a singleton
		Connection::setInstance($this->connectionMock);

		$this->user = new UserUnit();
	}

	protected function getUserData($data=array(), $withId=true)
	{
		// set mongoid
		if ($withId) $data = array_merge(array(
				'_id' => $this->getUniqueMongoId(),
			), $data);

		return array_merge(array(
			'name' => 'Martyn Bissett',
			'first_name' => 'Martyn',
			'last_name' => 'Bissett',
			'email' => 'martyn@example.com',
		), $data);
	}

	protected function getTagData($data=array(), $withId=true)
	{
		// set mongoid
		if ($withId) $data = array_merge(array(
				'_id' => $this->getUniqueMongoId(),
			), $data);

		return  array_merge(array(
			'name' => 'Cooking',
			'slug' => 'cooking',
		), $data);
	}

	// TODO have tests using this instead of setting mongoid from get*Data methods
	protected function getUniqueMongoId()
	{
		return new MongoId();
	}
}
