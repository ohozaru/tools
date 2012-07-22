<?php
require_once('test_bootstrap.php');
class Test_EntityCollection extends PHPUnit_Framework_TestCase {

    public function test_invalid_service_name() {
        $collection = Service::get('Test')->getTestCollection();
        $this->assertInstanceOf('ArrayIterator', $collection);
        $this->assertEquals('1', $collection->id);
        $this->assertEquals('1', $collection->getId());
    }
}
