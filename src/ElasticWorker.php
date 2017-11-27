<?php
namespace Hugo;

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ElasticWorker extends AbstractWorker
{
    protected function process(AMQPMessage $message)
    {
        echo " [x] Received ", $message->body, "\n";
        return true;
    }
}

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$consumer   = new ElasticWorker($connection,'task_queue',null);
$consumer->run();