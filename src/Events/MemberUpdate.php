<?php

namespace Exan\Bread\Events;

use Exan\Bread\CachyMcCacheFace;
use Exan\Bread\Contracts\EventListenerInterface;
use Exan\Bread\Contracts\ServerConfigRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\GuildMemberUpdate;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

class MemberUpdate implements EventListenerInterface
{
    public static function isFrench($username): bool
    {
        return str_contains($username, '🇫🇷');
    }

    public function __construct(
        private readonly Discord $discord,
        private readonly GuildMemberUpdate $guildMemberUpdate,
        private readonly CachyMcCacheFace $cache,
        private readonly LoggerInterface $log,
        private readonly ServerConfigRepositoryInterface $configs,
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

        $this->cache->updateUserFrench(
            $this->guildMemberUpdate->guild_id,
            $this->guildMemberUpdate->user->id,
            $isFrench,
        );
    }
}
