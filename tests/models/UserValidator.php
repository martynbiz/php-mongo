<?php

use MartynBiz\Mongo\Mongo;

/**
 * UserUnit class to unit test abstract Mongo methods
 */
class UserValidator extends Mongo
{
	/**
	 * @var string
	 */
	protected static $collection = 'users';

	/**
	 * @var string
	 */
	protected static $whitelist = array(
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
