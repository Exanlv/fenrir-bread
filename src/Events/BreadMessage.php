<?php

namespace Exan\Bread\Events;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\MessageCreate;
use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Rest\Helpers\Emoji\EmojiBuilder;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

use function React\Async\async;
use function React\Async\await;

class BreadMessage
{
    private const BREAD_MARKERS = ['bread', '🍞'];

    public function __construct(
        private readonly Discord $discord,
        private readonly MessageCreate $messageCreate,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $log,
    ) {
    }

    public function filter(): ExtendedPromiseInterface
    {
        return new Promise(function ($resolve, $reject) {
            $channelId = $this->messageCreate->channel_id;
            $channelCacheKey = 'channel_names.' . $channelId;

            if (!$this->cache->has($channelCacheKey)) {

                try {
                    /** @var Channel */
                    $channel = await($this->discord->rest->channel->get($channelId));
                } catch (\Exception $e) {
                    $this->log->info(sprintf('Unable to retrieve channel details of %s due to "%s"', $channelId, $e->getMessage()));

                    $reject();

                    return;
                }

                $this->cache->set(
                    $channelCacheKey,
                    strtolower($channel->name)
                );
            }

            $channelName = $this->cache->get($channelCacheKey);

            foreach (self::BREAD_MARKERS as $breadMarker) {
                if (str_contains($channelName, $breadMarker)) {
                    $resolve();

                    return;
                }
            }

            $reject();
        });
    }

    private function isFrench()
    {
        return new Promise(function ($resolve, $reject) {
            $frenchCacheKey = 'member_french.' . $this->messageCreate->guild_id . '.' . $this->messageCreate->author->id;
            if (!$this->cache->has($frenchCacheKey)) {
                $username = $this->messageCreate->member->nick ?? $this->messageCreate->author->global_name ?? '';

                $this->cache->set(
                    $frenchCacheKey,
                    str_contains($username, '🇫🇷'),
                );
            }

            $resolve($this->cache->get($frenchCacheKey));
        });
    }

    public function execute()
    {
        (async(function () {
            $isFrench = await($this->isFrench());

            $emote = $isFrench ? '🥖' : '🍞';

            $this->discord->rest->channel->createReaction(
                $this->messageCreate->channel_id,
                $this->messageCreate->id,
                EmojiBuilder::new()->setId($emote)
            );
        }))();
    }
}
