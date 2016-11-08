<?php

use MartynBiz\Mongo\Connection;

use MongoDB\BSON\ObjectID;
use MongoDB\Database;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * @var MongoDB_Mock
	 */
	protected $dbMock;

	/**
	 * @var MongoCollection_Mock
	 */
	protected $collectionMock;

	public function setup()
	{
		$this->dbMock = $this->getMockBuilder('MongoDB\Database')
			->disableOriginalConstructor()
			->getMock();

		$this->collectionMock = $this->getMockBuilder('MongoDB\Collection')
			->disableOriginalConstructor()
			->getMock();

		// mock method to return mock collection
		$this->dbMock
		 	->method('selectCollection')
			->willReturn( $this->collectionMock );

		// reset Connection as it's being used across multiple tests (unit, int)
		Connection::getInstance()->resetInstance();

		// set options such as classmap
		Connection::getInstance()->init( array(
			'db' => 'test',
			'classmap' => array(
				'users' => 'User',
			),
		) );

		// initiate the connection. set the database as the mock db
		Connection::getInstance()->setDatabase( $this->dbMock );

		$this->connection = Connection::getInstance();
	}

	public function test_instantiation()
    {
        $this->assertTrue($this->connection instanceof Connection);
    }

	public function test_get_database_returns_database_object()
    {
        $this->assertTrue($this->connection->getDatabase() instanceof Database);
    }

	public function test_multiple_instances()
    {
		$conn1 = Connection::getInstance('conn1')->init( array(
			'classmap' => array(
				'users' => 'User',
			),
		) );

		$conn2 = Connection::getInstance('conn2')->init( array(
			'classmap' => array(
				'users' => 'Articles',
			),
		) );

		$this->assertEquals($conn1, Connection::getInstance('conn1'));
		$this->assertEquals($conn2, Connection::getInstance('conn2'));
		$this->assertNotEquals(spl_object_hash($conn1), spl_object_hash($conn2));
    }

	public function test_find_returns_array()
    {
		$collection = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array(
			'fields' => array('name', 'email'),
		); // fields

		// the return value from the find
		$usersData = array(
			$this->getUserValues(), // one result
		);

		$this->collectionMock
			->expects( $this->once() )
			->method('find')
			->with($query, $options['fields'])
			->willReturn( $usersData );

		$result = $this->connection->find($collection, $query, $options);

		// assertions
		$this->assertEquals(count($usersData), count($result));
		$this->assertEquals($result[0]['name'], $usersData[0]['name']);
    }

	public function test_find_one_returns_array()
    {
		$collection = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// the return value from the find
		$userData = $this->getUserValues();

		$this->collectionMock
			->expects( $this->once() )
			->method('findOne')
			->with($query, $options)
			->willReturn( $userData );

		$result = $this->connection->findOne($collection, $query, $options);

		// assertions
		$this->assertEquals($result['name'], $userData['name']);
    }

	public function test_get_collection_class_name_from_class_map()
    {
		$class = $this->connection->getCollectionClassNameFromClassMap('users');

		// assertions
		$this->assertEquals('User', $class);
    }

	public function test_insert_calls_insert_with_object_id()
    {
		$collectionName = 'users';
		$values = $this->getUserValues(array(
			'id' => 1,
		));

		$this->collectionMock
			->expects( $this->once() )
			->method('insertOne')
			->with($values);

		$this->collectionMock
			->expects( $this->at(0) )
			->method('findOne')
			->willReturn( array(
				$collectionName => 1
			) );

		$this->connection->insert($collectionName, $values);
    }

	public function test_update_calls_update_one_method()
    {
		$collectionName = 'users';
		$query = array(
			'$id' => '1234567890'
		);
		$values = $this->getUserValues();

		$this->collectionMock
			->expects( $this->once() )
			->method('updateOne')
			->with($query, $values);

		$this->connection->update($collectionName, $query, $values);
    }

	public function test_update_calls_update_many_method()
    {
		$collectionName = 'users';
		$query = array(
			'$id' => '1234567890'
		);
		$values = $this->getUserValues();

		$this->collectionMock
			->expects( $this->once() )
			->method('updateMany')
			->with($query, $values);

		$this->connection->update($collectionName, $query, $values, [
			'multi' => true,
		]);
    }

	public function test_delete_calls_delete_one_method()
    {
		$collectionName = 'users';
		$query = array(
			'$id' => '1234567890'
		);
		$values = $this->getUserValues();

		$this->collectionMock
			->expects( $this->once() )
			->method('deleteOne')
			->with($query);

		$this->connection->delete($collectionName, $query);
    }

	public function test_delete_calls_delete_many_method()
    {
		$collectionName = 'users';
		$query = array(
			'$id' => '1234567890'
		);
		$values = $this->getUserValues();

		$this->collectionMock
			->expects( $this->once() )
			->method('deleteMany')
			->with($query);

		$this->connection->delete($collectionName, $query, [
			'multi' => true,
		]);
    }

	protected function getUserValues($data=array())
	{
		return array_merge(array(
			'_id' => new ObjectID('51b14c2de8e185801f000000'),
			'name' => 'Martyn',
			'email' => 'martyn@example.com',
		), $data);
	}
}
