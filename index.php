<?php

use Cache\Adapter\PHPArray\ArrayCachePool;
use Dotenv\Dotenv;
use Exan\Bread\Breadbot;
use Ragnarok\Fenrir\Discord;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\NullLogger;
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

$log->pushHandler(new StreamHandler('php://stdout', Level::Debug));

$discord = new Discord(env('TOKEN'), new NullLogger());

$discord->withGateway(Bitwise::from(
    Intent::GUILD_MESSAGES,
    Intent::GUILD_MEMBERS,
))->withRest();

$breadbot = new Breadbot(
    $discord,
    $cache,
    $log
);

$discord->gateway->open();
