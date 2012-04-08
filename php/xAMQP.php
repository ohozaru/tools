<?php
/**
 * USAGE:
 *
 * $amqp = new xAMQP(new AMQPConnection());
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
 *
 * @return xAMQP
 */
class xAMQP {
    protected $_connection;
    protected $_exchanges = array();
    protected $_queues = array();

    public function __construct(AMQPConnection $amqp_connection) {
        if(!$amqp_connection->isConnected()) {
           $amqp_connection->connect();
        }
        $this->_connection = $amqp_connection;
    }

    public function __destruct() {
        $this->_connection->disconnect();
    }

    /**
     * Declare a new exchange on the broker
     * 
     * @param string $name 
     * @param const $type 
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
     * @return xAMQPExchange
     * @throws AMQPException
     */
    public function declareExchange($name, $type = AMQP_EX_TYPE_DIRECT) {
        
        if(array_key_exists($name, $this->_exchanges)) {
            throw new AMQPException(sprintf('Exchange %s already declared', $name));
        }

        $exchange = new xAMQPExchange(new AMQPChannel($this->_connection));
        $exchange->setName($name);
        $exchange->setType($type);
        $exchange->declare();
        $this->_exchanges[$name] = $exchange;
        return $exchange;
    }

    /**
     * @param string $name 
     * @return xAMQPQueue
     * @throws AMQPException
     */
    public function declareQueue($name) {
        if(array_key_exists($name, $this->_queues)) {
            throw new AMQPException(sprintf('Queue %s already declared', $name));
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
     * @param string $name 
     * @return AMQPExchange
     * @throws AMQPException
     */
    public function exchange($name) {
        if(!array_key_exists($name, $this->_exchanges)) {
            throw new AMQPException(sprintf('Exchange %s is not declared', $name));
        }
        return $this->_exchanges[$name];
    }

    /**
     * Returns declared queue
     * 
     * @param string $name 
     * @return xAMQPQueue
     * @throws AMQPException
     */
    public function queue($name) {
        if(!array_key_exists($name, $this->_queues)) {
            throw new AMQPException(sprintf('Queue %s is not declared', $name));
        }
        return $this->_queues[$name];
        
    }
}

class xAMQPExchange extends AMQPExchange {
    protected $_type;
    protected $_queues = array();

    public function setType($type) {
        $this->_type = $type;
        return parent::setType($type);
    }

    /**
     * Bind a given queue to routing key on current exchange
     * 
     * @param AMQPQueue $queue 
     * @param string $routing 
     * @return xAMQPExchange
     */
    public function bindQueue(AMQPQueue $queue, $routing) {
        $queue->bind($this->getName(), $routing);
        $this->_queues[$queue->getName()]['routings'][] = $routing;
        return $this;
    }
}

class xAMQPQueue extends AMQPQueue {
    /**
     * get and remove message from queue
     * @return AMQPEnvelope
     */
    public function shift() {
        if($envelope = $this->get()) {
            $this->ack($envelope->getDeliveryTag());
        }
        return $envelope;
    }
}
