<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Exception thrown when the server responded with a 400 status code.
 */
class InvalidInputErrorResponseException extends Exception implements ErrorResponseException
{
    public function __construct(
        public readonly RequestInterface $request,
        readonly ?string $errorMessage = null,
        readonly ?int    $errorCode = null,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        $message = $message ?? "Api responded with an invalid input error ($errorCode) '$errorMessage'";

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
