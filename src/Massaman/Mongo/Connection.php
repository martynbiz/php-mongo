<?php

namespace Massaman\Mongo;

/**
 * This is the mongo db connection singleton instance class. It is only concerned
 * with accessing the mongo db and working with array data, no object creation here
 */
class Connection
{
    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;

	/**
	 * @var MongoClient
	 */
    private $client;

	/**
	 * @var MongoDB
	 */
    private $db;

	/**
	 * Options e.g. database, user, password, etc
	 * @var array
	 */
	private $options = array(
        'classmap' => array(),
    );

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Allows the instance to be swapped during tests
	 * @param Connection $instance New instance
     */
    public static function setInstance(Connection $instance)
    {
        static::$instance = $instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {

    }

	/**
	 * Database can be set here, also useful for running tests (mocking)
	 * @param MongoDB
	 * @return void
	 */
	public function setDatabase(\MongoDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Get the collection instance, for internal use. Only creates the MongoCollection
	 * when called upon.
	 * @return MongoDB
	 */
	public function getDatabase()
	{
		if (!$this->db instanceof \MongoDB) {
			$this->db = $this->client->selectDB( $this->options['dbname'] );
		}

		return $this->db;
	}

	/**
	 * Database can be set here, also useful for running tests (mocking)
	 * @param MongoDB
	 * @return void
	 */
	public function init($options)
	{
        // set defaults
        $options = array_merge(array(
            'host' => 'localhost',
            'port' => 27017,
            'connect' => true,
        ), $options);

        // build up the url
        $uri = 'mongodb://';
        $uri .= isset($options['socket']) ? $options['socket'] : $options['host'] . ':' . $options['port'];

        // prepare the options for this instance
        $this->options = array_intersect_key($options, array_flip( array(
            'db',
            'classmap',
        ) ));

        // prepare the options for
        $mOptions = array_intersect_key($options, array_flip( array(
            'connect',
            'username',
            'password',
            'db',
        ) ));

        $this->client = new \MongoClient($uri, $mOptions);
	}

	/**
     * Find by query from a given collection
     * @param string $collection
 	 * @param array $query
 	 * @param array $options
	 * @return void
	 */
	public function find($collection, $query=array(), $options=array())
	{
        $collection = $this->getDatabase()->selectCollection($collection);

        return $collection->find($query, $options);
	}

	/**
	 * @param string $collection
 	 * @param array $query
 	 * @param array $options
	 * @return void
	 */
	public function findOne($collection, $query=array(), $options=array())
	{
        $collection = $this->getDatabase()->selectCollection($collection);

        return $collection->findOne($query, $options);
	}

	/**
     * Insert new document to collection
 	 * @param string $collectionName
 	 * @param array &$values The _id will be assigned if all is well
	 * @return boolean?
	 */
	public function insert($collectionName, &$values)
	{
        // set the id auto-increment property
        $values['id'] = $this->getNextSequence($collectionName);

        $collection = $this->getDatabase()->selectCollection($collectionName);

        return $collection->insert($values);
	}

	/**
     * Update existing documents to collection
     * @param string $collectionName
 	 * @param array $query
 	 * @param array $values
 	 * @param array $options
	 * @return boolean?
	 */
	public function update($collectionName, $query, $values, $options=array())
	{
        // return false if nothing to update
        if (empty($values)) {
            return false;
        }

        $collection = $this->getDatabase()->selectCollection($collectionName);

        return $collection->update($query, $values, $options);
	}

	/**
     * Update existing documents to collection
     * @param string $collectionName
 	 * @param array $query
 	 * @param array $options
	 * @return boolean?
	 */
	public function delete($collectionName, $query, $options=array())
	{
        $collection = $this->getDatabase()->selectCollection($collectionName);

        return $collection->delete($query, $options);
	}

	/**
	 * Get the collection class name
 	 * @param string $collection
	 * @return string
	 */
	public function getCollectionClassNameFromClassMap($collection)
	{
        // get the class from the classmap, throw an exception if not set
        if (isset($this->options['classmap'][$collection])) {
            $class = $this->options['classmap'][$collection];
        } else {
            throw new \Exception('Class not found in classmap for "' . $collection . '"');
        }

        return $class;
	}

    /**
     * For creating a auto-increment sequence number
     * @param string $collectionName Collection to get next counter for
     * @return int
     */
    public function getNextSequence($collectionName) {

        $collection = $this->getDatabase()->selectCollection('counters');

        $counter = $collection->findAndModify( array(
            'query' => array(
                '_id' => $collectionName
            ),
            'update' => array(
                '$inc' => array(
                    'seq' => 1
                ),
            ),
            'new' => true,
        ) );

        return $counter['seq'];
    }
}
