<?php
/**
 * 消费
 * 是服务器处理队列中的数据
 */
#开启守护进程
\Swoole\Process::daemon();
#设置进程数量
$workerNum = 2;
/*实例化swoole进程*/
$pool = new Swoole\Process\Pool($workerNum);
$pool->on("WorkerStart", function ($pool,$workerId) {
    echo "Worker#{$workerId} is started \n";
    deal_order($workerId);
});

$pool->on("WorkerStop", function ($pool, $workerId) {
    echo "Worker#{$workerId} is stoped";
});

$pool->start();


function deal_order($workerId)
{

    /*交换机名称*/
    $exchangeName = 'trade';
    /*队列名称*/
    $queueName = 'email';
    $routingKey = 'superrd';
    #实例化AMQP
    $amqpConnect = new AMQPConnection([
        'host' => '127.0.0.1',
        'port' => '5672',
        'vhost' => '/',
        'login' => 'resty',
        'password' => 'resty',
    ]);
    #建立连接AMQP
    $amqpConnect->connect() or die('connect fail');

    try {
        #实例化通道
        $channel = new AMQPChannel($amqpConnect);
        #实例化交换机
        $exchange = new AMQPExchange($channel);
        #设置交换机名称
        $exchange->setName($exchangeName);
        #设置交换机类型
        $exchange->setType(AMQP_EX_TYPE_DIRECT);
        #交换机持久化
        $exchange->setFlags(AMQP_DURABLE);
        #声明交换机
        $exchange->declareExchange();
        #实例化队列
        $queue = new AMQPQueue($channel);
        #设置队列名称
        $queue->setName($queueName);
        #队列持久化
        $queue->setFlags(AMQP_DURABLE);
        #声明队列
        $queue->declareQueue();
        #绑定交换机
        $queue->bind($exchangeName, $routingKey);

        echo "message:\n ";
        while (true) {
            $queue->consume(function ($envelop,$queue) use($workerId)
            {
                $msg = $envelop->getBody();
                echo "当前进程WorkerId是：{$workerId}，消费的内容是：".$msg."\n";

//                $queue->ack($envelop->getDeliveryTag());//手动发送ACK应答
            });
        }

    } catch (AMQPConnectionException $e) {
        var_dump($e);
        exit();
    }

    $amqpConnect->disconnect();



}