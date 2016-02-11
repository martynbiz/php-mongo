<?php

namespace MartynBiz\Mongo;

use MartynBiz\Mongo\Mongo;

class MongoIterator extends \ArrayIterator {
    private $position = 0;
    private $data = array();

    public function __construct($array=array()) {
        $this->data = $array;

        $this->position = 0;
    }

    function count() {
        return count($this->data);
    }

    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->data[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->data[$this->position]);
    }

    function offsetGet($index) {
        return $this->data[$index];
    }

    function toArray() {
        $array = array();

        foreach ($this->data as $value) {
            if ($value instanceof Mongo) {
                $value = $value->toArray();
            }

            array_push($array, $value);
        }

        return $array;
    }
}
