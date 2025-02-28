<?php

namespace Brash\Websocket\Message\Validation;
use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Message\Message;


abstract class AbstractMessageValidator implements ValidationChainInterface
{

    protected ?ValidationChainInterface $nextHandler = null;

    #[\Override]
    public function validate(Message $message, Frame $frame): ValidationResult
    {
        return $this->nextHandler?->validate($message, $frame) ?? new ValidationResult(
            successfulMessage: $message
        );
    }

    #[\Override]
    public function setNext(ValidationChainInterface $next): ValidationChainInterface
    {
        $this->nextHandler = $next;

        return $next;
    }
}
