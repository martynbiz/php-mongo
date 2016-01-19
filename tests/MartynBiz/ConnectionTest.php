<?php

use MartynBiz\Mongo\Connection;

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
	 *
	 * @var MongoCollection_Mock
	 */
	protected $collectionMock;

	public function setup()
	{
		$this->dbMock = $this->getMockBuilder('MongoDB')
			->disableOriginalConstructor()
			->getMock();

		$this->collectionMock = $this->getMockBuilder('MongoCollection')
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
			'classmap' => array(
				'users' => 'User',
			),
		) );

		// initiate the connection. set the database as the mock db
		Connection::getInstance()->setDatabase( $this->dbMock );

		$this->connection = Connection::getInstance();
	}

	public function testInstantiation()
    {
        $this->assertTrue($this->connection instanceof Connection);
    }

	public function testFindReturnsArray()
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

	public function testFindOneReturnsArray()
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

	public function testGetCollectionClassNameFromClassMap()
    {
		$class = $this->connection->getCollectionClassNameFromClassMap('users');

		// assertions

		$this->assertEquals('User', $class);
    }

	public function testInsertCallsMongoDBInsertWithMongoIdAsArgument()
    {
		$collectionName = 'users';
		$values = $this->getUserValues(array(
			'id' => 1,
		));

		$this->collectionMock
			->expects( $this->once() )
			->method('insert')
			->with($values);

		$this->collectionMock
			->expects( $this->at(0) )
			->method('findOne')
			->willReturn( array(
				$collectionName => 1
			) );

		$this->connection->insert($collectionName, $values);
    }

	public function testUpdateCallsMongoDBUpdate()
    {
		$collectionName = 'users';
		$query = array(
			'$id' => '1234567890'
		);
		$values = $this->getUserValues();
		$options = array(
			'multi' => true,
		);

		$this->collectionMock
			->expects( $this->once() )
			->method('update')
			->with($query, $values, $options);

		$this->connection->update($collectionName, $query, $values, $options);
    }

	public function testUpdateNeverCallsMongoDBUpdateWhenValuesEmpty()
    {
		$collectionName = 'users';
		$query = array(
			'$id' => '1234567890'
		);
		$values = array();
		$options = array(
			'multi' => true,
		);

		$this->collectionMock
			->expects( $this->never() )
			->method('update')
			->with($query, $values, $options);

		$this->connection->update($collectionName, $query, $values, $options);
    }

	public function testGetNextSequenceInsertsWhenSequencesNotFound()
    {
		$collectionName = 'users';

		$insertValues = array(
			$collectionName => 1,
		);

		$this->collectionMock
			->expects( $this->once() )
			->method('findOne')
			->with()
			->willReturn(null);

		$this->collectionMock
			->expects( $this->once() )
			->method('insert')
			->with($insertValues);

		$nextSequence = $this->connection->getNextSequence($collectionName);

		$this->assertEquals(1, $nextSequence);
    }

	public function testGetNextSequenceInsertsWhenSequencesFound()
    {
		$collectionName = 'users';

		$this->collectionMock
			->expects( $this->once() )
			->method('findOne')
			->with()
			->willReturn(array(
				$collectionName => 99,
			));

		$this->collectionMock
			->expects( $this->never() )
			->method('insert');

		$nextSequence = $this->connection->getNextSequence($collectionName);

		$this->assertEquals(99, $nextSequence);
    }

	public function testGetNextSequenceInsertsWhenSequencesFoundButMissingCollectionName()
    {
		$collectionName = 'users';

		$updateQuery = array(
			'_id' => '1234567890',
		);

		$updateValues = array(
			$collectionName => 1,
		);

		$this->collectionMock
			->expects( $this->once() )
			->method('findOne')
			->with()
			->willReturn(array(
				'_id' => '1234567890', // index just needs to be present
				'another' => 99,
			));

		$this->collectionMock
			->expects( $this->never() )
			->method('insert');

		$this->collectionMock
			->expects( $this->once() )
			->method('update')
			->with($updateQuery, $updateValues);

		$nextSequence = $this->connection->getNextSequence($collectionName);

		$this->assertEquals(1, $nextSequence);
    }

	protected function getUserValues($data=array())
	{
		return array_merge(array(
			'_id' => new MongoId('51b14c2de8e185801f000000'),
			'name' => 'Martyn',
			'email' => 'martyn@example.com',
		), $data);
	}
}
