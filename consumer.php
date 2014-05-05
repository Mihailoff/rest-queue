<?php

require_once __DIR__.'/vendor/autoload.php';

define('HOST', 'localhost');
define('PORT', 5672);
define('USER', 'guest');
define('PASS', 'guest');
define('VHOST', '/');
//define('AMQP_DEBUG', true);

use PhpAmqpLib\Connection\AMQPConnection;

$exchange = 'router';
$queue = 'msgs';
$consumer_tag = 'consumer';

$conn = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
$ch = $conn->channel();
$ch->queue_declare($queue, false, true, false, false);
$ch->exchange_declare($exchange, 'direct', false, true, false);
$ch->queue_bind($queue, $exchange);

function process_message($msg)
{
    $task = unserialize($msg->body);
    $pid = __DIR__ . '/var/processing.' . $task['id'];
    $result = __DIR__ . '/var/cache.' . $task['id'];
    if (!file_exists($pid)) {
        file_put_contents($pid, '');
    }
    
    // Do the "work"
    sleep(isset($task['ttl']) ? $task['ttl'] : 20);
    
    unlink($pid);
    
    file_put_contents($result, serialize([
        'creation_datetime' => $task['creation_dt'],
        'ready_datetime' => (new DateTime())->getTimestamp()
    ]));

    $msg->delivery_info['channel']->
        basic_ack($msg->delivery_info['delivery_tag']);

    // Send a message with the string "quit" to cancel the consumer.
    if ($msg->body === 'quit') {
        $msg->delivery_info['channel']->
            basic_cancel($msg->delivery_info['consumer_tag']);
    }
}

$ch->basic_consume($queue, $consumer_tag, false, false, false, false, 'process_message');

function shutdown($ch, $conn)
{
    $ch->close();
    $conn->close();
}
register_shutdown_function('shutdown', $ch, $conn);

// Loop as long as the channel has callbacks registered
while (count($ch->callbacks)) {
    $ch->wait();
}
