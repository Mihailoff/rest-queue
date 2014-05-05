<?php

require_once __DIR__.'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

define('HOST', 'localhost');
define('PORT', 5672);
define('USER', 'guest');
define('PASS', 'guest');
define('VHOST', '/');
//define('AMQP_DEBUG', true);

function create_task($user_id) {
    $conn = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
    $ch = $conn->channel();
    $ch->queue_declare('msgs', false, true, false, false);
    $ch->exchange_declare('router', 'direct', false, true, false);
    $ch->queue_bind('msgs', 'router');

    $created = (new DateTime())->getTimestamp();
    $msg_body = serialize([
        'id' => $user_id,
        'creation_dt' => $created
    ]);

    $msg = new AMQPMessage($msg_body, array('content_type' => 'text/plain', 'delivery_mode' => 2));
    $ch->basic_publish($msg, 'router');

    $ch->close();
    $conn->close();
    
    return $created;
}