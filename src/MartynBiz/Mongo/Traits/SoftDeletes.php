<?php

namespace MartynBiz\Mongo\Traits;

use MartynBiz\Mongo\Connection;

/**
 * When included, will delete by "soft delete" (deleted_at)
 */
trait SoftDeletes
{
    /**
     * Override the delete method to update with deleted_at instead
     */
    public function delete()
    {
        // check _id is set
        if (!isset($this->data['_id'])) {
            return false;
        }

        $query = array(
            '_id' => $this->data['_id'],
        );

        // append deleted_at date
        $values = array(
            'deleted_at' => new \MongoDate(time()),
        );

        $options = array(
            'multi' => false, // only update one, don't spend looking for others
        );

        // update
        $result = Connection::getInstance()->update($this->collection, $query, $values, $options);

		// merge values into data - if insert, will add id and _id
		$this->data = array_merge($this->data, $values);

        return $result;
    }

    /**
     * Find with soft deletes means that we need to exclude (soft) "deleted" items
     */
     /**
 	 * @param array $query
 	 * @param array $options
 	 * @return array
 	 */
 	public function find($query=array(), $options=array())
 	{
        // attach deleted_at does not exist or deleted_at eq null
        $query = array_merge($query, array(
            'deleted_at' => array(
                '$exists' => false,
            )
        ));

        return parent::find($query, $options);
    }

    /**
     * FindOne with soft deletes means that we need to exclude (soft) "deleted" items
     */
     /**
 	 * @param array $query
 	 * @param array $options
 	 * @return array
 	 */
 	public function findOne($query=array(), $options=array())
 	{
        // attach deleted_at does not exist or deleted_at eq null
        $query = array_merge($query, array(
            'deleted_at' => array(
                '$exists' => false,
            )
        ));

        return parent::findOne($query, $options);
    }
}
