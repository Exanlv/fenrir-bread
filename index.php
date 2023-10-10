<?php

use Cache\Adapter\PHPArray\ArrayCachePool;
use Dotenv\Dotenv;
use Exan\Bread\Breadbot;
use Exan\Bread\CachyMcCacheFace;
use Exan\Bread\FsServerConfigRepository;
use Ragnarok\Fenrir\Discord;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\NullLogger;
use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\Intent;
use React\EventLoop\Loop;

use function React\Async\await;

require './vendor/autoload.php';

$loop = Loop::get();

function env(string $key, mixed $default = null) {
    $var = isset($_ENV[$key]) ? $_ENV[$key] : getenv($key);

    return $var === false ? $default : $var;
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dotenv->required('TOKEN');

$cache = new ArrayCachePool();

$log = new Logger('name');

$log->pushHandler(new StreamHandler('php://stdout', Level::Error));
$log->pushHandler(new StreamHandler('php://stdout', Level::Critical));
$log->pushHandler(new StreamHandler('php://stdout', Level::Debug));
$log->pushHandler(new StreamHandler('php://stdout', Level::Info));

$discord = new Discord(env('TOKEN'), new NullLogger(), $loop);

$discord->withGateway(Bitwise::from(
    Intent::GUILD_MESSAGES,
    Intent::GUILD_MEMBERS,
))->withRest();

$cachy = new CachyMcCacheFace(
    $discord,
    $cache,
);

$repository = new FsServerConfigRepository('./bread', $loop);
await($repository->initialize());

$breadbot = new Breadbot(
    $discord,
    $cachy,
    $log,
    $repository
);

$discord->gateway->open();
