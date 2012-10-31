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
        if ($envelope = $this->get()) {
            $this->ack($envelope->getDeliveryTag());
        }
        return $envelope;
    }
}
