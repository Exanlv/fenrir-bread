<?php

namespace Exan\Bread\Contracts;

interface BreadScoreInterface
{
    public function getUserId(): string;

    public function getScore(): int;
}
