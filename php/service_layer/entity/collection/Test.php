<?php
namespace Entity\Collection;
require_once(__DIR__ . '/../Test.php');

class Test extends \ArrayIterator {
    public function __call($name, $args) {
        $this->rewind();
        return call_user_func_array(array($this->current(),$name), $args);
    }

    public function __get($name) {
        $this->rewind();
        return $this->current()->$name;
    }
}
