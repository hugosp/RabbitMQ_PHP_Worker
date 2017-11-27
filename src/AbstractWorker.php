<?php
namespace Hugo;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractWorker
{
    protected $connection;
    protected $queueName;
    protected $exchange;

    public function __construct(AMQPStreamConnection $AMQPConnection, $queueName, $exchange = null)
    {
        $this->connection = $AMQPConnection;
        $this->queueName = $queueName;
        $this->exchange = $exchange;
    }

    public function run()
    {
        $channel = $this->connection->channel();
        if ($this->exchange !== null) {
            $channel->exchange_declare($this->exchange, "topic", false, true, false);
        }

        $channel->queue_declare($this->queueName, false, true, false, false);
        if ($this->exchange !== null) {
            $channel->queue_bind($this->queueName, $this->exchange);
        }

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($this->queueName, '', false, false, false, false, [$this, 'callback']);

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $this->connection->close();
    }


    final function callback(AMQPMessage $message)
    {
        $result = $this->process($message);

        if (false === $result) {
            $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'], false, true);
        } else {
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        }
    }

    /**
     * @param AMQPMessage $message
     *
     * @return bool
     */
    abstract protected function process(AMQPMessage $message);
}