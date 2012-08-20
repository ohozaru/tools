<?php
/**
 * ServiceLayer factory class
 * https://github.com/nigro/tools/tree/master/php/service_layer
 * Usage:
    Service::init(array(<config>))
    Service::get('Article')->getBreakingNews(10);
 */
class Service {
    static protected $_registry = array();
    static protected $_options = array(
        'service_path' => '/services',
        'service_namespace' => 'Service',
    );
    protected $_working_namespace;

    static public function init(array $options = array()) {
        foreach($options as $key => $value) {
            if(!array_key_exists($key, self::$_options)) {
                throw new InvalidArgumentException("Unknown option given: $key");
            }
            if($key === 'service_path') {
                $value = rtrim($value, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }
            else if($key === 'service_namespace') {
                $value = rtrim($value, '\\');
            }
            self::$_options[$key] = $value;
        }
    }

    static public function _($namespace) {
        if(!array_key_exists($namespace, self::$_registry)) {
            $file =  self::$_options['service_path'] . str_replace('.', DIRECTORY_SEPARATOR, $namespace) . '.php';
            self::_require($file);
            self::$_registry[$namespace] = realpath($file);
        }

        return new self($namespace);
    }

    static private function _require($file) {
        if(!is_file($file)) {
            throw new RuntimeException("File not found $file");
        }
        if(!is_readable($file)) {
            throw new RuntimeException("File is not readable $file");
        }
        require_once($file);
    }

    public function __construct($namespace) {
        $this->_working_namespace = $namespace;
    }

    public function __get_working_namespace() {
        return self::$_options['service_namespace'] . '\\' . $this->_working_namespace;
    }

    public function __call($name, $args) {
        $method_name = $this->__get_working_namespace() . '\\' . $name;
        if(!is_callable($method_name)) {
            throw new RuntimeException(sprintf("Method %s dosen't exist in service %s", $name, $this->__get_working_namespace()));
        }
        return call_user_func_array($method_name, $args);
    }
}
