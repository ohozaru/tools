<?php
require_once('test_bootstrap.php');
class Test_ServiceService extends PHPUnit_Framework_TestCase {
    /**
     * @expectedException RuntimeException
     */
    public function test_invalid_service_name() {
        Service::_('InvalidName');
    }

    public function test_getting_service() {
        $s = Service::_('Test');
        $this->assertEquals('Service\Test', $s->__get_working_namespace());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Method boo dosen't exist in service Service\Test
     */
    public function test_calling_invalid_method() {
        $respone = Service::_('Test')->boo();
    }

    public function test_service_response() {
        $s = Service::_('Test');
        $response = $s->echo('echo');
        $this->assertEquals('echo', $response);
    }

    public function test_service_response_2() {
        $this->assertEquals('foo', Service::_('Test')->get(Service::_('Foo')->foo()));
    }

    /**
     * @expectedException RuntimeException
     */
    public function test_invalid_service_call() {
        $this->assertEquals('boo', Service::_('Test.Invalid')->echo());
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function test_sub_service_call() {
        $this->assertEquals('boo', Service::_('Test.Boo')->echo());
    }
}
