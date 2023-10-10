<?php

use Exan\Bread\CachyMcCacheFace;
use Exan\Bread\Contracts\EventListenerInterface;
use Exan\Bread\Contracts\ServerConfigRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\MessageReactionAdd;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

use function React\Async\await;

class ReactionAdd implements EventListenerInterface
{
    public function __construct(
        private readonly Discord $discord,
        private readonly MessageReactionAdd $messageReactionAdd,
        private readonly CachyMcCacheFace $cache,
        private readonly LoggerInterface $log,
        private readonly ServerConfigRepositoryInterface $configs,
    ) {
    }

    private function getMessageInfoCacheKey(string $messageId): string
    {
        return 'messages.' . $messageId;
    }

    public function filter(): ExtendedPromiseInterface
    {
        return new Promise(function () {
            $cacheKey = $this->getMessageInfoCacheKey($this->messageReactionAdd->message_id);

            if (!$this->cache->has($cacheKey)) {
                $message = await($this->discord->rest->channel->getMessage(
                    $this->messageReactionAdd->channel_id,
                    $this->messageReactionAdd->message_id
                ));

            }
        });
    }

    public function execute(): void
    {
        $serverConfig = $this->configs->get($this->messageReactionAdd->guild_id);

        $currentScore = $serverConfig->getBreadScore($this->messageReactionAdd->user_id);
        $currentScore++;

        $serverConfig->storeBreadScore($this->messageReactionAdd->user_id, $currentScore);
    }
}
