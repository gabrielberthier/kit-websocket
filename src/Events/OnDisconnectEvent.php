<?php

declare(strict_types=1);

namespace Kit\Websocket\Events;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Events\Protocols\Event;

final readonly class OnDisconnectEvent extends Event
{
    public function __construct(public Connection $connection)
    {
    }
}
