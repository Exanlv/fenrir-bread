<?php

namespace Exan\Bread;

use Exan\Bread\Events\MemberUpdate;
use Psr\SimpleCache\CacheInterface;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Parts\Channel;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

class CachyMcCacheFace
{
    public function __construct(
        private Discord $discord,
        private CacheInterface $cache,
    ) {
    }

    public function getChannelName(string $channelId): ExtendedPromiseInterface
    {
        return (new Promise(function ($resolve, $reject) use ($channelId) {
            $cacheKey = $this->getChannelNameCacheKey($channelId);

            if ($this->cache->has($cacheKey)) {
                $resolve($cacheKey);
                return;
            }

            $this->cacheChannelName($channelId, $cacheKey)->then(function () use ($resolve, $cacheKey) {
                $resolve($cacheKey);
            }, function ($e) use ($reject) {
                $reject($e);
            });
        }))->then(function (string $cacheKey) {
            return $this->cache->get($cacheKey);
        });
    }

    private function getChannelNameCacheKey(string $channelId): string
    {
        return 'channel_names.' . $channelId;
    }

    private function cacheChannelName(
        string $channelId,
        string $cacheKey,
    ): ExtendedPromiseInterface {
        return $this->discord->rest->channel->get($channelId)->then(function (Channel $channel) use ($cacheKey) {
            $this->cache->set($cacheKey, strtolower($channel->name));
        });
    }

    public function updateUserFrench(string $guildId, string $userId, bool $isFrench): void
    {
        $this->cache->set(
            $this->getUserFrenchCacheKey($guildId, $userId),
            $isFrench
        );
    }

    public function isUserFrench(string $guildId, string $userId, callable $usernameResolver): ExtendedPromiseInterface
    {
        return new Promise(function ($resolve) use ($guildId, $userId, $usernameResolver) {
            $cacheKey = $this->getUserFrenchCacheKey($guildId, $userId);

            if (!$this->cache->has($cacheKey)) {
                $this->cache->set(
                    $cacheKey,
                    MemberUpdate::isFrench($usernameResolver())
                );
            }

            $resolve($this->cache->get($cacheKey));
        });
    }

    private function getUserFrenchCacheKey(string $guildId, string $userId): string
    {
        return 'member_french.' . $guildId . '.' . $userId;
    }
}
