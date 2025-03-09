<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Throwable;


/**
 * Error thrown when the server responded with a 500 status code.
 */
class UnexpectedErrorResponseException extends Exception implements ErrorResponseException
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
        $message = $message ?? "Api responded with an unexpected error ($errorCode) '$errorMessage'.";

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
