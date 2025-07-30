<?php

namespace MrNewport\LaravelPlaid\Exceptions;

use Exception;
use Throwable;

class PlaidException extends Exception
{
    protected string $errorCode;
    protected string $errorType;
    protected ?array $displayMessage;
    protected ?string $requestId;

    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        string $errorCode = '',
        string $errorType = '',
        ?array $displayMessage = null,
        ?string $requestId = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorCode = $errorCode;
        $this->errorType = $errorType;
        $this->displayMessage = $displayMessage;
        $this->requestId = $requestId;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getDisplayMessage(): ?array
    {
        return $this->displayMessage;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }
}