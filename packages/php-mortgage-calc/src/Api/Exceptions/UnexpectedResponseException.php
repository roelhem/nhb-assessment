<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Error thrown when the response of the api server was unexpected.
 *
 * This exception usually indicates that there is a problem with the expectations of the application. It is
 * generally not possible to handle this error gracefully.
 */
class UnexpectedResponseException extends Exception
{
    public function __construct(
        public readonly RequestInterface $request,
        public readonly ?ResponseInterface $response = null,
        public readonly mixed $responseData = null,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        $message ??= "Failed to interpret response of request: ".$request->getMethod().' '.$request->getUri();

        parent::__construct($message, $code, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
