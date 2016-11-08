<?php

namespace MartynBiz\Mongo;

use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectID;
use MongoDB\Database;

use MartynBiz\Mongo\Traits\Singleton as SingletonTrait;

/**
 * This is the mongo db connection singleton instance class. It is only concerned
 * with accessing the mongo db and working with array data, no object creation here
 */
class Connection
{
    use SingletonTrait;

//     /**
// 	 * @var MongoClient
// 	 */
//     private $client;

	/**
	 * @var MongoDB
	 */
    private $db;

// 	/**
// 	 * Options e.g. database, user, password, etc
// 	 * @var array
// 	 */
// 	private $options = array(
//         'classmap' => array(),
//     );

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
        $this->client = new MongoClient($uri, $mongoOptions);

        return $this;
	}

	/**
	 * Get the collection instance, for internal use. Only creates the MongoCollection
	 * when called upon.
	 * @return MongoDB
	 */
	public function getDatabase()
	{
		if (!$this->db instanceof Database) {
			$this->db = $this->client->selectDatabase( $this->options['db'] );
		}

		return $this->db;
	}

	/**
	 * Database can be set here, also useful for running tests (mocking)
	 * @param MongoDB
	 * @return void
	 */
	public function setDatabase(Database $db)
	{
		$this->db = $db;
	}

	/**
     * Find by query from a given collection
     * @param string $collection
 	 * @param array $query
 	 * @param array $options
	 * @return array
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
            $values['_id'] = new ObjectID();

        // this is an auto-increment id
        if (! isset($values['id']))
            $values['id'] = $this->getNextSequence($collectionName);

        $collection = $this->getDatabase()->selectCollection($collectionName);

        $result = $collection->insertOne($values);

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
        // default options
        $options = array_merge(array(
            'multi' => false,
        ), $options);

        $collection = $this->getDatabase()->selectCollection($collectionName);

        if ($options['multi']) {
            return $collection->updateMany($query, $values);
        } else {
            return $collection->updateOne($query, $values);
        }
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
        // default options
        $options = array_merge(array(
            'multi' => false,
        ), $options);

        $collection = $this->getDatabase()->selectCollection($collectionName);

        if ($options['multi']) {
            return $collection->deleteMany($query);
        } else {
            return $collection->deleteOne($query);
        }
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

        $collection->updateOne(
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

        // $sequence = $collection->findAndModify(
        //     array('_id' => $name),
        //     array('$inc' => array("seq" => 1)),
        //     null,
        //     array(
        //         "new" => true,
        //     )
        // );

//         // fetch from the sequences collection
//         $sequences = $this->getDatabase()->selectCollection('sequences')->findOne();
//
//         // if not found, create new
//         if (is_null($sequences)) { // document not found, insert
//
//             $sequences = array(
//                 $collectionName => 1,
//             );
//
//             $this->getDatabase()->selectCollection('sequences')->insert($sequences);
//
//         } elseif (! isset($sequences[$collectionName])) { // document found, but collection name missing
// var_dump($sequences); exit;
//             $query = array(
//                 '_id' => $sequences['_id'],
//             );
//
//             $sequences = array(
//                 $collectionName => 1,
//             );
//
//             $this->getDatabase()->selectCollection('sequences')->update($query, $sequences);
//
//         }

        return $sequence[$name];
    }
}
