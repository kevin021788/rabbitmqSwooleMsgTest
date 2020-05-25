<?php

$workerNum = 8;
$pool = new Swoole\Process\Pool($workerNum);
$pool->on("WorkerStart", function ($pool,$workerId) {
    echo "Worker#{$workerId} is started \n";

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


        echo "message:\n ";
        while (true) {
            $queue->consume(function ($envelop,$queue) use($workerId)
            {
                $msg = $envelop->getBody();
                echo "WorkerId:{$workerId}".$msg."\r\n";
                $queue->ack($envelop->getDeliveryTag());//手动发送ACK应答
            });
        }

    } catch (AMQPConnectionException $e) {
        var_dump($e);
        exit();
    }

    $amqpConnect->disconnect();

});

$pool->on("WorkerStop", function ($pool, $workerId) {
    echo "Worker#{$workerId} is stoped";
});
$pool->start();
