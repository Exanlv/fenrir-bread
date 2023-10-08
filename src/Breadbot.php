<?php

namespace Exan\Bread;

use Exan\Bread\Events\BreadMessage;
use Exan\Bread\Events\MemberUpdate;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\GuildMemberUpdate;
use Ragnarok\Fenrir\Gateway\Events\MessageCreate;

class Breadbot
{
    private const LISTENERS = [
        Events::MESSAGE_CREATE => [
            BreadMessage::class,
        ],

        Events::GUILD_MEMBER_UPDATE => [
            MemberUpdate::class,
        ],
    ];

    public function __construct(
        private readonly Discord $discord,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
        foreach (self::LISTENERS as $eventName => $listeners) {
            /** @var class-string $listener */
            foreach ($listeners as $listener) {
                $this->discord->gateway->events->on($eventName, function ($event) use ($listener) {
                    $eventHandler = new $listener(
                        $this->discord,
                        $event,
                        $this->cache,
                        $this->logger
                    );

                    $eventHandler->filter()->then(static function () use ($eventHandler) {
                        $eventHandler->execute();
                    }, function () {
                        // Filtered out, do nothing
                    });
                });
            }
        }
    }
}
