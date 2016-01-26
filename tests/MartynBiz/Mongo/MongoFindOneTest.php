<?php

// TODO test set can accept an array too

require_once 'MongoTestAbstract.php';

class MongoFindOneTest extends MongoTestAbstract
{
	public function testStaticFindOneReturnsModelInstance()
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

		$result = UserUnit::findOne($query, $options);

		// assertions

		$this->assertTrue($result instanceof UserUnit);
		$this->assertEquals($result->name, $usersData['name']);
    }

	/**
	 * @expectedException MartynBiz\Mongo\Exception\NotFound
	 */
	public function testFindOneOrFailThrowsExceptionWhenNotFound()
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
			->willReturn(null);

		$result = $this->user->findOneOrFail($query, $options);
    }

	public function testFindOneOrFailReturnsModelInstance()
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

		$result = $this->user->findOneOrFail($query, $options);

		// assertions

		$this->assertTrue($result instanceof UserUnit);
		$this->assertEquals($result->name, $usersData['name']);
    }

	public function testFindOneReturnsNullWhenNotFound()
    {
		$collectionName = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// mock connection methods

		$this->connectionMock
			->expects( $this->once() )
			->method('findOne')
			->with($collectionName, $query, $options)
			->willReturn(null);

		$result = $this->user->findOne($query, $options);

		// assertions

		$this->assertTrue(is_null($result));
    }
}
