<?php
namespace xAMQP;
class Exchange extends \AMQPExchange
{
    protected $_type;
    protected $_queues = array();

    public function setType($type)
    {
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
    public function bindQueue(\AMQPQueue $queue, $routing)
    {
        $queue->bind($this->getName(), $routing);
        $this->_queues[$queue->getName()]['routings'][] = $routing;
        return $this;
    }
}

