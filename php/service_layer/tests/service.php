<?php
require_once('test_bootstrap.php');
class Test_ServiceService extends PHPUnit_Framework_TestCase {
    /**
     * @expectedException RuntimeException
     */
    public function test_invalid_service_name() {
        Service::get('InvalidName');
    }

    public function test_getting_service() {
        $s = Service::get('Test');
        $this->assertEquals('Service\Test', $s->__get_working_namespace());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Method boo dosen't exist in service Service\Test
     */
    public function test_calling_invalid_method() {
        $respone = Service::get('Test')->boo();
    }

    public function test_service_response() {
        $s = Service::get('Test');
        $response = $s->foo();
        $this->assertEquals('foo', $response);
    }

    /**
     * @expectedException RuntimeException
     */
    public function test_invalid_service_call() {
        $this->assertEquals('boo', Service::get('Test.Invalid')->foo());
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function test_sub_service_call() {
        $this->assertEquals('boo', Service::get('Test.Boo')->foo());
    }
}
