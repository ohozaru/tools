<?php
require_once '../Adapter.php';
class TestAdapter extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $rabbit = new AMQPConnection(
            array(
                'host' => 'localhost',
                'port' => '5672',
                'login' => 'guest',
                'password' => 'guest',
            )
        );
        if (!$rabbit->connect()) {
            throw RuntimeException("Can't connect to rabbit server");
        }
        $amqp = new xAMQP\Adapter($rabbit);
        $amqp->declareExchange('test');
        $foo = $amqp->declareQueue('foo');
        $boo = $amqp->declareQueue('boo');
        $amqp->exchange('test')->bindQueue($foo, 'test.foo');
        $amqp->exchange('test')->bindQueue($boo, 'test.boo');
        $this->adapter = $amqp;
    }

    public function test_doWeHaveTestExchangeDeclared()
    {
        $this->assertInstanceOf('xAMQP\Exchange', $this->adapter->exchange('test'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function test_tryToDeclareAlreadyDeclaredExchange()
    {
        $this->adapter->declareExchange('test');
    }

    public function test_doWeHaveTestQueuesDeclared()
    {
        $this->assertInstanceOf('xAMQP\Queue', $this->adapter->queue('foo'));
        $this->assertInstanceOf('xAMQP\Queue', $this->adapter->queue('boo'));
    }

    public function test_purgeTestQueues()
    {
        $this->assertTrue($this->adapter->queue('foo')->purge());
        $this->assertTrue($this->adapter->queue('boo')->purge());

        $this->assertFalse($this->adapter->queue('foo')->shift());
        $this->assertFalse($this->adapter->queue('boo')->shift());
    }

    public function test_pushingDataToTestExchange()
    {
        $this->assertTrue($this->adapter->exchange('test')->publish('f1', 'test.foo'));
        $this->assertTrue($this->adapter->exchange('test')->publish('f2', 'test.foo'));
        $this->assertTrue($this->adapter->exchange('test')->publish('f3', 'test.foo'));

        $this->assertTrue($this->adapter->exchange('test')->publish('b1', 'test.boo'));
        $this->assertTrue($this->adapter->exchange('test')->publish('b2', 'test.boo'));
        $this->assertTrue($this->adapter->exchange('test')->publish('b3', 'test.boo'));
    }

    public function test_fetchingDataFromTestQueuesWithoutAck()
    {
        $this->assertEquals('f1', $this->adapter->queue('foo')->get()->getBody());
        $this->assertEquals('f2', $this->adapter->queue('foo')->get()->getBody());
        $this->assertEquals('f3', $this->adapter->queue('foo')->get()->getBody());

        $this->assertEquals('b1', $this->adapter->queue('boo')->get()->getBody());
        $this->assertEquals('b2', $this->adapter->queue('boo')->get()->getBody());
        $this->assertEquals('b3', $this->adapter->queue('boo')->get()->getBody());
    }

    public function test_fetchingDataFromTestQueuesWithAck()
    {
        $this->assertEquals('f1', $this->adapter->queue('foo')->shift()->getBody());
        $this->assertEquals('f2', $this->adapter->queue('foo')->shift()->getBody());
        $this->assertEquals('f3', $this->adapter->queue('foo')->shift()->getBody());

        $this->assertEquals('b1', $this->adapter->queue('boo')->shift()->getBody());
        $this->assertEquals('b2', $this->adapter->queue('boo')->shift()->getBody());
        $this->assertEquals('b3', $this->adapter->queue('boo')->shift()->getBody());
    }

    public function test_areQueuesEmptyAtTheEnd()
    {
        $this->assertFalse($this->adapter->queue('foo')->shift());
        $this->assertFalse($this->adapter->queue('boo')->shift());
    }
}
