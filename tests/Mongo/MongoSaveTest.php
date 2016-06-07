<?php

class MongoSaveTest extends MongoTestAbstract
{
	// public function testSaveReturnsFalseWhenValidationFails()
    // {
	// 	// the return value from the find
	// 	$collectionName = 'users';
	// 	$userData = array(
	// 		// 'name' => 'Martyn',
	// 	);
	//
	// 	$this->connectionMock
	// 		->expects( $this->never() )
	// 		->method('insert');
	//
	// 	$user = new UserValidator($userData);
	// 	$user->save();
    // }

	public function testSaveInserts()
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

	public function testSaveConvertsMongoIteratorObjectToArrayOfDBRefs()
    {
		// the return value from the find
		$tagsData = array(
			$this->getTagData(), // defaults
			$this->getTagData(array( // defined
				'name' => 'Art',
				'slug' => 'art',
			)),
		);

		// mock method to return mock collection
		$this->connectionMock
			->expects( $this->once() )
			->method('find')
			->with('tags', array(), array())
			->willReturn($tagsData);

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with('articles', array(
				'tags' => array(
					\MongoDBRef::create('tags', $tagsData[0]['_id']),
					\MongoDBRef::create('tags', $tagsData[1]['_id']),
				),
				'created_at' => new \MongoDate(time()),
			))
			->willReturn($tagsData);

		// first, fetch the tags
		$tags = TagUnit::find();

		// next, set the article's tags property and save
		// when saving, an array of dbrefs should be given
		$article = new ArticleUnit();
		$article->tags = $tags;
		$article->save();
    }

	public function testWhitelistEnabledWhenInvalidPropertySet()
    {
		// ================
		// prepare data

		// the return value from the find
		$usersData = $this->getUserData( array(
			'invalid' => 'not on whitelist',
		), false );


		// ================
		// mock

		// these are the values that will be passed to Connection::insert
		// we'll remove the invalid one, and add additional ones such as created_at
		$values = $usersData;
		unset($values['invalid']);
		$values['created_at'] = new \MongoDate(time());

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( 'users', $values );


		// ================
		// run test

		$user = new UserUnit($usersData);
		$user->save();
    }

	public function testSaveInsertsWhenPassingArrayValues()
    {
		// ================
		// prepare data

		// the return value from the find
		$userData = $this->getUserData(array(), false);


		// ================
		// mock

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( 'users', array_merge($userData, array(
				'created_at' => new \MongoDate(time()),
			)) );


		// ================
		// run test

		$user = new UserUnit();
		$user->save( $userData );
    }

	public function testSaveInsertWithDBRefWhenPassingMongoObjectProperty()
    {
		// ================
		// prepare data

		// create friend object
		$friend = new \UserUnit( $this->getUserData( array(
			'name' => 'Neil',
			'email' => 'neil@example.com',
		) ) );
		$friend->_id = new \MongoId();


		// ================
		// mock

		$this->connectionMock
			->expects( $this->once() )
			->method('insert')
			->with( 'users', array(
				'name' => 'Martyn',
				'email' => 'martyn@example.com',
				'friend' => \MongoDBRef::create('users', $friend->_id),
				'created_at' => new \MongoDate(time()),
			) );

		$user = new UserUnit(array(
			'name' => 'Martyn',
			'email' => 'martyn@example.com',
			'friend' => $friend, // pass object
		));


		// ================
		// run test

		$user->save();
    }

	public function testSaveUpdatesWhenPassingArrayValues()
    {
		// ================
		// prepare User

		// this is our User we'll be saving, note we set the _id as this
		// is required to ensure it's an update() - not a insert() .. usually
		// this would be set internally by load() during a find/findOne() call
		$user = new UserUnit();
		$user->_id = new \MongoId();

		// this is the data we'll pass to save()
		$saveData = $this->getUserData(array(), false); // no _id


		// ================
		// mock

		$this->connectionMock
			->expects( $this->once() )
			->method('update')
			->with( 'users', array( // query
				'_id' => $user->_id,
			), array( // update values
				'$set' => array_merge($saveData, array(
					'updated_at' => new \MongoDate(time()),
				)),
			), array( // options
				'multi' => false,
			) );


		// ================
		// run test

		$user->save($saveData);
    }

	/**
	 * @expectedException MartynBiz\Mongo\Exception\WhitelistEmpty
	 */
	public function testSaveThrowsExceptionWhenWhitelistEmpty()
    {
		$user = new UserWhitelistEmpty();
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
		$article = new ArticleUnit( array(
			'title' => 'My article',
		) );

		$values = array(
			'friend' => array(
				'Monty',
				$id,
				$dbref,
				$user,
			),
			'article' => $article,
			'articles' => array(
				$article,
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
		$this->assertEquals('My article', $toArray['article']['title']);
		$this->assertEquals('My article', $toArray['articles'][0]['title']);
    }
}
