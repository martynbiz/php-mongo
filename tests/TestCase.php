<?php

use MongoDB\BSON\ObjectID;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
	protected function getUserData($data=array(), $withId=true)
	{
		// set mongoid
		if ($withId) $data = array_merge(array(
				'_id' => new ObjectID(),
			), $data);

		return array_merge(array(
			'name' => 'Martyn Bissett',
			'first_name' => 'Martyn',
			'last_name' => 'Bissett',
			'email' => 'martyn@example.com',
		), $data);
	}

	protected function getTagData($data=array(), $withId=true)
	{
		// set mongoid
		if ($withId) $data = array_merge(array(
				'_id' => new ObjectID(),
			), $data);

		return  array_merge(array(
			'name' => 'Cooking',
			'slug' => 'cooking',
		), $data);
	}
}
