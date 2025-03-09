<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions;

use Exception;
use Throwable;

/**
 * Error thrown by a calculation provider when an error occurred during the calculation.
 *
 * In most cases, this error signals that the configuration of the application is invalid or that there is some bug
 * in the application itself. It is not likely that the input of the calculation has caused this error to occur.
 *
 * @template TInput The input type of the calculation that throws these errors.
 *
 * @implements CalcException<TInput|null>
 */
class CalcErrorException extends Exception implements CalcException
{
    /**
     * @param TInput|null $input
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        private readonly mixed $input = null,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

    public function getCalcInput(): mixed
    {
        return $this->input;
    }
}
