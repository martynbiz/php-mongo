<?php
// TODO paginate
// TODO access properties like: $user['username'] or $user->username
// TODO test: findOne, find, load with Mongo and MongoIterator in $query
// TODO nested properties? e.g. 'model.name' - needs tested

namespace MartynBiz\Mongo;

/**
 * Abstract class for creating mongo models
 */

use MartynBiz\Mongo\Connection;
use MartynBiz\Mongo\Utils;
use MartynBiz\Mongo\MongoIterator;
use MartynBiz\Mongo\Exception\WhitelistEmpty as WhitelistEmptyException;
use MartynBiz\Mongo\Exception\CollectionUndefined as CollectionUndefinedException;
use MartynBiz\Mongo\Exception\NotFound as NotFoundException;
use MartynBiz\Mongo\Exception\MissingId as MissingIdException;

abstract class Mongo implements \ArrayAccess
{
	/**
	 * @var string
	 */
	protected static $conn = 'default';

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

		// Merge $data with the protected $data
		$this->data = array_merge($this->data, $data); //$this->updated);
	}

	public function offsetSet($name, $value) {
        $this->data[$name] = $value;
    }

    public function offsetGet($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function offsetExists($name) {
        return isset($this->data[$name]);
    }

    public function offsetUnset($name) {
        unset($this->data[$name]);
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
	 *
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * Get the object properties in $data
	 * @param string $name
	 * @return mixed
	 */
	public function get($name)
	{
		// get the value from $data
		$value = @$this->data[$name];

		// if value of $name is a dbref, then convert it to it's object
		if (\MongoDBRef::isRef($value)) {

			$dbref = $value;

			// we have the data for the ref, but connection has the classmap
			$className = Connection::getInstance(self::$conn)->getCollectionClassNameFromClassMap($dbref['$ref']);
			$value = new $className();

			$value->load(array(
				'_id' => $dbref['$id'],
			));

			// cache the new object so we don't have to do this every time we fetch a
			// property from this dbref
			$this->data[$name] = $value;
		}

		// if value is an array, it is possibly an array of DBRefs. In which case we
		// should convert to models
		if (is_array($value) and !\MongoDBRef::isRef($value)) {
			array_walk($value, function (&$item, $key) use ($name) {
				if (\MongoDBRef::isRef($item)) {
					$dbref = $item;

					// we have the data for the ref, but connection has the classmap
					$className = Connection::getInstance(self::$conn)->getCollectionClassNameFromClassMap($dbref['$ref']);

					$item = new $className();

					$item->load(array(
						'_id' => $dbref['$id'],
					));

					$this->data[$name][$key] = $item;
				}
			});
		}

		// check if a custom getter has been defined for this class
		$getterMethod = 'get' . Utils::snakeCaseToCamelCase($name);
		if (method_exists($this, $getterMethod)) {
			$value = $this->$getterMethod($value);
		}

		return $value;
	}

	/**
	 * Get the object properties in $data
	 * Useful in controllers to have a method that accepts an array
	 * @param string|array $data Name\value pairs to set, or can be string key
	 * @param mixed $value
	 */
	public function set($data, $value=null)
	{
		// as we're supporting arrays, we'll make even a name/value
		// pair an array just so that we can use the same code later
		if (!is_array($data)) {
			$data = array(
				$data => $value,
			);
		}

		foreach ($data as $name => $value) {

			// check if a custom getter has been defined for this class
			$setterMethod = 'set' . Utils::snakeCaseToCamelCase($name);
			if (method_exists($this, $setterMethod)) {
				$value = $this->$setterMethod($value);
			}

			// TODO can this be tidier, seems strange to pass $value twice (one for &ref, the other for value)
			// .. maybe try a Closure within the method to accomodate the &ref?
			$this->convertDotSyntaxNameToNestedArray($value, $name, $value);
// var_dump($value); exit;
			// set the update property so it knows what fields have changed
			$this->data = array_replace_recursive($this->data, $value);
		}
	}

	/**
	 * This will convert a dot syntax name such as 'model.name' to nested arrays
	 * @param mixed &$arr This is the value to be updated (converted to arrays)
	 * @param string $name The name, possible dot notation (e.g. "model.name.en")
	 * @param mixed $value This is the value of the last property in the dot notation (e.g. "en")
	 * @return null
	 */
	public function convertDotSyntaxNameToNestedArray(&$arr, $name, $value, $separator='.') {
		$arr = array();

		$keys = explode($separator, $name);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $arr = $value;
    }

	/**
	 * @param array $query
	 * @param array $options
	 * @return array
	 */
	public static function find($query=array(), $options=array())
	{
		// convert Mongo/ MongoIterator instances to dbrefs
		self::convertArrayItemsToDBRefs($query);

		$result = Connection::getInstance(self::$conn)->find(static::$collection, $query, $options);

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
		// convert Mongo/ MongoIterator instances to dbrefs
		self::convertArrayItemsToDBRefs($query);

		$result = Connection::getInstance(self::$conn)->findOne(static::$collection, $query, $options);

		// if result from findOne is null, return null
		if (! $result) {
			return null;
		}

		return static::createObjectFromDataArray($result);
	}

	/**
	 * Will load an objects values by query. Uses findOne with the query to load
	 * values.
	 * @param array $query
	 * @return Mongo
	 */
	public function load($query=array(), $options=array())
	{
		// convert Mongo/ MongoIterator instances to dbrefs
		self::convertArrayItemsToDBRefs($query);

		$result = Connection::getInstance(self::$conn)->findOne(static::$collection, $query, $options);

		// if result from findOne is null, return null
		if (! $result) {
			return null;
		}

		$this->data = $result;
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

		// convert Mongo/ MongoIterator instances to dbrefs
		self::convertArrayItemsToDBRefs($values);

		// determine whether this is an insert or update
		if (isset($this->data['_id'])) {

			// append updated_at date, also use $set so we don't overwrite
			// values
			$values = array(
				'$set' => array_merge($values, array(
					'updated_at' => new \MongoDate(time()),
				)),
			);
			unset($values['$set']['_id']); // don't need this

			$options = array(
				'multi' => false, // only update one, don't spend time looking for others
			);

			// update
			$result = Connection::getInstance(self::$conn)->update(static::$collection, array(
				'_id' => $this->data['_id'],
			), $values, $options);

		} else {

			// append created_at date
			$values = array_merge($values, array(
				'created_at' => new \MongoDate(time()),
			));

			// insert - will return _id for us too
			$result = Connection::getInstance(self::$conn)->insert(static::$collection, $values);

		}

		// merge values into data - if insert, will add id and _id
		$this->data = array_merge($this->data, $values);

		return $result;
	}

	/**
	 * Prepares the update for a $push, will convert Mongo and MongoIterators to
	 * their DBRefs
	 * @param array $data field/data pairs
	 */
	public function push($data, $options=array())
	{
		// default options (e.g. each=true)
		$options = array_merge(array(
			'each' => true,
		), $options);

		// check for _id
		if (! isset($this->data['_id'])) {
			throw new MissingIdException;
		}

		$update = array(
			'$push' => array(),
		);

		foreach ($data as $property => $value) {

			// if MongoIterator then build up an array of Mongo instances
			// eg. [ User, User, User ]
			if ($value instanceof MongoIterator) {
				$arr = array();
				foreach ($value as $i => $obj) {
					array_push($arr, $obj);
				}
				$value = $arr;
			}

			// if $value is not an array, convert it to one here as it makes
			// later simpler if we only have to expect one type (array)
			if (! is_array($value)) {
				$value = array($value);
			}

			// convert any Mongo elements to DBRefs. this will dig deep into
			// nested arrays and convert them
			array_walk_recursive($value, function (&$item, $key) {
				if ($item instanceof Mongo) {
						$item = $item->getDBRef(); // seems this needs to be an array??
				}
			});

			// if $each is set (default: true) then build the $each array
			if ($options['each']) {
				$value = array(
					'$each' => $value,
				);
			} else {
				$value = $value;
			}

			$update['$push'][$property] = $value;
		}

		$result = Connection::getInstance(self::$conn)->update(static::$collection, array(
			'_id' => $this->data['_id'],
		), $update);

		// now update updated_at coz we can't do that with the $push update
		Connection::getInstance(self::$conn)->update(static::$collection, array(
			'_id' => $this->data['_id'],
		), array(
			'$set' => array(
				'updated_at' => new \MongoDate( time() )
			),
		));

		// we can reload the document by calling it's own findOne
		$this->load( array(
			'_id' => $this->data['_id'],
		) );
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

		$query = array(
			'_id' => $this->data['_id'],
		);

		$options = array(
			'justOne' => true,
		);

		return Connection::getInstance(self::$conn)->delete(static::$collection, $query, $options);
	}

	/**
	 * Remove documents from collection by query
	 * @param array $query
	 * @param array $options
	 */
	public static function remove($query, $options=array())
	{
		return Connection::getInstance(self::$conn)->delete(static::$collection, $query, $options);
	}

	/**
	 * Create a dbref of this object if required
	 * @return MongoDBRef
	 */
	public function getConnection()
	{
		return Connection::getInstance(self::$conn);
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
		foreach ($values as $name => &$value) {
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
					$value = Connection::getInstance(self::$conn)->findOne($value['$ref'], array(
						'_id' => $value['$id'],
					));
				} else {
					$value = '...';
				}
			} elseif (is_array($value)) {
				$value = $this->toArray($deep, $value);
			} else {
				// run $value through get() as there may be some custom getter methods
				// as it may be a array or instances, we will skip if $name is not string
				if (is_string($name)) $value = $this->get($name);
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
	 * Will accept Mongo/ MongoIterator instances and convert them to DBRefs for find, findOne etc
	 * @param array $query
	 * @return mixed
	 */
	protected static function convertArrayItemsToDBRefs(&$query=array())
 	{
		foreach($query as &$value) {
			if ($value instanceof Mongo) {
				$value = $value->getDBRef();
			} elseif ($value instanceof MongoIterator) {
				$newValue = []; // we'll build up an array
				foreach($value as $model) {
					array_push($newValue, $model->getDBRef());
				}
				$value = $newValue;
			}
		}

		return $query;
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
