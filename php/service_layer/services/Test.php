<?php
namespace Service\Test;
require_once(__DIR__ . '/test/_entity.php');
require_once(__DIR__ . '/test/_collection.php');

function get($foo) {
    return $foo;
}

function getTestCollection() {
    $collection = new Collection;
    $collection->append(new Entity(1));
    $collection->append(new Entity(2));
    return $collection;
}
