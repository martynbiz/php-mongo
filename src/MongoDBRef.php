<?php

namespace MartynBiz\Mongo;

use MongoDB\BSON\ObjectID;

class MongoDBRef implements \ArrayAccess{

    // /**
    //  * string
    //  */
    // private $collection;
    //
    // /**
    //  * MongoDB\BSON\ObjectID
    //  */
    // private $id;

    /**
     * array
     */
    private $data;

    public function __constuct($ref, ObjectID $id)
    {
        // $this->collection = $collection;
        // $this->id = $id;

        $this->data = [
            '$ref' => $ref,
            '$id' => $id,
        ];
    }

    public static function create($ref, $id, $database=null)
    {
        $obj = new static($ref, $id);
        
        $obj['$ref'] = $ref;
        $obj['$id'] = $id;

        return $obj;
    }

    // public static function get(Database $db , $ref)
    // {
    //
    // }

    public static function isRef($ref)
    {
        return ($ref instanceof self);
    }


    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }
    public function offsetGet($offset) {
        return $this->data[$offset];
    }
    public function offsetSet($offset, $value) {
        $this->data[$offset] = $value;
    }
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }
}
