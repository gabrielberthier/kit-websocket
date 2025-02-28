<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame;

use Brash\Websocket\Frame\DataManipulation\Functions\BytesFromToStringFunction;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Frame\Enums\InspectionFrameEnum;

use function Brash\Websocket\functions\frameSize;
use function Brash\Websocket\functions\intToBinaryString;

class Frame
{
    public function __construct(
        private readonly FrameTypeEnum $opcode,
        private readonly FrameMetadata $metadata,
        private readonly FramePayload $framePayload,
    ) {
    }

    public function getOpcode(): FrameTypeEnum
    {
        return $this->opcode;
    }

    public function getMetadata(): FrameMetadata
    {
        return $this->metadata;
    }

    public function getFramePayload(): FramePayload
    {
        return $this->framePayload;
    }

    public function getRawData(): string
    {
        $data = '';
        $firstLen = $this->framePayload->getFirstLength();
        $secondLen = $this->framePayload->getSecondLength();

        // Build the initial portion of the data
        $data .= $this->buildInitialDataPortion($firstLen);

        // Append second length if necessary
        if (!is_null($secondLen)) {
            $data .= intToBinaryString($secondLen, $firstLen === 126 ? 2 : 8);
        }

        // Handle masking
        if ($this->framePayload->isMasked()) {
            $data .= $this->framePayload->getMaskingKey();
        }

        $data .= $this->framePayload->getRawPayload();

        return $data;
    }

    public function isFinal(): bool
    {
        return $this->metadata->fin;
    }

    public function isControlFrame(): bool
    {
        return $this->opcode->isControlFrame();
    }

    private function buildInitialDataPortion(int $firstLen): string
    {
        $newHalfFirstByte = (intval($this->isFinal()) << 7) + (intval($this->metadata->rsv1) << 6);
        $newFirstByte = ($newHalfFirstByte + $this->opcode->value) << 8;
        $newSecondByte = ($this->framePayload->isMasked() << 7) + $firstLen;

        return intToBinaryString($newFirstByte + $newSecondByte);
    }

    /**
     * Returns the content and not potential metadata of the body.
     * If you want to get the real body you will prefer using `getPayload`
     *
     * @return string
     */
    public function getContent(): string
    {
        $payload = $this->getPayload();
        if (in_array($this->getOpcode(), [FrameTypeEnum::Text, FrameTypeEnum::Binary], strict: true)) {
            return $payload;
        }

        $len = frameSize($payload);
        if ($len !== 0 && $this->getOpcode() === FrameTypeEnum::Close) {
            return BytesFromToStringFunction::getBytesFromToString(
                $payload,
                0,
                $len,
                inspectionFrameEnum: InspectionFrameEnum::MODE_FROM_TO
            );
        }

        return $payload;
    }

    public function getPayload(): string
    {
        return $this->framePayload->getPayload();
    }
}
