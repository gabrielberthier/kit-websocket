<?php

declare(strict_types=1);

namespace Brash\Websocket\Message\Validation;

use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Frame\FrameValidation\ValidationUponOpCode;
use Brash\Websocket\Message\Message;

final class ValidateOpCode extends AbstractMessageValidator
{
    #[\Override]
    public function validate(Message $message, Frame $frame): ValidationResult
    {
        $validation = new ValidationUponOpCode();
        $result = $validation->validate($frame);

        if (is_null($result)) {
            if ($frame->isControlFrame() && $message->hasFrames()) {
                $controlFrameMessage = new Message();
                $controlFrameMessage->addFrame($frame);
                $controlFrameMessage->makeItContinuationMessage();

                return new ValidationResult($controlFrameMessage);
            }
            
            return parent::validate($message, $frame);
        }

        return new ValidationResult(error: $result);
    }
}
