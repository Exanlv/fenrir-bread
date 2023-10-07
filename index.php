<?php

use Cache\Adapter\PHPArray\ArrayCachePool;
use Dotenv\Dotenv;
use Exan\Bread\Breadbot;
use Ragnarok\Fenrir\Discord;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\Intent;

require './vendor/autoload.php';

function env(string $key, mixed $default = null) {
    $var = isset($_ENV[$key]) ? $_ENV[$key] : getenv($key);

    return $var === false ? $default : $var;
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dotenv->required('TOKEN');

$cache = new ArrayCachePool();

$log = new Logger('name');

$log->pushHandler(new StreamHandler('php://stdout', Level::Info));
$log->pushHandler(new StreamHandler('php://stdout', Level::Warning));
$log->pushHandler(new StreamHandler('php://stdout', Level::Debug));
$log->pushHandler(new StreamHandler('php://stdout', Level::Critical));
$log->pushHandler(new StreamHandler('php://stdout', Level::Notice));
$log->pushHandler(new StreamHandler('php://stdout', Level::Alert));

$discord = new Discord(env('TOKEN'), $log);

$discord->withGateway(Bitwise::from(
    Intent::GUILD_MESSAGES,
))->withRest();

$breadbot = new Breadbot(
    $discord,
    $cache,
    $log
);

$discord->gateway->open();
