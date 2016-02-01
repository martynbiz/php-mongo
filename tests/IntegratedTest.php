<?php

use MartynBiz\Mongo;
use MartynBiz\Mongo\Connection;
use MartynBiz\Mongo\MongoIterator;

class IntegratedTest extends TestCase
{
	/**
	 * @var User
	 */
	protected $martyn;

	/**
	 * @var User
	 */
	protected $neil;

	/**
	 * @var User
	 */
	protected $louise;

	/**
	 * @var User
	 */
	protected $article;

	/**
	 *
	 * @var MongoDB
	 */
	protected $db;

	public function setup()
	{
		$options = array(
			'db' => 'phpmongo_test',
			'classmap' => array(
				'users' => 'UserIntegrated',
				'articles' => 'ArticleIntegrated',
			),
		);

		// reset Connection as it's being used across multiple tests (unit, int)
		Connection::getInstance()->resetInstance();

		// set options such as classmap
		Connection::getInstance()->init($options);


		// create some fixtures

		// neil
		$this->neil = UserIntegrated::create(array(
			'name' => 'Neil',
			'email' => 'neil@example.com',
		));

		// louise
		$this->louise = UserIntegrated::create(array(
			'name' => 'Louise',
			'email' => 'louise@example.com',
		));

		// martyn
		$this->martyn = UserIntegrated::create(array(
			'name' => 'Martyn',
			'email' => 'martyn@example.com',
		));

		// article by martyn
		$this->article = ArticleIntegrated::create(array(
			'title' => 'My article',
			'slug' => 'my-article',
			'author' => $this->martyn,
		));
	}

	public function teardown()
	{
		// remove fixtures
		UserIntegrated::remove(array());
		ArticleIntegrated::remove(array());
	}

	public function testGetWhenValueIsArrayOfDBRefsReturnsModelInstances()
    {
		$user = new UserIntegrated();
		$user->set('friends', array(
			$this->neil->getDBRef(),
			$this->louise->getDBRef(),
		));

		// assertions
		$this->assertEquals('Neil', $user->friends[0]->name);
		$this->assertEquals('Louise', $user->friends[1]->name);
    }

	public function testFindReturnsArrayOfInstances()
    {
		$query = array(
			'email' => 'martyn@example.com',
		);

		$result = UserIntegrated::find($query);

		// assertions

		$this->assertTrue($result[0] instanceof UserIntegrated);
		$this->assertEquals(1, count($result));
		$this->assertEquals($result[0]->email, $query['email']);
    }

	public function testFindOneReturnsModelInstance()
    {
		$query = array(
			'email' => 'martyn@example.com',
		);

		$result = UserIntegrated::findOne($query);

		// assertions

		$this->assertTrue($result instanceof UserIntegrated);
		$this->assertEquals($result->email, $query['email']);
    }

	public function testDBRefPropertySetAsMongoObject()
    {
		$article = ArticleIntegrated::findOne();

		// assertions

		$this->assertTrue($article instanceof ArticleIntegrated);
		$this->assertTrue($article->author instanceof UserIntegrated);
		$this->assertEquals('Martyn', $article->author->name);
    }

	public function testSaveInsertsWhenSettingProperties()
    {
		$louiseData = $this->getUserData( array(
			'name' => 'Louise',
			'email' => 'louise@example.com',
		) );

		$user = new UserIntegrated();
		$user->name = $louiseData['name'];
		$user->email = $louiseData['email'];
		$user->save();

		// attempt to find
		$query = array(
			'email' => $louiseData['email'],
		);

		$result = UserIntegrated::findOne($query);

		// assertions

		$this->assertEquals($result->email, $louiseData['email']);

		// created_at timestamp
		$this->assertTrue($result->created_at instanceof \MongoDate);
		$this->assertEquals(date('Y-m-d H:i:s', $result->created_at->sec), date('Y-m-d H:i:s'));
    }

	public function testSaveInsertsWhenPassingArrayValues()
    {
		$louiseData = $this->getUserData( array(
			'name' => 'Louise',
			'email' => 'louise@example.com',
		) );

		$user = new UserIntegrated();
		$user->save($louiseData);

		// attempt to find
		$query = array(
			'email' => $louiseData['email'],
		);

		$result = UserIntegrated::findOne($query);

		// assertions

		$this->assertEquals($result->email, $louiseData['email']);

		// created_at timestamp
		$this->assertTrue($result->created_at instanceof \MongoDate);
		$this->assertEquals(date('Y-m-d H:i:s', $result->created_at->sec), date('Y-m-d H:i:s'));
    }

	public function testCreateReturnsObjectAfterInsert()
    {
		$userData = $this->getUserData();

		$user = (new UserIntegrated())->create($userData);

		// assertions

		$this->assertEquals($user->email, $userData['email']);

		// created_at timestamp
		$this->assertTrue($user->created_at instanceof \MongoDate);
		$this->assertEquals(date('Y-m-d H:i:s', $user->created_at->sec), date('Y-m-d H:i:s'));
    }

	public function testDBRefObjectificationWithDBRefWhenPassingMongoObjectProperty()
    {
		$userData = $this->getUserData( array(
			'name' => 'Louise',
			'email' => 'louise@example.com',
		) );

		// create friend object
		$louise = new UserIntegrated($userData);
		$louise->save();

		$user = new UserIntegrated();
		$user->friend = $louise; // append friend

		// assertions

		$this->assertEquals($user->friend->email, $louise->email);
    }

