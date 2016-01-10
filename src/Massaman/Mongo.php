<?php

namespace Massaman;

/**
 * Abstract class for creating mongo models
 **/

use Massaman\Mongo\Connection;
use Massaman\Mongo\Utils;
use Massaman\Mongo\Exception\WhitelistEmpty as WhitelistEmptyException;
use Massaman\Mongo\Exception\CollectionUndefined as CollectionUndefinedException;

abstract class Mongo
{
	/**
	 * @var string|MongoCollection
	 */
	protected $collection = '';

	/**
	 * @var string|MongoCollection
	 */
	protected $whitelist = array();

	/**
	 * This is the data of the document being represented
	 * @var array
	 */
	protected $data = array();

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
		$this->data = array_merge($this->data, $data);
	}

	/**
	 * Get the object properties in $data
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		// check if the developer has created a custom accessor here
		$method = 'get' . Utils::snakeCaseToCamelCase($name);
		if (method_exists($this, $method)) {
			return $this->$method();
		}

		// first check $updated, then $data
		if (isset($this->updated[$name])) {
			$value = &$this->updated[$name];
		} else {
			$value = &$this->data[$name];
		}

		// if value of $name is a dbref, then convert it to it's object and then
		// store the object as the new value of $name (so no need to load again)
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
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		// check if the developer has created a custom accessor here
		$method = 'set' . Utils::snakeCaseToCamelCase($name);
		if (method_exists($this, $method)) {
			return $this->$method($value);
		}

		// set the update property so it knows what fields have changed
		$this->updated[$name] = $value;

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

		// filter $data against $whitelist
		// it goes here as we only want to protect against saving to db, we still want to
		// be able to load protected properties eg. is_admin
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

			// insert
			$result = Connection::getInstance()->insert($this->collection, $values);

			// if _id set, set it to $data
			if (isset($values['_id'])) {
				$this->data['_id'] = $values['_id'];
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
	public function delete($query, $options=array())
	{
		if (! isset($this->data['_id'])) {
			return false;
		}

		return Connection::getInstance()->delete($this->collection, $query, $options);
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
