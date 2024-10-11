<?php

declare(strict_types=1);

namespace Kit\Websocket\Message;

use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Message\Exceptions\LimitationException;
use Kit\Websocket\Message\Exceptions\MissingDataException;
use Kit\Websocket\Message\Exceptions\WrongEncodingException;
use function Kit\Websocket\functions\removeStart;


final class Message
{
    /**
     * It allows ~50MiB buffering as the default of Frame content is 0.5MB
     */
    const int MAX_MESSAGES_BUFFERING = 100;

    /**
     * @var \Kit\Websocket\Frame\Frame[]
     */
    private array $frames;
    private bool $isComplete;
    private string $buffer;
    private int $maxMessagesBuffering;

    private bool $isContinuationMessage = false;

    public function __construct(?int $maxMessagesBuffering = null)
    {
        $this->frames = [];
        $this->isComplete = false;
        $this->buffer = '';
        $this->maxMessagesBuffering = $maxMessagesBuffering ?? self::MAX_MESSAGES_BUFFERING;
    }

    public function addBuffer($data)
    {
        $this->buffer .= $data;
    }

    public function clearBuffer()
    {
        $this->buffer = '';
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    public function removeFromBuffer(Frame $frame): string
    {
        $this->buffer = removeStart($this->buffer, $frame->getRawData());

        return $this->buffer;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function addFrame(Frame $frame): ?\Exception
    {
        if ($this->isComplete) {
            throw new \InvalidArgumentException('The message is already complete.');
        }

        if (count($this->frames) > $this->maxMessagesBuffering) {
            return new LimitationException(
                sprintf('We don\'t accept more than %s frames by message. This is a security limitation.', $this->maxMessagesBuffering)
            );
        }

        $this->isComplete = $frame->isFinal();
        $this->frames[] = $frame;

        if ($this->isComplete() && !$this->validDataEncoding()) {
            return new WrongEncodingException('The text is not encoded in UTF-8.');
        }

        return null;
    }

    /**
     * Validates the current encoding, as WebSockets only allow UTF-8
     */
    private function validDataEncoding(): bool
    {
        $firstFrame = $this->getFirstFrame();
        $valid = true;

        if ($firstFrame->getOpcode() === FrameTypeEnum::Text || $firstFrame->getOpcode() === FrameTypeEnum::Close) {
            $valid = \mb_check_encoding($this->getContent(), 'UTF-8');
        }

        return $valid;
    }

    /**
     * @return Frame
     * @throws MissingDataException
     */
    public function getFirstFrame(): Frame
    {
        if (empty($this->frames[0])) {
            throw new MissingDataException('There is no first frame for now.');
        }

        return $this->frames[0];
    }

    /**
     * This could in the future be deprecated in favor of a stream object.
     * @throws MissingDataException
     */
    public function getContent(): string
    {
        if (!$this->isComplete) {
            throw new MissingDataException('The message is not complete. Frames are missing.');
        }

        $res = '';

        foreach ($this->frames as $frame) {
            $res .= $frame->getContent();
        }

        return $res;
    }

    public function getOpcode(): FrameTypeEnum
    {
        return $this->getFirstFrame()->getOpcode();
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->isComplete;
    }

    public function isOperation(): bool
    {
        return $this->getFirstFrame()->getOpcode()->isOperation();
    }

    /**
     * @return Frame[]
     */
    public function getFrames(): array
    {
        return $this->frames;
    }

    public function hasFrames(): bool
    {
        return !empty($this->frames);
    }


    public function countFrames(): int
    {
        return \count($this->frames);
    }

    public function makeItContinuationMessage(): void
    {
        $this->isContinuationMessage = true;
    }

    public function isContinuationMessage(): bool
    {
        return $this->isContinuationMessage;
    }
}