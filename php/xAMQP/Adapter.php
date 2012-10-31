<?php
namespace xAMQP;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Exchange.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Queue.php';
/**
 * USAGE:
 *
 * $amqp = new xAMQP\Adapter(new AMQPConnection());
 * $queue1 = $amqp->declareQueue('queue1');
 * $queue2 = $amqp->declareQueue('queue2');
 *
 * $exchange = $amqp->declareExchange('myexchange');
 * $exchange
 *   ->bindQueue($queue1, 'routing.key');
 *   ->bindQueue($queue1, 'routing.x.key');
 *   ->bindQueue($queue2, 'routing.key');
 *
 * To send message to queues binded to goal exchange:
 *      $amqp->exchange('goal')->publish($message, 'device.info');
 *
 * To receive message from queue:
 *      $amqp->queue('queue1')->shift(); //get and ack
 *      $amqp->queue('queue2')->get();   //get without ack
 */
class Adapter
{
    protected $_connection;
    protected $_exchanges = array();
    protected $_queues = array();

    public function __construct(AMQPConnection $amqp_connection)
    {
        if (!$amqp_connection->isConnected()) {
           $amqp_connection->connect();
        }
        $this->_connection = $amqp_connection;
    }

    public function __destruct()
    {
        $this->_connection->disconnect();
    }

    /**
     * Declare a new exchange on the broker
     * 
     * @param $name (string)
     * @param $type (const)
     *
     * Types:
     *      AMQP_EX_TYPE_DIRECT -   A direct exchange matches when the routing key property of a message and the key of the binding are identical.
     *      AMQP_EX_TYPE_FANOUT -   A fanout exchange always matches, even on bindings without a key.
     *      AMQP_EX_TYPE_HEADER -   A headers exchange matches on the presence of keys as well as key–value pairs which can be concatenated with logical and/or connectives in a message header. 
     *                              In this case the routing key is not used for matching. Instead of a routing key, header keys and/or key-value pairs are used for matching; 
     *                              header key matching is done on keys that are present; key-value pair matching is done on keys and values of the keys respectively 
     *      AMQP_EX_TYPE_TOPIC  -   A topic exchange matches the routing key property of a message on binding key words. Words are strings which are separated by dots. 
     *                              Two additional characters are also valid: the *, which matches 1 word and the #, which matches 0..N words. 
     *                              Example: *.stock.# matches the routing keys usd.stock and eur.stock.db but not stock.nasdaq. 
     *
     * @return Exchange
     * @throws InvalidArgumentException
     */
    public function declareExchange($name, $type = AMQP_EX_TYPE_DIRECT)
    {
        if (array_key_exists($name, $this->_exchanges)) {
            throw new \InvalidArgumentException(sprintf('Exchange %s already declared', $name));
        }

        $exchange = new Exchange(new AMQPChannel($this->_connection));
        $exchange->setName($name);
        $exchange->setType($type);
        $exchange->declare();
        $this->_exchanges[$name] = $exchange;
        return $exchange;
    }

    /**
     * @param $name (string)
     * @return Queue
     * @throws InvalidArgumentException
     */
    public function declareQueue($name)
    {
        if (array_key_exists($name, $this->_queues)) {
            throw new \InvalidArgumentException(sprintf('Queue %s already declared', $name));
        }

        $queue = new xAMQPQueue(new AMQPChannel($this->_connection));
        $queue->setName($name);
        $queue->declare();
        $this->_queues[$name] = $queue;
        return $queue;
    }

    /**
     * Returns declared exchanged
     * 
     * @param $name (string)
     * @return AMQPExchange
     * @throws AMQPException
     */
    public function exchange($name)
    {
        if (!array_key_exists($name, $this->_exchanges)) {
            throw new \InvalidArgumentException(sprintf('Exchange %s is not declared', $name));
        }
        return $this->_exchanges[$name];
    }

    /**
     * Returns declared queue
     * 
     * @param  $name (string)
     * @return Queue
     * @throws InvalidArgumentException
     */
    public function queue($name)
    {
        if (!array_key($name, $this->_queues)) {
            throw new \InvalidArgumentException(sprintf('Queue %s is not declared', $name));
        }
        return $this->_queues[$name];
    }
}
