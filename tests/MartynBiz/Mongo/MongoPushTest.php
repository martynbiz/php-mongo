<?php

// !!! TODO we also set the new pushed property after writing
// Do some integrated tests for this

require_once 'MongoTestAbstract.php';

use MartynBiz\Mongo\Exception\MissingId;
use MartynBiz\Mongo\MongoIterator;

class MongoPushTest extends MongoTestAbstract
{
	public function testPushWithSingleValueAndHasMongoIdConvertsToPushUpdate()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->at(0) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'photo_ids' => array(
						'$each' => array(
							1,
						)
					),
				),
			), array() );

		$this->connectionMock
			->expects( $this->at(1) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'updated_at' => new \MongoDate(time()),
			), array() );


		$user->push( array(
			'photo_ids' => 1,
		) );
    }

	public function testPushWithArrayValueAndHasMongoIdConvertsToPushUpdate()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->at(0) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'photo_ids' => array(
						'$each' => array(
							1, 2, 3,
						)
					),
				),
			), array() );

		$this->connectionMock
			->expects( $this->at(1) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->push( array(
			'photo_ids' => array(
				1, 2, 3,
			),
		) );
    }

	public function testPushWithMongoValueConvertsToPushUpdateWithDBRef()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$friend = new UserUnit();
		$friend->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->at(0) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'friends' => array(
						'$each' => array(
							$friend->getDBRef(),
						),
					),
				),
			), array() );

		$this->connectionMock
			->expects( $this->at(1) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->push( array(
			'friends' => $friend,
		) );
    }

	public function testPushMultiplePropertiesWithMongoValueConvertsToPushUpdateWithDBRef()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$friend = new UserUnit();
		$friend->_id = new \MongoId();

		$enemy = new UserUnit();
		$enemy->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->at(0) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'friends' => array(
						'$each' => array(
							$friend->getDBRef(),
						),
					),
					'enemies' => array(
						'$each' => array(
							$friend->getDBRef(),
						),
					),
				),
			), array() );

		$this->connectionMock
			->expects( $this->at(1) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->push( array(
			'friends' => $friend,
			'enemies' => $friend,
		) );
    }

	public function testPushWithMongoArrayValueConvertsToPushUpdateWithDBRef()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$friend = new UserUnit();
		$friend->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->at(0) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'friends' => array(
						'$each' => array(
							$friend->getDBRef(),
						)
					),
				),
			), array() );

		$this->connectionMock
			->expects( $this->at(1) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->push( array(
			'friends' => array(
				$friend,
			),
		) );
    }

	public function testPushWithMongoIteratorValueConvertsToPushUpdateWithDBRef()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$friend = new UserUnit();
		$friend->_id = new \MongoId();

		$friends = new MongoIterator(array(
			$friend,
		));

		$this->connectionMock
			->expects( $this->at(0) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'friends' => array(
						'$each' => array(
							$friend->getDBRef(),
						)
					),
				),
			), array() );

		$this->connectionMock
			->expects( $this->at(1) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->push( array(
			'friends' => $friends,
		) );
    }

	public function testPushWithArrayValueAndHasMongoIdConvertsToPushUpdateWhenEachIsFalse()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->at(0) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'photo_ids' => array(
						1, 2, 3,
					),
				),
			), array() );

		$this->connectionMock
			->expects( $this->at(1) )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->push( array(
			'photo_ids' => array(
				1, 2, 3,
			),
		), array(
			'each' => false,
		));
    }

	/**
	 * @expectedException MartynBiz\Mongo\Exception\MissingId
	 */
	public function testPushWhenIdMissingThrowException()
    {
		$user = new UserUnit();
		// $user->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->never() )
			->method('update');

		$user->push( array(
			'photo_ids' => 1,
		), array( 'each' => false ) );
    }
}
