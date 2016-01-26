<?php

// TODO test set can accept an array too

require_once 'MongoTestAbstract.php';

use MartynBiz\Mongo\Exception\MissingId;
use MartynBiz\Mongo\MongoIterator;

class MongoAttachTest extends MongoTestAbstract
{
	public function testPushWithSingleValueAndHasMongoIdConvertsToPushUpdate()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->once() )
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
				'updated_at' => new \MongoDate(time()),
			), array() );


		$user->attach( array(
			'photo_ids' => 1,
		) );
    }

	public function testPushWithArrayValueAndHasMongoIdConvertsToPushUpdate()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->once() )
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
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->attach( array(
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
			->expects( $this->once() )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'friends' => array(
						'$each' => array(
							$friend->getDBRef()
						),
					),
				),
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->attach( array(
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
			->expects( $this->once() )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'friends' => array(
						'$each' => array(
							$friend->getDBRef()
						),
					),
					'enemies' => array(
						'$each' => array(
							$friend->getDBRef()
						),
					),
				),
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->attach( array(
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
			->expects( $this->once() )
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
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->attach( array(
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
			->expects( $this->once() )
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
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->attach( array(
			'friends' => $friends,
		) );
    }

	public function testPushWithArrayValueAndHasMongoIdConvertsToPushUpdateWhenEachIsFalse()
    {
		// the return value from the find
		$user = new UserUnit();
		$user->_id = new \MongoId();

		$this->connectionMock
			->expects( $this->once() )
			->method('update')
			->with( 'users', array(
				'_id' => $user->_id,
			), array(
				'$push' => array(
					'photo_ids' => array(
						1, 2, 3,
					),
				),
				'updated_at' => new \MongoDate(time()),
			), array() );

		// attach
		$user->attach( array(
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

		$user->attach( array(
			'photo_ids' => 1,
		), array( 'each' => false ) );
    }
}
