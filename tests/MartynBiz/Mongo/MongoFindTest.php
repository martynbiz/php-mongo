<?php

// TODO test set can accept an array too

require_once 'MongoTestAbstract.php';

class MongoFindTest extends MongoTestAbstract
{
	public function testFindReturnsArrayOfInstances()
    {
		$collectionName = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// the return value from the find
		$usersData = array(
			$this->getUserData(), // one result
		);

		// mock method to return mock collection
		$this->connectionMock
			->expects( $this->once() )
			->method('find')
			->with($collectionName, $query, $options)
			->willReturn($usersData);

		$result = $this->user->find($query, $options);

		// assertions

		$this->assertTrue($result[0] instanceof UserUnit);
		$this->assertEquals(count($usersData), count($result));
		$this->assertEquals($result[0]->name, $usersData[0]['name']);
    }

	public function testStaticFindReturnsArrayOfInstances()
    {
		$collectionName = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// the return value from the find
		$usersData = array(
			$this->getUserData(), // one result
		);

		// mock method to return mock collection
		$this->connectionMock
			->expects( $this->once() )
			->method('find')
			->with($collectionName, $query, $options)
			->willReturn($usersData);

		$result = UserUnit::find($query, $options);

		// assertions

		$this->assertTrue($result[0] instanceof UserUnit);
		$this->assertEquals(count($usersData), count($result));
		$this->assertEquals($result[0]->name, $usersData[0]['name']);
    }

	public function testFindReturnsEmptyArrayWhenNotFound()
    {
		$collectionName = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// mock connection methods

		$this->connectionMock
			->expects( $this->once() )
			->method('find')
			->with($collectionName, $query, $options)
			->willReturn(null);

		$result = $this->user->find($query, $options);

		// assertions

		$this->assertTrue(is_array($result) and empty($result));
    }

	public function testFindOneReturnsModelInstance()
    {
		$collectionName = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// the return value from the find
		$usersData = $this->getUserData();

		// mock connection methods

		$this->connectionMock
			->expects( $this->once() )
			->method('findOne')
			->with($collectionName, $query, $options)
			->willReturn($usersData);

		$result = $this->user->findOne($query, $options);

		// assertions

		$this->assertTrue($result instanceof UserUnit);
		$this->assertEquals($result->name, $usersData['name']);
    }

	public function testFindWithMongoInstanceQueriesWithDBRef()
    {
		// ===========================
		// set up $friend and $user with _ids (required for dbref)

		$friendData = $this->getUserData( array(
			'name' => 'Neil McInnes',
			'first_name' => 'Neil',
			'last_name' => 'McInnes',
			'email' => 'neil@example.com',
		) );

		$friend = new UserUnit($friendData);
		$friend->_id = $friendData['_id'];

		$userData = $this->getUserData( array(
			'friend' => $friend->getDBRef(),
		) );

		$user = new UserUnit($userData);
		$user->_id = $userData['_id'];



		// =======================
		// mock connection methods

		$this->connectionMock
			->expects( $this->once() ) // first call
			->method('findOne')
			->with('users', array(
				'friend' => $friend->getDBRef(),
			), array())
			->willReturn($user);

		$result = $this->user->findOne(array(
			'friend' => $friend, // Mongo instance
		));
    }
}
