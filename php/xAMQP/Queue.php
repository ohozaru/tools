<?php
namespace xAMQP;

class Queue extends \AMQPQueue
{
    /**
     * get and remove message from queue
     * @return AMQPEnvelope
     */
    public function shift()
    {
        return $this->get(AMQP_AUTOACK);
        if ($envelope = $this->get(AMQP_AUTOACK)) {
            $this->ack($envelope->getDeliveryTag());
        }
        return $envelope;
    }
}
