<?php

namespace MartynBiz;

/**
 * Abstract class for creating mongo models
 */

use MartynBiz\Mongo\Connection;
use MartynBiz\Mongo\Utils;
use MartynBiz\Mongo\MongoIterator;
use MartynBiz\Mongo\Exception\WhitelistEmpty as WhitelistEmptyException;
use MartynBiz\Mongo\Exception\CollectionUndefined as CollectionUndefinedException;
use MartynBiz\Mongo\Exception\NotFound as NotFoundException;

abstract class Mongo
{
	/**
	 * @var string
	 */
	protected $collection = '';

	/**
	 * @var array
	 */
	protected $whitelist = array();

	/**
	 * This is the data of the document being represented
	 * @var array
	 */
	protected $data = array();

	/**
	 * The errors from validate (or by calls to setError)
	 * @var array
	 */
	protected $errors = array();

	/**
	 * This is the updated data not yet saved. We store updated values in
	 * a seperate array so that we only need to update those values in the
	 * collection when we save
	 * @var array
	 */
	protected $updated = array();

	/**
	 * Options e.g. database, user, password, etc
	 * @var array
	 */
	protected $options = array();

	/**
	 * @param MongoClient $client
	 * @return void
	 */
	public function __construct($data=array())
	{
		// check if a $collection has been set
		if (empty($this->collection)) {
			throw new CollectionUndefinedException;
		}

		// store the data in the data property
		$this->updated = array_merge($this->data, $data);

		// ensure that data is also refelctive of $updated
		$this->data = array_merge($this->data, $this->updated);
	}

	/**
	 * Get the object properties in $data
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * Get the object properties in $data
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * Get the object properties in $data
	 * @param string $name
	 * @return mixed
	 */
	public function get($name)
	{
		// first check $updated, then $data
		if (isset($this->updated[$name])) {
			$value = $this->updated[$name];
		} else {
			$value = $this->data[$name];
		}

		// TODO what if ref'd item changes? we're not caching right? needs tested
		// if value of $name is a dbref, then convert it to it's object
		if (\MongoDBRef::isRef($value)) {

			// get the dbref item
			$data = Connection::getInstance()->findOne($value['$ref'], array(
				'_id' => $value['$id'],
			));

			// we have the data for the ref, but connection has the classmap
			$class = Connection::getInstance()->getCollectionClassNameFromClassMap($value['$ref']);
			$value = new $class($data);
		}

		return $value;
	}

	/**
	 * Get the object properties in $data
	 * Useful in controllers to have a method that accepts an array
	 * @param string|array $name Name of item, or name/value array
	 * @param mixed $value
	 */
	public function set($name, $value=null)
	{
		if (is_array($name)) {
			$this->updated = array_merge($this->updated, $name);
		} else {
			// set the update property so it knows what fields have changed
			$this->updated[$name] = $value;
		}

		// ensure that data is also refelctive of $updated
		$this->data = array_merge($this->data, $this->updated);
	}

	/**
	 * @param array $query
	 * @param array $options
	 * @return array
	 */
	public function find($query=array(), $options=array())
	{
		$result = Connection::getInstance()->find($this->collection, $query, $options);

		// if result from find is null, return empty array
		if (! $result) {
			return array();
		}

		// built array of objects
		$return = array();
		foreach($result as $data) {
			array_push($return, $this->createObjectFromDataArray($data));
		}

		// create iterator from $return
		$return = new MongoIterator($return);

		return $return;
	}

	/**
	 * @param array $query
	 * @return array $options
	 * @return Mongo
	 */
	public function findOne($query=array(), $options=array())
	{
		$result = Connection::getInstance()->findOne($this->collection, $query, $options);

		// if result from findOne is null, return null
		if (! $result) {
			return null;
		}

		return $this->createObjectFromDataArray($result);
	}

	/**
	 * Similar to findOne, but will throw an NotFoundException if not found
	 * @param array $query
	 * @param array $options
	 * @return Mongo
	 */
	public function findOneOrFail($query=array(), $options=array())
	{
		$result = $this->findOne($query, $options);

		// if result from findOne is null, return null
		if (! $result) {
			throw new NotFoundException;
		}

		return $result;
	}

	/**
	 * This is the empty validate method, each model will defined their own
	 * it is called during save
	 * @return boolean
	 */
	public function validate()
	{
		// first, reset errors for this validation check
		$this->resetErrors();

		// validation code here
		// e.g. if empty($this->data['name']) $this->setError('Name missing');

		// return true if errors is empty
		return empty( $this->getErrors() );
	}

