<?php

namespace Exan\Bread;

use Exan\Bread\Contracts\ServerConfigInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;

class FsServerConfig implements ServerConfigInterface
{
    private $dirty = false;

    public function __construct(
        private Filesystem $fs,
        private LoopInterface $loop,
        private string $path,
        private array $data,
    ) {
        $this->loop->addPeriodicTimer(60, $this->save(...));
    }

    private function save()
    {
        if (!$this->dirty) {
            return;
        }

        $this->fs->file($this->path)->open('cwt')->then(function ($stream) {
            $stream->end(json_encode($this->data));
        });

        $this->dirty = false;
    }

    public function getBreadScore(string $userId): int
    {
        return $this->data['scores'][$userId] ?? 0;
    }

    public function storeBreadScore(string $userId, int $bread)
    {
        $this->dirty = true;

        $this->data['scores'][$userId] = $bread;
    }

    public function removeBreadScore(string $userId)
    {
        $this->dirty = true;

        unset($this->data['scores'][$userId]);
    }

    public function getTop(int $places = 5): array
    {
        arsort($this->data['scores'], SORT_DESC & SORT_NUMERIC);

        return array_slice($this->data['scores'], 0, $places, true);
    }
}