	public function testSaveUpdatesWhenPassingArrayValues()
    {
		// the return value from the find
		$updatedValues = array(
			'email' => 'updated@example.com'
		);

		$user = (new UserIntegrated())->findOne();
		$user->save($updatedValues);

		// now try to find that new user
		$user = (new UserIntegrated())->findOne($updatedValues);

		// assertions

		$this->assertEquals($user->email, $updatedValues['email']);

		// updated_at timestamp
		$this->assertTrue($user->updated_at instanceof \MongoDate);
		$this->assertEquals(date('Y-m-d H:i:s', $user->updated_at->sec), date('Y-m-d H:i:s'));
    }

	public function testDelete()
    {
		$martyn = $this->getUserData();

		$query = array(
			'email' => $martyn['email'],
		);

		$options = array(
			'multi' => false,
		);

		// get a user to delete
		$user = (new UserIntegrated())->findOne($query);

		// delete the user
		$user->delete($query, $options);

		// now try to find the deleted user
		$user = (new UserIntegrated())->findOne($query);

		// assertions

		$this->assertTrue(is_null($user));
    }


	// connection

	public function testInsertCreateSequenceNumbers()
    {
		// reset Connection as it's being used across multiple tests (unit, int)
		$connection = Connection::getInstance();

		// don't really know where sequence will be up to, so we'll start from
		// whatever it gives us here
		$baseSequence = $connection->getNextSequence('users');

		$this->assertEquals($baseSequence + 1, $connection->getNextSequence('users') );
		$this->assertEquals($baseSequence + 2, $connection->getNextSequence('users') ); // upsert
		$this->assertEquals($baseSequence + 3, $connection->getNextSequence('users') ); // upsert
    }

	// push

	public function testPushWithSingleValueAndHasMongoIdConvertsToPushUpdate()
    {
		// the return value from the find
		$article = ArticleUnit::findOne();

		$article->push( array(
			'photo_ids' => 1,
		) );

		$article->push( array(
			'photo_ids' => 1,
		) );

		$this->assertTrue( isset($article->photo_ids) );
		$this->assertEquals( 2, count($article->photo_ids) );
    }

	public function testPushWithArrayValueAndHasMongoIdConvertsToPushUpdate()
    {
		// the return value from the find
		$article = ArticleUnit::findOne();

		// attach
		$article->push( array(
			'photo_ids' => array(
				1, 2, 3,
			),
		) );

		$this->assertTrue( isset($article->photo_ids) );
		$this->assertEquals( 3, count($article->photo_ids) );
    }

	public function testPushWithMongoValueConvertsToPushUpdateWithDBRef()
    {
		// the return value from the find
		$user = UserIntegrated::findOne(array(
			'name' => 'Martyn',
		));

		$friend = UserIntegrated::findOne(array(
			'name' => 'Neil',
		));

		// attach
		$user->push( array(
			'friends' => $friend,
		) );

		$this->assertTrue( isset($user->friends) );
		$this->assertEquals( 1, count($user->friends) );
		$this->assertEquals( $friend, $user->friends[0] );
    }

	public function testPushMultiplePropertiesWithMongoValueConvertsToPushUpdateWithDBRef()
    {
		// the return value from the find
		$martyn = UserIntegrated::findOne(array(
			'name' => 'Martyn',
		));

		$neil = UserIntegrated::findOne(array(
			'name' => 'Neil',
		));

		$louise = UserIntegrated::findOne(array(
			'name' => 'Louise',
		));

		// attach
		$martyn->push( array(
			'friends' => $neil,
			'compadres' => array(
				$neil,
				$louise,
			),
		) );

		$this->assertTrue( isset($martyn->friends) );
		$this->assertTrue( isset($martyn->compadres) );
		$this->assertEquals( 1, count($martyn->friends) );
		$this->assertEquals( 2, count($martyn->compadres) );
		$this->assertEquals( $neil, $martyn->friends[0] );
		$this->assertEquals( $neil, $martyn->compadres[0] );
		$this->assertEquals( $louise, $martyn->compadres[1] );
    }

	public function testPushWithMongoArrayValueConvertsToPushUpdateWithDBRef()
    {
		$martyn = UserIntegrated::findOne(array(
			'name' => 'Martyn',
		));

		$neil = UserIntegrated::findOne(array(
			'name' => 'Neil',
		));

		// attach
		$martyn->push( array(
			'friends' => array(
				$neil,
			),
		) );

		$this->assertTrue( isset($martyn->friends) );
		$this->assertEquals( 1, count($martyn->friends) );
		$this->assertEquals( $neil, $martyn->friends[0] );
    }

	public function testPushWithMongoIteratorValueConvertsToPushUpdateWithDBRef()
    {
		$martyn = UserIntegrated::findOne(array(
			'name' => 'Martyn',
		));

		$friends = UserIntegrated::find(array(
			'name' => 'Neil',
		));

		// attach
		$martyn->push( array(
			'friends' => $friends,
		) );

		$this->assertTrue( isset($martyn->friends) );
		$this->assertEquals( 1, count($martyn->friends) );
		$this->assertEquals( $friends[0], $martyn->friends[0] );
    }

	public function testPushWithArrayValueAndHasMongoIdConvertsToPushUpdateWhenEachIsFalse()
    {
		// the return value from the find
		$article = ArticleIntegrated::findOne();

		$photoIds = array(
			1, 2, 3,
		);

		$article->push( array(
			'photo_ids' => $photoIds,
		), array(
			'each' => false,
		));

		$this->assertTrue( isset($article->photo_ids) );
		$this->assertEquals( 1, count($article->photo_ids) );
		$this->assertEquals( $photoIds, $article->photo_ids[0] );
    }
}
