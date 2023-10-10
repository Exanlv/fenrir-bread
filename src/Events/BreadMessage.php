<?php

namespace Exan\Bread\Events;

use Exan\Bread\CachyMcCacheFace;
use Exan\Bread\Contracts\EventListenerInterface;
use Exan\Bread\Contracts\ServerConfigRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\MessageCreate;
use Ragnarok\Fenrir\Rest\Helpers\Emoji\EmojiBuilder;
use React\Promise\ExtendedPromiseInterface;

use function React\Async\async;
use function React\Async\await;

class BreadMessage implements EventListenerInterface
{
    private const BREAD_MARKERS = ['bread', '🍞'];

    public function __construct(
        private readonly Discord $discord,
        private readonly MessageCreate $messageCreate,
        private readonly CachyMcCacheFace $cache,
        private readonly LoggerInterface $log,
        private readonly ServerConfigRepositoryInterface $configs,
    ) {
    }

    public function filter(): ExtendedPromiseInterface
    {
        return $this->cache->getChannelName($this->messageCreate->channel_id)->then(function (string $channelName) {
            foreach (self::BREAD_MARKERS as $breadMarker) {
                if (str_contains($channelName, $breadMarker)) {
                    return;
                }
            }

            var_dump($channelName);

            throw new Exception('Channel is not a bread-channel');
        });
    }

    private function isFrench(): ExtendedPromiseInterface
    {
        return $this->cache->isUserFrench(
            $this->messageCreate->guild_id,
            $this->messageCreate->author->id,
            function (): string {
                return $this->messageCreate->member->nick ?? $this->messageCreate->author->username ?? '';
            }
        );
    }

    public function execute(): void
    {
        async(function () {
            $isFrench = await($this->isFrench());

            $emote = $isFrench ? '🥖' : '🍞';

            $this->log->debug('Adding react to message', [
                'reaction' => $emote,
                'message' => [
                    'guild' => $this->messageCreate->guild_id,
                    'channel' => $this->messageCreate->channel_id,
                    'message' => $this->messageCreate->id,
                ],
            ]);

            $this->discord->rest->channel->createReaction(
                $this->messageCreate->channel_id,
                $this->messageCreate->id,
                EmojiBuilder::new()->setId($emote)
            );
        })();
    }
}