	/**
	 * Set push string error message or merge array error message
	 * @param sting|array
	 */
	public function setError($error)
	{
		if (is_array($error)) {
			array_merge($this->errors, $error);
		} else {
			array_push($this->errors, $error);
		}
	}

	/**
	 * Set push string error message or merge array error message
	 * @param sting|array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Set push string error message or merge array error message
	 * @param sting|array
	 */
	public function resetErrors()
	{
		$this->errors = array();
	}

	/**
	 * Save an object's data to the database (insert or update)
	 * @param array $data Data can also by save by passing into this method
	 */
	public function save($data=array())
	{
		// check if a $whitelist has been defined, if not throw Exception
		if (empty($this->whitelist)) {
			throw new WhitelistEmptyException;
		}

		// merge passed in values too
		$this->updated = array_merge($this->updated, $data);

		// call valdidate method, this may be a user defined validate method
		// by default though, this will return true and never really interfere
		if (! $this->validate() ) {
			return false;
		}

		// filter $data against $whitelist
		// it goes here as we only want to protect against saving to db, we still want to
		// be able to load protected properties eg. is_admin
		// This should come after validate() as we mayb want to validate additional
		// params within this model prior to saving, even if they don't make it to the db
		$this->filterValues($this->updated);

		// if nothing to update, return false
		if (empty($this->updated)) {
			return false;
		}

		// set updated as we'll pass by referrence and it upsets out tests
		// when $this->updated is reset
		$values = $this->updated;

		// loop through each $value and check if we need to convert
		// objects to dbrefs
		foreach($values as &$value) {
			if ($value instanceof $this) {
				$value = $value->getDBRef();
			}
		}

		// determine whether this is an insert or update
		if (isset($this->data['_id'])) {

			$options = array(
				'multi' => false, // only update one, don't spend looking for others
			);

			// update
			$result = Connection::getInstance()->update($this->collection, array(
				'_id' => $this->data['_id'],
			), $values, $options);

		} else {

			// insert - will return _id for us too
			$result = Connection::getInstance()->insert($this->collection, $values);

			// merge any newly set values (e.g. id, _id)
			if (isset($values['id'])) {
				$this->data['id'] = $values['id'];
			}

		}

		// reset updated
		$this->updated = array();

		return $result;
	}

	/**
	 * @param array $query
	 * @return array $options
	 * @return Mongo
	 */
	public function delete()
	{
		if (! isset($this->data['_id'])) {
			return false;
		}

		return Connection::getInstance()->delete($this->collection, array(
			'_id' => $this->data['_id'],
		), array(
			'justOne' => true,
		));
	}

	/**
	 * Create a dbref of this object if required
	 * @return MongoDBRef
	 */
	public function getConnection()
	{
		return Connection::getInstance();
	}

	/**
	 * Create a dbref of this object if required
	 * @return MongoDBRef
	 */
	public function getDBRef()
	{
		return \MongoDBRef::create($this->collection, $this->_id);
	}

	/**
	 * Will convert objects to arrays
 	 * @param int $deep How deep to recursively convert to arrays
 	 * @param mixed $values Used by the class when recursively converting arrays
	 * @return array
	 */
	public function toArray($deep=2, $values=null)
	{
		if (is_null($values)) {
			$values = $this->data;
		}

		// look for
		foreach ($values as &$value) {
			if ($value instanceof \MongoId) {
				$value = $value->__toString();
			} elseif ($value instanceof $this) {
				if ($deep > 0) {
					$value = $value->toArray( $deep-1 );
				} else {
					$value = '...';
				}
			} elseif (\MongoDBRef::isRef($value)) {
				if ($deep > 0) {
					$value = Connection::getInstance()->findOne($value['$ref'], array(
						'_id' => $value['$id'],
					));
				} else {
					$value = '...';
				}
			} elseif (is_array($value)) {
				$value = $this->toArray($deep, $value);
			}
		}

		return $values;
	}

	/**
	 * Will convert an array of data from mongo to a Mongo instance object
	 * Will also convert any DBRefs to instances of their objects
	 * @param array $data
	 * @return Mongo
	 */
	protected function createObjectFromDataArray($data=array())
	{
		return new static($data);
	}

	/**
	 * Will convert an array of data from mongo to a Mongo instance object
	 * Will also convert any DBRefs to instances of their objects
	 * @param array $data
	 * @return Mongo
	 */
	protected function filterValues(&$data)
	{
		$data = array_intersect_key($data, array_flip($this->whitelist));
	}
}

?>