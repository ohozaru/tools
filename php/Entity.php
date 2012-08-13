<?php

abstract class Entity {
    protected $_data = array();

    final public function __construct(array $data = array()) {
        $this->populate($data);
        $this->init($data);
    }

    public function init(array $data = array()) {
    }

    public function populate($data) {
        $data = ($data instanceof stdClass) ? (array) $data : $data;
        foreach($data as $key => $value)
            $this->$key = $value;
    }

    public function getData() {
        return $this->_data;
    }

    public function toArray() {
        return $this->getData();
    }

    public function __set($name, $value) {
        if(! array_key_exists($name, $this->_data))
            throw new InvalidArgumentException(sprintf('Variable %s dosen\'t exists in %s', $name, get_class($this)));

        $magic_method = '__set_'.$name;
        if(method_exists($this, $magic_method))
            $this->$magic_method($value);
        else
            $this->_set($name, $value);
    }

    public function __get($name) {
        if(property_exists($this, $name))
            return $this->$name;

        if(array_key_exists($name, $this->_data))
            return $this->_data[$name];

        throw new InvalidArgumentException(sprintf('Variable %s dosen\'t exists in %s', $name, get_class($this)));
    }

    protected function _set($name, $value) {
        $this->_data[$name] = $value;
    }
}
