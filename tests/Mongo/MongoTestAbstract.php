<?php

// TODO test set can accept an array too

// use MartynBiz\Mongo;
use MartynBiz\Mongo\Connection;

abstract class MongoTestAbstract extends TestCase
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
}
