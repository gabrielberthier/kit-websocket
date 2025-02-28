<?php


namespace Brash\Websocket\Message\Handlers;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Message\Message;
use Brash\Websocket\Message\Protocols\MessageHandlerInterface;

class MessageDeflateProxyHandler implements MessageHandlerInterface
{
    private ?MessageHandlerInterface $handler = null;

    public function proxy(MessageHandlerInterface $messageHandlerInterface): self
    {
        $this->handler = $messageHandlerInterface;

        return $this;
    }
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function hasSupport(Message $message): bool
    {
        return $this->handler->hasSupport($message);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function handle(Message $message, Connection $connection): void
    {
        $op = $message->getOpcode();
        if ($op === FrameTypeEnum::Text || $op === FrameTypeEnum::Binary) {
            $inflatedMessage = new Message();
            foreach ($message->getFrames() as $frame) {
                $compressedFrame = $connection->getCompression()->inflateFrame($frame);
                $inflatedMessage->addFrame($compressedFrame);
            }

            $message = $inflatedMessage;
        }

        $this->handler->handle($message, $connection);
    }
}
