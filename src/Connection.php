<?php

namespace MartynBiz\Mongo;

use MartynBiz\Mongo\Traits\Singleton as SingletonTrait;

/**
 * This is the mongo db connection singleton instance class. It is only concerned
 * with accessing the mongo db and working with array data, no object creation here
 */
class Connection
{
    use SingletonTrait;

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
	private $options = [];

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
        if (isset($options['socket'])) {
            $uri = 'mongodb://' . $options['socket'];
        } elseif (isset($options['host']) and isset($options['port'])) {
            $uri = 'mongodb://' . $options['host'] . ':' . $options['port'];
        }

        // prepare the options for this instance
        $this->options = array_intersect_key($options, array_flip( array(
            'db',
            'classmap',
        ) ));

        // ensure an empty classmap exists
        $this->options = array_merge([
            'classmap' => [],
        ], $this->options);

        // prepare the options for
        $mongoOptions = array_intersect_key($options, array_flip( array(
            'connect',
            'username',
            'password',
            'db',
        ) ));

        // $this->client = new \MongoClient($uri, $mongoOptions);
        $this->client = new \MongoClient($uri, $mongoOptions);

        return $this;
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
			$this->db = $this->client->selectDB( $this->options['db'] );
		}

		return $this->db;
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

        // MongoCollection::find(): expects parameter 2 to be an array or object
        $fields = isset($options['fields']) ? $options['fields'] : array();

        $result = $collection->find($query, $fields);

        // limit if set TODO test this?
        if (isset($options['limit'])) {
            $result = $result->limit((int) $options['limit']);
        }

        // skip if set
        if (isset($options['skip'])) {
            $result = $result->skip((int) $options['skip']);
        }

        // $total = $result->count();

        return $result;
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
        // our hash mongo id, this will be set to
        if (! isset($values['_id']))
            $values['_id'] = new \MongoId();

        // this is an auto-increment id
        if (! isset($values['id']))
            $values['id'] = $this->getNextSequence($collectionName);

        $collection = $this->getDatabase()->selectCollection($collectionName);

        $result = $collection->insert($values);

        return $result;
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

        return $collection->remove($query, $options);
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
     * @see https://docs.mongodb.org/manual/tutorial/create-an-auto-incrementing-field/
     */
    public function getNextSequence($name) {

        $collection = $this->getDatabase()->selectCollection('sequences');

        $collection->update(
            array(
                $name => array('$exists' => true),
            ),
            array(
                '$inc' => array($name => 1)
            ),
            array(
                'upsert' => true,
            )
        );

        $sequence = $collection->findOne(array(
            $name => array(
                '$exists' => true,
            ),
        ));

        return $sequence[$name];
    }

    /**
     * Get the classmap
     * @return array
     */
    public function getClassMap()
    {
        return $this->options['classmap'];
    }

    /**
     * Get the classmap
     * @param array $classmap
     */
    public function setClassMap($classmap)
    {
        $this->options['classmap'] = $classmap;
    }

    /**
     * Append more classmaps to the existing classmap
     * @param array $classmap
     */
    public function appendClassMap($classmap=array())
    {
        $this->options['classmap'] = array_merge($this->options['classmap'], $classmap);
    }
}
