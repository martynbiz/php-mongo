<?php

// TODO use getUser() -> new User with _id
// TODO test when app sets id and _id for insert
// TODO test set can accept an array too

use MartynBiz\Mongo;
use MartynBiz\Mongo\Connection;

/**
 * UserUnit class to unit test abstract Mongo methods
 */
class UserUnit extends Mongo
{
	/**
	 * @var string
	 */
	protected $collection = 'users';

	/**
	 * @var string
	 */
	protected $whitelist = array(
		'name',
		'first_name',
		'last_name',
		'email',
		'friend',
	);

	/**
	 * Custom method available to each instance
	 */
	public function doSomething()
	{
		return 'something';
	}
}

/**
 * UserUnit class to unit test abstract Mongo methods
 */
class UserValidator extends Mongo
{
	/**
	 * @var string
	 */
	protected $collection = 'users';

	/**
	 * @var string
	 */
	protected $whitelist = array(
		'name',
		'first_name',
		'last_name',
		'email',
		'friend',
	);

	/**
	 * Custom method available to each instance
	 */
	public function validate()
	{
		$this->resetErrors();

		if (empty($this->data['name'])) {
            $this->setError('name_missing_error');
        }

		return empty( $this->getErrors() );
	}
}

/**
 * This class has no whitelist - it shouldn't save
 */
class WhitelistEmptyUser extends Mongo
{
	/**
	 * @var string
	 */
	protected $collection = 'users';
}

/**
 * This class has no whitelist - it shouldn't instantiate
 */
class CollectionUndefinedUser extends Mongo
{
	/**
	 * @var string
	 */
	protected $whitelist = array(
		'name',
		'email'
	);
}

class MongoTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var User
	 */
	protected $user;

	/**
	 *
	 * @var MongoCollection_Mock
	 */
	protected $collectionMock;

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

	public function testInstantiation()
    {
        $this->assertTrue($this->user instanceof Mongo);
    }

	public function testGetConnection()
    {
        $this->assertTrue($this->user->getConnection() instanceof Connection);
    }

	/**
	 * @expectedException MartynBiz\Mongo\Exception\CollectionUndefined
	 */
	public function testInstantiationThrowsExceptionWhenCollectionUndefined()
    {
		$user = new CollectionUndefinedUser();
    }

	public function testCustomMethod()
    {
		$user = new UserUnit();

		$value = $user->doSomething();

		// assertions

		$this->assertEquals('something', $value);
    }

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

	public function testDBRefPropertySetAsMongoObject()
    {
		// the return value from the find
		$usersData = $this->getUserData( array(
			'friend' => MongoDBRef::create('users', '51b14c2de8e185801f000001'),
		) );

		// get the data for a friend
		$friendData = $this->getUserData( array(
			'_id' => new MongoId('51b14c2de8e185801f000001'),
			'name' => 'Neil McInnes',
			'first_name' => 'Neil',
			'last_name' => 'McInnes',
			'email' => 'neil@example.com',
		) );

		$this->connectionMock
			->expects( $this->at(0) ) // first call
			->method('findOne')
			->willReturn( $usersData );

		$this->connectionMock
			->expects( $this->at(1) ) // __get call for friend
			->method('findOne')
			->willReturn( $friendData );

		$this->connectionMock
			->method('getCollectionClassNameFromClassMap')
			->willReturn( 'UserUnit' );

		$result = $this->user->findOne();

		// assertions

		$this->assertTrue($result instanceof UserUnit);
		$this->assertTrue($result->friend instanceof UserUnit);
    }

	public function testSaveReturnsFalseWhenValidationFails()
    {
		// the return value from the find
		$collectionName = 'users';
		$userData = array(
			// 'name' => 'Martyn',
		);

		$this->connectionMock
			->expects( $this->never() )
			->method('insert');

		$user = new UserValidator($userData);
		$user->save();
    }

	public function testSaveInsertsWhenValidationSucceeds()
    {
		// the return value from the find
		$collectionName = 'users';
		$userData = array(
			'name' => 'Martyn',
			'created_at' => new \MongoDate(time()),
		);

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( $collectionName, $userData );

		$user = new UserValidator($userData);
		$user->save();
    }

	public function testSaveInsertsWhenSettingProperties()
    {
		// the return value from the find
		$collectionName = 'users';

		$usersData = $this->getUserData();

		// these are the values that will be passed to Connection::insert
		$values = array(
			'name' => $usersData['name'],
			'email' => $usersData['email'],
			'created_at' => new \MongoDate(time()),
		);

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( $collectionName, $values );

		$user = new UserUnit();
		$user->name = $usersData['name'];
		$user->email = $usersData['email'];
		$user->save();
    }

	public function testWhitelistEnabledWhenInvalidPropertySet()
    {
		// the return value from the find
		$collectionName = 'users';
		$usersData = $this->getUserData( array(
			'invalid' => 'not on whitelist',
		) );
		unset($usersData['_id']); // TODO this shouldn't be set from getUserData

		// these are the values that will be passed to Connection::insert
		// we'll remove the invalid one, and add additional ones such as created_at
		$values = $usersData;
		unset($values['invalid']);
		$values['created_at'] = new \MongoDate(time());

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( $collectionName, $values );

		$user = new UserUnit($usersData);
		$user->save();

		// // this second call shouldn't trigger another save, as $updated should
		// // be empty
		// $user->save();
    }

	public function testSaveInsertsWhenPassingArrayValues()
    {
		// the return value from the find
		$collectionName = 'users';
		$userData = $this->getUserData();

		$values = array(
			'name' => $userData['name'],
			'email' => $userData['email'],
			'created_at' => new \MongoDate(time()),
		);

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( $collectionName, $values );

		$user = new UserUnit();
		$user->save($values);
    }

	public function testGetDBRefReturnsValidRef()
    {
		$userData = $this->getUserData();
		$user = new UserUnit($userData);
		$user->_id = $userData['_id']; // not on whitelist

		$dbref = $user->getDBRef();

		$this->assertTrue(MongoDBRef::isRef($dbref));
    }

	// TODO how to set _id for tests?
	// public function testSaveUpdatesWithDBRefWhenPassingMongoObjectProperty()
    // {
	// 	// the return value from the find
	// 	$collectionName = 'users';
	//
	// 	// create friend object
	// 	$friend = new UserUnit( $this->getUserData( array(
	// 		'_id' => new MongoId('51b14c2de8e185801f000001'),
	// 		'name' => 'Neil',
	// 		'email' => 'neil@example.com',
	// 	) ) );
	//
	// 	$friendRef = \MongoDBRef::create('users', $friend->_id);
	//
	// 	$this->connectionMock
	// 		->expects( $this->once() )
	// 		->method('update')
	// 		->with( $collectionName, array(
	// 			'friend' => $friendRef,
	// 		) );
	//
	// 	$user = new UserUnit();
	// 	$user->friend = $friend; // append friend
	// 	$user->save();
	//
	// 	// this second call shouldn't trigger another save, as $updated should
	// 	// be empty
	// 	$user->save();
    // }

	public function testSaveUpdatesWhenPassingArrayValues()
    {
		// the return value from the find
		$collectionName = 'users';

		$userData = $this->getUserData(array(
			'updated_at' => new \MongoDate(time()),
		));

		$query = array(
			'_id' => $userData['_id'],
		);

		$values = $userData;
		unset($values['_id']);

		$options = array(
			'multi' => false,
		);

		$this->connectionMock
			->expects( $this->once() )
			->method('update')
			->with( $collectionName, $query, $values, $options );

		$user = new UserUnit($userData);
		$user->_id = $userData['_id'];
		$user->save($values);
    }

	public function testCreateReturnsObjectAfterInsert()
    {
		// the return value from the find
		$collectionName = 'users';
		$userData = $this->getUserData(array(
			'created_at' => new \MongoDate(time()),
		));
		unset($userData['_id']);

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( $collectionName, $userData );

		$user = (new UserUnit())->create($userData);

		$this->assertTrue($user instanceof UserUnit);

		// created_at timestamp
		$this->assertEquals(date('Y-m-d H:i:s', $user->created_at->sec), date('Y-m-d H:i:s'));
    }

	public function testDelete()
    {
		// the return value from the find
		$collectionName = 'users';

		$userData = $this->getUserData();

		$query = array(
			'_id' => $userData['_id'],
		);

		$options = array(
			'justOne' => true,
		);

		$this->connectionMock
			->expects( $this->once() )
			->method('delete')
			->with( $collectionName, $query, $options );

		$user = new UserUnit($userData);
		$user->_id = $userData['_id'];
		$user->delete();
    }

	public function testDeleteReturnsFalseWhenMongoIdMissing()
    {
		// the return value from the find
		$collectionName = 'users';

		$userData = $this->getUserData();
		unset($userData['_id']); // remove _id

		$query = array(
			'name' => 'Hans',
		);

		$options = array(
			'multi' => false,
		);

		$this->connectionMock
			->expects( $this->never() )
			->method('delete');

		$user = new UserUnit($userData);
		$result = $user->delete();

		$this->assertFalse($result);
    }

	/**
	 * @expectedException MartynBiz\Mongo\Exception\WhitelistEmpty
	 */
	public function testSaveThrowsExceptionWhenWhitelistEmpty()
    {
		$user = new WhitelistEmptyUser();
		$user->name = 'Mr Careless';
		$user->save();
    }

	public function testToArrayWithArray()
    {
		$id = new \MongoId('51b14c2de8e185801f000000');
		$dbref = \MongoDBRef::create('users', '51b14c2de8e185801f000001');
		$user = new UserUnit( array(
			'name' => 'Martyn',
		) );

		$values = array(
			'friend' => array(
				'Monty',
				$id,
				$dbref,
				$user,
			),
		);

		$this->connectionMock
			->method('findOne')
			->willReturn( array(
				'name' => 'Neil',
			) );

		$user = new UserUnit($values);
		$toArray = $user->toArray(2);

		// assertions

		$this->assertEquals('Monty', $toArray['friend'][0]);
		$this->assertEquals('51b14c2de8e185801f000000', $toArray['friend'][1]);
		$this->assertEquals('Neil', $toArray['friend'][2]['name']);
		$this->assertEquals('Martyn', $toArray['friend'][3]['name']);
    }

	public function testToArrayDeep()
    {
		$louise = new UserUnit( $this->getUserData( array(
			'_id' => new MongoId('51b14c2de8e185801f000001'),
			'name' => 'Louise',
			'email' => 'louise@example.com',
		) ) );

		$neil = new UserUnit( $this->getUserData( array(
			'_id' => new MongoId('51b14c2de8e185801f000001'),
			'name' => 'Neil',
			'email' => 'neil@example.com',
			'friend' => $louise,
		) ) );

		$martyn = new UserUnit( $this->getUserData( array(
			'friend' => $neil,
		) ) );

		$this->connectionMock
			->method('findOne')
			->willReturn( array(
				'name' => 'Neil',
			) );

		$toArray = $martyn->toArray(1);

		// assertions

		$this->assertEquals('...', $toArray['friend']['friend']);
    }

	protected function getUserData($data=array())
	{
		return array_merge(array(
			'_id' => new MongoId('51b14c2de8e185801f000000'),
			'name' => 'Martyn Bissett',
			'first_name' => 'Martyn',
			'last_name' => 'Bissett',
			'email' => 'martyn@example.com',
		), $data);
	}
}
