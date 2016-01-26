<?php

// TODO test set can accept an array too

require_once 'MongoTestAbstract.php';

class MongoAttachTest extends MongoTestAbstract
{
	public function testPushWithSingleValueNotEachAndHasMongoIdConvertsToPushUpdate()
    {
		// the return value from the find
		$collectionName = 'users';

		$user = new UserUnit();
		$user->_id = new \MongoId();

		$query = array(
			'_id' => $user->_id,
		);

		$options = array(
			'each' => false,
		);

		$this->connectionMock
			->expects( $this->once() )
			->method('update')
			->with( $collectionName, $query, array(
				'$push' => array(
					'photo_ids' => 1,
				),
				'updated_at' => new \MongoDate(time()),
			), array() );


		$user->attach( array(
			'photo_ids' => 1,
		), array( 'each' => false ) );
    }

	public function testPushWhenIdMissingReturnsFalse()
    {
		// // the return value from the find
		// $collectionName = 'users';
		//
		// $user = new UserUnit();
		// $user->_id = new \MongoId();
		//
		// $query = array(
		// 	'_id' => $user->_id,
		// );
		//
		// $options = array(
		// 	'each' => false,
		// );
		//
		// $this->connectionMock
		// 	->expects( $this->once() )
		// 	->method('update')
		// 	->with( $collectionName, $query, array(
		// 		'$push' => array(
		// 			'photo_ids' => 1,
		// 		),
		// 		'updated_at' => new \MongoDate(time()),
		// 	), array() );
		//
		//
		// $user->attach( array(
		// 	'photo_ids' => 1,
		// ), array( 'each' => false ) );
    }
}
