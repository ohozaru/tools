<?php

abstract class Collection extends ArrayIterator {
    public function __call($name, $args) {
        if($this->count() > 1) {
            throw new LogicException("Collection has more then one element, you cannot call entity method directly");
        }
        return call_user_func_array(array($this->current(),$name), $args);
    }

    public function __get($name) {
        if($this->count() > 1) {
            throw new LogicException("Collection has more then one element, you cannot get entity property directly");
        }
        return $this->current()->$name;
    }
}
