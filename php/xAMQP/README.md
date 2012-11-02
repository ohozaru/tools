#xAMQP
##wrapper for AMQP pecl library to make life easier</p>

##Usage Example

$amqp = new xAMQP\Adapter(new AMQPConnection());
$queue1 = $amqp->declareQueue('queue1');
$queue2 = $amqp->declareQueue('queue2');

To declare exchange and bind queues to it:

$exchange = $amqp->declareExchange('direct');
$exchange
  ->bindQueue($queue1, 'routing.key')
  ->bindQueue($queue1, 'routing.x.key')
  ->bindQueue($queue2, 'routing.key');

To send message to queues binded to goal exchange:

     $amqp->exchange('direct')->publish($message, 'routing.key');

To receive message from queue:

     $amqp->queue('queue1')->shift(); //get and ack
     $amqp->queue('queue2')->get();   //get without ack
