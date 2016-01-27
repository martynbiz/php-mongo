<?php

// TODO test set can accept an array too

require_once 'MongoTestAbstract.php';

class MongoLoadTest extends MongoTestAbstract
{
	public function testStaticFindOneReturnsModelInstance()
    {
		// the return value from the find
		$usersData = $this->getUserData();

		// mock connection methods

		$this->connectionMock
			->expects( $this->once() )
			->method('findOne')
			->with('users', array(
				'email' => 'martyn@example.com',
			))
			->willReturn($usersData);

		$user = new UserUnit();
		$user->load(array(
			'email' => 'martyn@example.com',
		));

		// assertions

		$this->assertEquals($user->name, $usersData['name']);
    }
}
