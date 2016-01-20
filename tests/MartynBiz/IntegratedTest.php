<?php

use MartynBiz\Mongo;
use MartynBiz\Mongo\Connection;

/**
 * User class to test abstract Mongo methods
 */
class UserIntegrated extends Mongo
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
		'email',
		'friend'
	);
}

class IntegratedTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var User
	 */
	protected $user;

	/**
	 *
	 * @var MongoDB
	 */
	protected $db;

	public function setup()
	{
		$this->user = new UserIntegrated();

		$options = array(
			'db' => 'phpmongo_test',
			'classmap' => array(
				'users' => 'UserIntegrated',
			),
		);

		// reset Connection as it's being used across multiple tests (unit, int)
		Connection::getInstance()->resetInstance();

		// set options such as classmap
		Connection::getInstance()->init($options);


		// create some fixtures

		$client = new \MongoClient();
		$this->db = $client->selectDB($options['db']);

		// neil
		$neilData = $this->getUserData( array(
			'name' => 'Neil',
			'email' => 'neil@example.com',
		) );
		$this->db->users->insert($neilData);

		// martyn
		$martynData = $this->getUserData( array(
			'friend' => \MongoDBRef::create('users', $neilData['_id']),
		) );
		$this->db->users->insert($martynData);
	}

	public function teardown()
	{
		// remove fixtures
		$this->db->users->remove();
	}

	public function testFindReturnsArrayOfInstances()
    {
		$query = array(
			'email' => 'martyn@example.com',
		);

		$result = $this->user->find($query);

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

		$result = $this->user->findOne($query);

		// assertions

		$this->assertTrue($result instanceof UserIntegrated);
		$this->assertEquals($result->email, $query['email']);
    }

	public function testDBRefPropertySetAsMongoObject()
    {
		$query = array(
			'email' => 'martyn@example.com',
		);

		$user = $this->user->findOne($query);

		// assertions

		$this->assertTrue($user instanceof UserIntegrated);
		$this->assertTrue($user->friend instanceof UserIntegrated);
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

		$result = $this->user->findOne($query);

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

		$result = $this->user->findOne($query);

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

	protected function getUserData($data=array())
	{
		return array_merge(array(
			'name' => 'Martyn',
			'email' => 'martyn@example.com',
			'enabled' => 1,
		), $data);
	}
}
