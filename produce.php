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
    #实例化ＡＭＱＰ
    $channel = new AMQPChannel($amqpConnect);
    #实例化交换机
    $exchange = new AMQPExchange($channel);
    #设置交换机名称
    $exchange->setName($exchangeName);
    #设置交换机连接类型,直接交换类型,
    $exchange->setType(AMQP_EX_TYPE_DIRECT);
    #交换机持久化
    $exchange->setFlags(AMQP_DURABLE);
    #声明交换机F
    $exchange->declareExchange();
    /*实例化队列*/
    $queue = new AMQPQueue($channel);
    #设置队列名称
    $queue->setName($queueName);
    #队列持久化＃，长连接
    $queue->setFlags(AMQP_DURABLE);
    #声明队列　
    $queue->declareQueue();
    #绑定队列　
    $queue->bind($exchangeName, $routingKey);

    $msg = [
        'code' => '200',
        'msg' => 'success',
        'tid' => uniqid(),
        'data' => 'hello world!',
    ];
    $msg = json_encode($msg);
    #生产消息发送
    $exchange->publish($msg, $routingKey);

    echo $msg."\r\n";

} catch (AMQPConnectionException $e) {
    var_dump($e);
    exit();
}

$amqpConnect->disconnect();