<?php

namespace Exan\Bread\Contracts;

interface ServerConfigInterface
{
    public function getBreadScore(string $userId): int;

    public function storeBreadScore(string $userId, int $bread);

    /**
     * @return BreadScoreInterface[]
     */
    public function getTop(int $places = 5): array;
}
