<?php

namespace Kit\Websocket\Utilities;

use function sha1;
use function base64_encode;

final class KeyDigest
{
    const WEBSOCKET_MAGIC_STRING_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    public function createSocketAcceptKey(string $id): string
    {
        $socketMagicString = self::WEBSOCKET_MAGIC_STRING_KEY;
        $hash = sha1("{$id}{$socketMagicString}", true);
        

        return base64_encode($hash);
    }
}

