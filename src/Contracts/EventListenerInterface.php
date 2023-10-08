<?php

namespace Exan\Bread\Contracts;

use React\Promise\ExtendedPromiseInterface;

interface EventListenerInterface
{
    public function filter(): ExtendedPromiseInterface;

    public function execute(): void;
}
