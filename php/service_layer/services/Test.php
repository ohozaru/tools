<?php
namespace Service\Test;
require_once(__DIR__ . '/../entity/Test.php');
require_once(__DIR__ . '/../entity/collection/Test.php');

use Entity\Test as Entity;
use Entity\Collection\Test as Collection;

function foo() {
    return 'foo';
}

function getTestCollection() {
    $collection = new Collection;
    $collection->append(new Entity(1));
    $collection->append(new Entity(2));
    return $collection;
}
