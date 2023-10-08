<?php

namespace Exan\Bread\Contracts;

interface ServerConfigRepositoryInterface
{
    public function get(string $id): ServerConfigInterface;
}
