<?php

class MongoCountTest extends MongoTestAbstract
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

		$count = $this->user->count($query, $options);

		// assertions

		$this->assertTrue(is_numeric($count));
		$this->assertEquals(count($usersData), $count);
    }
}
