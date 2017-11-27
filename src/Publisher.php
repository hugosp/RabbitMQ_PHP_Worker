<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Publisher {

    protected $connection;
    protected $queueName;
    protected $exchange;

    public function __construct(AMQPStreamConnection $AMQPConnection, $queueName, $exchange = null)
    {
        $this->connection = $AMQPConnection;
        $this->queueName = $queueName;
        $this->exchange = $exchange;
    }

    public function publish($message)
    {
        $channel = $this->connection->channel();

        if ($this->exchange !== null) {
            $channel->exchange_declare($this->exchange, "topic", false, true, false);
        }
        $channel->queue_declare($this->queueName, false, true, false, false);
        if ($this->exchange !== null) {
            $channel->queue_bind($this->queueName, $this->exchange);
        }

        $msg = new AMQPMessage($message, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

        $channel->basic_publish($msg, '', $this->queueName);

        echo " [x] Sent ", $message, "\n";

        $channel->close();
        $this->connection->close();
    }
}

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$publisher  = new Publisher($connection,'task_queue',null);
$publisher->publish(implode(' ', $argv));