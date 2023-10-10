<?php

namespace Exan\Bread;

use Exan\Bread\Contracts\ServerConfigInterface;
use Exan\Bread\Contracts\ServerConfigRepositoryInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;
use SplObjectStorage;

use function React\Async\await;
use function React\Promise\all;

class FsServerConfigRepository implements ServerConfigRepositoryInterface
{
    private const FILE_EXTENSION = '.json';

    private Filesystem $fs;
    private array $configs;

    public function __construct(
        private string $path,
        private LoopInterface $loop,
    ) {
        $this->fs = Filesystem::create($this->loop);
    }

    public function initialize(): ExtendedPromiseInterface
    {
        return new Promise(function (callable $resolve) {
            /** @var SplObjectStorage */
            $dir = await($this->fs->dir($this->path)->ls());

            $paths = array_map(
                function ($entry) {
                    $fullPath = $entry->getPath();

                    $serverId = substr(
                        $fullPath,
                        strlen($this->path) + 1, // + 1 for added slash
                        (0 - strlen(self::FILE_EXTENSION))
                    );

                    return [$serverId => $fullPath];
                },
                iterator_to_array($dir),
            );

            $paths = array_merge(...$paths);

            await($this->importPaths($paths));

            $resolve();
        });
    }

    private function importPaths(array $paths): ExtendedPromiseInterface
    {
        return new Promise(function ($resolve) use ($paths) {
            $fileReadings = array_map(fn ($path) => $this->getFullFileContents($path), $paths);
            $contents = await(all($fileReadings));

            foreach ($paths as $guildId => $configPath) {
                $this->configs[$guildId] = new FsServerConfig(
                    $this->fs,
                    $this->loop,
                    $configPath,
                    json_decode($contents[$guildId], true),
                );
            }

            $resolve();
        });
    }

    private function getFullFileContents(string $filePath): ExtendedPromiseInterface
    {
        return new Promise(function ($resolve) use ($filePath) {
            $this->fs->file($filePath)->open('r')->then(function ($stream) use ($resolve) {
                $buffer = '';

                $stream->on('data', function ($data) use (&$buffer) {
                    $buffer .= $data;
                });

                $stream->on('end', function () use ($stream, &$buffer, $resolve){
                    $stream->close();

                    $resolve($buffer);
                });
            });
        });
    }

    public function get(string $id): ServerConfigInterface
    {
        return $this->configs[$id] ?? $this->configs[$id] = new FsServerConfig(
            $this->fs,
            $this->loop,
            $this->getConfigPathByGuildId($id),
            ['scores' => []]
        );
    }

    private function getConfigPathByGuildId(string $guildId): string
    {
        return $this->path . '/' . $guildId . self::FILE_EXTENSION;
    }
}
