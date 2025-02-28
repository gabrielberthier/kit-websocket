<?php

namespace Brash\Websocket\Http;

use Psr\Http\Message\UriInterface;

/**
 * Net\UriFactory class.
 */
class UriFactory
{
    // ---------- PSR-7 methods ---------------------------------------------------------------------------------------

    /**
     * Create a new URI.
     * @param string $uri The URI to parse.
     * @throws \InvalidArgumentException If the given URI cannot be parsed
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }


    // ---------- Extensions ------------------------------------------------------------------------------------------

    /**
     * Create a new URI from existing.
     * @param UriInterface $uri A URI instance to create from.
     * @throws \InvalidArgumentException If the given URI cannot be parsed
     */
    public function createUriFromInterface(UriInterface $uri): UriInterface
    {
        return new Uri($uri->__toString());
    }
}
