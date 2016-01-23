<?php
// TODO create method
// TODO mongo - how to handle created_at, updated_at
// TODO mongo - soft deletes, deleted_at
// TODO paginate
// TODO access properties like: $user['username'] or $user->username
// TODO $article = $this->get('model.article')->create(...); // we can mock this

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
	protected static $collection = '';

	/**
	 * @var array
	 */
	protected static $whitelist = array();

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

	// /**
	//  * When new values are written, this will flag that the object is unsaved
	//  * Also, prevents us hitting the database when there is nothing updated
	//  * @var array
	//  */
	// protected $isDirty = false;

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
		if (empty(static::$collection)) {
			throw new CollectionUndefinedException;
		}

		// filter $data against $whitelist
		static::filterValues($data);

		// // store the data in the data property
		// $this->updated = array_merge($this->data, $data);

		// Merge $data with the protected $data
		$this->data = array_merge($this->data, $data); //$this->updated);
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
		// get the value from $data
		$value = $this->data[$name];

		// TODO what if ref'd item changes? we're not caching right? needs tested
		// if value of $name is a dbref, then convert it to it's object
		if (\MongoDBRef::isRef($value)) {

			// get the dbref item
			$data = Connection::getInstance()->findOne($value['$ref'], array(
				'_id' => $value['$id'],
			));

			// we have the data for the ref, but connection has the classmap
			$className = Connection::getInstance()->getCollectionClassNameFromClassMap($value['$ref']);
			$value = new $className();

			// as some data might not be present in the whitelist, we need to directly
			// assign
			foreach ($data as $k => $v) {
				$value->$k = $v;
			}

			// cache the new object so we don't have to do this every time we fetch a
			// property from this dbref
			$this->data[$name] = $value;
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
			$this->data = array_merge($this->updated, $name);
		} else {
			// set the update property so it knows what fields have changed
			$this->data[$name] = $value;
		}
	}

	/**
	 * @param array $query
	 * @param array $options
	 * @return array
	 */
	public static function find($query=array(), $options=array())
	{
		$result = Connection::getInstance()->find(static::$collection, $query, $options);

		// if result from find is null, return empty array
		if (! $result) {
			return array();
		}

		// built array of objects
		$return = array();
		foreach($result as $data) {
			array_push($return, static::createObjectFromDataArray($data));
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
	public static function findOne($query=array(), $options=array())
	{
		$result = Connection::getInstance()->findOne(static::$collection, $query, $options);

		// if result from findOne is null, return null
		if (! $result) {
			return null;
		}

		return static::createObjectFromDataArray($result);
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
			$this->errors = array_merge($this->errors, $error);
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
	 * Create on the fly, save, and return the new object. Alternative way to insert
	 * a new document (testable too)
	 * @param array $data Data can also by save by passing into this method
	 * @return Mongo Newly inserted object
	 */
	public static function create($data=array())
	{
		$className = get_called_class();
		$obj = new $className($data);
		$obj->save();
		return $obj;
	}

	/**
	 * Create on the fly, and return the new object (no insert). Alternative way to instantiate
	 * an object (testable too)
	 * @param array $data Data can also by save by passing into this method
	 * @return Mongo Newly instatiated object
	 */
	public static function factory($data=array())
	{
		$className = get_called_class();
		$obj = new $className($data);
		return $obj;
	}

	/**
	 * Save an object's data to the database (insert or update)
	 * @param array $data Data can also by save by passing into this method
	 */
	public function save($data=array())
	{
		// check if a $whitelist has been defined, if not throw Exception
		if (empty(static::$whitelist)) {
			throw new WhitelistEmptyException;
		}

		// filter $data against $whitelist
		$this->filterValues($data);

		// merge passed in values too
		$this->data = array_merge($this->data, $data);

		// call valdidate method, this may be a user defined validate method
		// by default though, this will return true and never really interfere
		if (! $this->validate() ) {
			return false;
		}

		// These are the values we'll save with
		$values = $this->data;

		// loop through each $value and check if we need to convert
		// objects to dbrefs
		foreach($values as &$value) {
			if ($value instanceof Mongo) {
				$value = $value->getDBRef();
			}
		}
// var_dump($values); exit;
		// determine whether this is an insert or update
		if (isset($this->data['_id'])) {

			// append created_at date
			$values = array_merge($values, array(
				'updated_at' => new \MongoDate(time()),
			));
			unset($values['_id']); // don't need this

			$options = array(
				'multi' => false, // only update one, don't spend looking for others
			);

			// update
			$result = Connection::getInstance()->update(static::$collection, array(
				'_id' => $this->data['_id'],
			), $values, $options);

		} else {

			// append created_at date
			$values = array_merge($values, array(
				'created_at' => new \MongoDate(time()),
			));

			// insert - will return _id for us too
			$result = Connection::getInstance()->insert(static::$collection, $values);

		}

		// merge values into data - if insert, will add id and _id
		$this->data = array_merge($this->data, $values);

		// // reset updated as data has been written
		// $this->updated = array();

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

		return Connection::getInstance()->delete(static::$collection, array(
			'_id' => $this->data['_id'],
		), array(
			'justOne' => true,
		));
	}

	/**
	 * @param array $query
	 */
	public static function remove($query, $options)
	{
		return Connection::getInstance()->delete(static::$collection, $query, $options);
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
		return \MongoDBRef::create(static::$collection, $this->_id);
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
			} elseif ($value instanceof Mongo) {
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
	protected static function createObjectFromDataArray($data=array())
	{
		// we'll loop here because we don't want $data to be filtered (as this
		// has come from the database)

		$obj = new static();

		foreach($data as $key => $value) {
			$obj->$key = $value;
		}

		return $obj;
	}

	/**
	 * Will convert an array of data from mongo to a Mongo instance object
	 * Will also convert any DBRefs to instances of their objects
	 * @param array $data
	 * @return Mongo
	 */
	protected static function filterValues(&$data)
	{
		$data = array_intersect_key($data, array_flip(static::$whitelist));
	}
}

?>
