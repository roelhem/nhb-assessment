<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Throwable;

class ForbiddenResponseException extends Exception implements ErrorResponseException
{
    public function __construct(
        public readonly RequestInterface $request,
        public readonly string $errorMessage,
        public readonly int $errorCode,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        $message = $message ?? "Api returned a Forbidden response: ($errorCode) '$errorMessage'.";

        parent::__construct($message, $code, $previous);
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
