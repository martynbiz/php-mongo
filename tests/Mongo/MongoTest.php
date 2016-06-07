<?php

use MartynBiz\Mongo\Mongo;
use MartynBiz\Mongo\Connection;

class MongoTest extends MongoTestAbstract
{
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
		$user = new UserCollectionUndefined();
    }

	public function testCustomGetterSetterMethods()
    {
		$user = new UserCustomGetterSetter();

		$user->first_name = 'getter';
		$user->last_name = 'setter';

		// assertions
		$this->assertEquals(md5('getter'), $user->first_name);
		$this->assertEquals(md5(md5('setter')), $user->last_name);
    }

	public function testCustomGetterSetterMethodsWhenUseCustomGetterSetterIsFalse()
    {
		$user = new UserCustomGetterSetter();

		$user->set('first_name', 'getter');
		$user->set('last_name', 'setter', false);

		// assertions
		$this->assertEquals('getter', $user->get('first_name', false));
		$this->assertEquals('setter', $user->get('last_name'));
    }

	public function testCustomGetterSetterMethodsWithSave()
    {
		$user = new UserCustomGetterSetter();

		$user->save(array(
			'first_name' => 'getter',
			'last_name' => 'setter',
		));

		// assertions
		$this->assertEquals(md5('getter'), $user->first_name);
		$this->assertEquals(md5(md5('setter')), $user->last_name);
    }

	public function testCustomGetterSetterMethodsWithFactory()
    {
		$user = UserCustomGetterSetter::factory(array(
			'first_name' => 'getter',
			'last_name' => 'setter',
		));

		// assertions
		$this->assertEquals(md5('getter'), $user->first_name);
		$this->assertEquals(md5(md5('setter')), $user->last_name);
    }

	public function testSetWhenValueIsArray()
    {
		$user = new UserUnit();

		$user->set(array(
			'first_name' => 'Martyn',
			'last_name' => 'Bissett',
		));

		// assertions
		$this->assertEquals('Martyn', $user->first_name);
		$this->assertEquals('Bissett', $user->last_name);
    }

	public function testProperyReturnsNullWhenDoesNotExist()
    {
		$user = new UserUnit();

		// assertions
		$this->assertTrue( is_null($user->idontexist) );
    }

	public function testCustomMethod()
    {
		$user = new UserUnit();

		$value = $user->doSomething();

		// assertions

		$this->assertEquals('something', $value);
    }

	public function testGetDBRefPropertyReturnsObjectWithAllPropertiesFromDB()
    {
		$collectionName = 'users';

		$query = array(
			'_id' => new \MongoId('51b14c2de8e185801f000001'),
		);

		$options = array();

		$this->connectionMock
			->expects( $this->once() )
			->method('findOne')
			->with($collectionName, $query, $options)
			->willReturn(array(
				'name' => 'Neil',
				'email' => 'neil@example.com',
				'_id' => new \MongoId('51b14c2de8e185801f000001'),
				'notwhitelisted' => 'hello'
			));

		$this->connectionMock
			->method('getCollectionClassNameFromClassMap')
			->willReturn( 'UserUnit' );

		$user = new UserUnit(array(
			'friend' => MongoDBRef::create('users', '51b14c2de8e185801f000001'),
		));

		$this->assertEquals('Neil', $user->friend->name);

		// now that we've accessed the friend property once, it should be cached
		// and >friend should now be an object
		$this->assertTrue($user->friend instanceof UserUnit);

		$this->assertEquals('neil@example.com', $user->friend->email);

		// test an item not on the whitelist
		$this->assertEquals('hello', $user->friend->notwhitelisted);
		$this->assertEquals(new \MongoId('51b14c2de8e185801f000001'), $user->friend->_id);
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



	public function testGetDBRefReturnsValidRef()
    {
		$userData = $this->getUserData();
		$user = new UserUnit($userData);
		$user->_id = $userData['_id']; // not on whitelist

		$dbref = $user->getDBRef();

		$this->assertTrue(MongoDBRef::isRef($dbref));
    }



	public function testFactoryReturnsObjectAfterInstatiating()
    {
		// the return value from the find
		$collectionName = 'users';
		$userData = $this->getUserData();
		unset($userData['_id']);

		$this->connectionMock
			->expects( $this->never() )
			->method('insert');

		$user = (new UserUnit())->factory($userData);

		$this->assertTrue($user instanceof UserUnit);

		// created_at timestamp
		$this->assertFalse(isset($user->created_at));
    }

	public function testFactoryReturnsObjectWithParams()
    {
		// the return value from the find
		$collectionName = 'users';
		$userData = $this->getUserData();
		unset($userData['_id']);

		$this->connectionMock
			->expects( $this->never() )
			->method('insert');

		$userInstance = new UserUnit();
		$user = $userInstance->factory($userData);

		$this->assertTrue($user instanceof UserUnit);
    }

	public function testStaticFactoryReturnsObjectWithParams()
    {
		// the return value from the find
		$collectionName = 'users';
		$userData = $this->getUserData();
		unset($userData['_id']);

		$this->connectionMock
			->expects( $this->never() )
			->method('insert');

		$user = UserUnit::factory($userData);

		$this->assertTrue($user instanceof UserUnit);
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

		$userInstance = new UserUnit();
		$user = $userInstance->create($userData);

		$this->assertTrue($user instanceof UserUnit);

		// created_at timestamp
		$this->assertEquals(date('Y-m-d H:i:s', $user->created_at->sec), date('Y-m-d H:i:s'));
    }

	public function testStaticCreateReturnsObjectAfterInsert()
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

		$user = UserUnit::create($userData);

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

	public function testRemove()
    {
		// the return value from the find
		$collectionName = 'users';

		$userData = $this->getUserData();

		$query = array(
			'email' => $userData['email'],
		);

		$options = array();

		$this->connectionMock
			->expects( $this->once() )
			->method('delete')
			->with( $collectionName, $query, $options );

		UserUnit::remove($query, $options);
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
}
