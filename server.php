<?php

include 'vendor/autoload.php';

use App\Event\ServerSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use React\EventLoop\Factory;
use Smalot\Smtp\Server\Server;

$dispatcher = new EventDispatcher();

$stream = fopen('php://output', 'w');
$handler = new StreamHandler($stream, Logger::INFO);
$logger = new Logger('log', [$handler]);
$dispatcher->addSubscriber(new ServerSubscriber($logger, '/data'));

$loop = Factory::create();
$server = new Server($loop, $dispatcher);
$server->listen(25, '0.0.0.0');
$loop->run();
