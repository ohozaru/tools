<?php
namespace Entity;

class Test {
    public function __construct($id) {
        $this->id = $id;
    }

    public function __get($name) {
        return $this->$name;
    }

    public function getId() {
        return $this->id;
    }
}
