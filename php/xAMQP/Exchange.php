<?php
namespace xAMQP;
class Exchange extends \AMQPExchange
{
    protected $_queues = array();

    /**
     * Bind a given queue to routing key on current exchange
     * 
     * @param Queue $queue 
     * @param string $routing 
     * @return Exchange
     */
    public function bindQueue(Queue $queue, $routing)
    {
        $queue->bind($this->getName(), $routing);
        $this->_queues[$queue->getName()]['routings'][] = $routing;
        return $this;
    }
}

