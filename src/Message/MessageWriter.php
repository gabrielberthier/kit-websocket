<?php

namespace Brash\Websocket\Message;

use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Frame\FrameFactory;
use React\Socket\ConnectionInterface;
use React\Stream\DuplexStreamInterface;

class MessageWriter
{
    public function __construct(
        private readonly FrameFactory $frameFactory,
        private readonly DuplexStreamInterface $socket,
        private readonly bool $writeMasked = false,
    ) {
    }

    public function write(string $data): void
    {
        $this->socket->write($data);
    }

    public function writeFrame(Frame|string $frame, FrameTypeEnum $opCode): void
    {
        if (!$frame instanceof Frame) {
            $frame = $this->frameFactory->newFrame(
                payload: $frame,
                frameTypeEnum: $opCode,
                writeMask: $this->writeMasked
            );
        }

        $this->socket->write($frame->getRawData());
    }

    public function writeTextFrame(Frame|string $frame)
    {
        $this->writeFrame($frame, FrameTypeEnum::Text);
    }


    public function getFrameFactory(): FrameFactory
    {
        return $this->frameFactory;
    }

    public function getProcessConnection(): ConnectionInterface
    {
        return $this->socket;
    }

    public function close(
        CloseFrameEnum $status = CloseFrameEnum::CLOSE_NORMAL,
        string $reason = null
    ): void {
        $closeFrame = $this->frameFactory->createCloseFrame($status, $reason);
        $this->writeTextFrame($closeFrame);

        $this->socket->end();
    }

    public function writeExceptionCode(CloseFrameEnum $closeCode)
    {
        $closeFrame = $this->frameFactory->createCloseFrame(status: $closeCode);
        $this->writeTextFrame($closeFrame);
        $this->socket->end();
    }
}
