<?php

$amqpConnect = new AMQPConnection([
    'host' => '127.0.0.1',
    'port' => '5672',
    'vhost' => '/',
    'login' => 'resty',
    'password' => 'resty',
]);
$exchangeName = 'trade';
$queueName = 'email';
$routingKey = 'superrd';
$amqpConnect->connect() or die('connect fail');

try {

    $channel = new AMQPChannel($amqpConnect);

    $exchange = new AMQPExchange($channel);
    $exchange->setName($exchangeName);
    $exchange->setType(AMQP_EX_TYPE_DIRECT);
    //交换机持久化
    $exchange->setFlags(AMQP_DURABLE);
    $exchange->declareExchange();

    $queue = new AMQPQueue($channel);
    $queue->setName($queueName);
    //队列持久化
    $queue->setFlags(AMQP_DURABLE);

    $queue->declareQueue();
    $queue->bind($exchangeName, $routingKey);
    $msg = [
        'code' => '200',
        'msg' => 'success',
        'tid' => uniqid(),
        'data' => 'hello world!',
    ];
    $msg = json_encode($msg);
    $exchange->publish($msg, $routingKey);

    echo $msg."\r\n";

} catch (AMQPConnectionException $e) {
    var_dump($e);
    exit();
}

$amqpConnect->disconnect();