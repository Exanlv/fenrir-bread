<?php

namespace Exan\Bread\Events;

use Exan\Bread\Contracts\EventListenerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\GuildMemberUpdate;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

class MemberUpdate implements EventListenerInterface
{
    public static function getFrenchCacheKey(string $guildId, string $userId): string
    {
        return 'member_french.' . $guildId . '.' . $userId;
    }

    public static function isFrench($username): bool
    {
        return str_contains($username, '🇫🇷');
    }

    public function __construct(
        private readonly Discord $discord,
        private readonly GuildMemberUpdate $guildMemberUpdate,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $log,
    ) {
    }

    public function filter(): ExtendedPromiseInterface
    {
        return new Promise(function ($resolve, $reject) {
            $resolve();
        });
    }

    public function execute(): void
    {
        $username = $this->guildMemberUpdate->nick ?? $this->guildMemberUpdate->user->username ?? '';

        $isFrench = $this->isFrench($username);

        $this->log->debug('Setting frenchness of user', [
            'user' => $this->guildMemberUpdate->user->id,
            'state' => $isFrench,
        ]);

        $this->cache->set(
            $this->getFrenchCacheKey($this->guildMemberUpdate->guild_id, $this->guildMemberUpdate->user->id),
            $isFrench,
        );
    }
}
