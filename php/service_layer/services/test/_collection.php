<?php
namespace Service\Test;

class Collection extends \ArrayIterator {
    //those methods should be placed in abstract class
    public function __call($name, $args) {
        $this->rewind();
        return call_user_func_array(array($this->current(),$name), $args);
    }

    public function __get($name) {
        $this->rewind();
        return $this->current()->$name;
    }
}
