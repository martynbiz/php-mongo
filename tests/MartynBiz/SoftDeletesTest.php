<?php

// TODO test when app sets id and _id for insert
// TODO test set can accept an array too

use MartynBiz\Mongo;
use MartynBiz\Mongo\Connection;
use MartynBiz\Mongo\Traits\SoftDeletes;

/**
 * UserUnit class to unit test abstract Mongo methods
 */
class SoftDeletesUser extends Mongo
{
	use SoftDeletes;

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
		'friend'
	);
}

class SoftDeletesTest extends PHPUnit_Framework_TestCase
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
	}

	public function testDeleteCallsUpdateWithDeletedAt()
    {
		// the return value from the find
		$collectionName = 'users';

		$userData = $this->getUserData();

		$query = array(
			'_id' => $userData['_id'],
		);

		$values = array(
			'deleted_at' => new \MongoDate(time()),
		);

		$options = array(
			'justOne' => true,
		);

		$this->connectionMock
			->expects( $this->once() )
			->method('update')
			->with( $collectionName, $query, $values );

		$user = new SoftDeletesUser($userData);
		$user->delete();

		$this->assertEquals($values['deleted_at'], $user->deleted_at);
    }

	public function testDeleteReturnsFalseWhenIdNotSet()
    {
		// the return value from the find
		$collectionName = 'users';

		$userData = $this->getUserData();
		unset($userData['_id']);

		$this->connectionMock
			->expects( $this->never() )
			->method('update');

		$user = new SoftDeletesUser($userData);
		$user->delete();
    }

	public function testFindExcludesSoftDeletedResults()
    {
		$collectionName = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// mock method to return mock collection
		$this->connectionMock
			->expects( $this->once() )
			->method('find')
			->with($collectionName, array_merge($query, array(
				'deleted_at' => array(
	                '$exists' => false,
	            ),
			)), $options);

		$users = (new SoftDeletesUser())->find($query, $options);
    }

	public function testFindOneExcludesSoftDeletedResults()
    {
		$collectionName = 'users';

		$query = array(
			'email' => 'martyn@example.com',
		);

		$options = array();

		// mock method to return mock collection
		$this->connectionMock
			->expects( $this->once() )
			->method('findOne')
			->with($collectionName, array_merge($query, array(
				'deleted_at' => array(
	                '$exists' => false,
	            ),
			)), $options);

		$user = (new SoftDeletesUser())->findOne($query, $options);
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
