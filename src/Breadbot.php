<?php

namespace Exan\Bread;

use Exan\Bread\Events\BreadMessage;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\MessageCreate;

class Breadbot
{
    public function __construct(
        private readonly Discord $discord,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
        $this->discord->gateway->events->on(Events::MESSAGE_CREATE, function (MessageCreate $messageCreate) {
            $breadMessage = new BreadMessage(
                $this->discord,
                $messageCreate,
                $this->cache,
                $this->logger,
            );

            $breadMessage->filter()->then(static function () use ($breadMessage) {
                $breadMessage->execute();
            }, function () {
                // Not a bread channel
            });
        });
    }
}
