<?php

namespace MartynBiz\Mongo;

use MartynBiz\Mongo\SingletonTrait;

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
	private $options = array(
        'classmap' => array(),
    );

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

        // prepare the options for
        $mongoOptions = array_intersect_key($options, array_flip( array(
            'connect',
            'username',
            'password',
            'db',
        ) ));

        // $this->client = new \MongoClient($uri, $mongoOptions);
        $this->client = new \MongoClient($uri, $mongoOptions);
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
    public function getNextSequence($collectionName) {

        // fetch from the sequences collection
        $sequences = $this->getDatabase()->selectCollection('sequences')->findOne();

        // if not found, create new
        if (is_null($sequences)) { // document not found, insert

            $sequences = array(
                $collectionName => 1,
            );

            $this->getDatabase()->selectCollection('sequences')->insert($sequences);

        } elseif (! isset($sequences[$collectionName])) { // document found, but collection name missing

            $query = array(
                '_id' => $sequences['_id'],
            );

            $sequences = array(
                $collectionName => 1,
            );

            $this->getDatabase()->selectCollection('sequences')->update($query, $sequences);

        }

        return $sequences[$collectionName];
    }
}
