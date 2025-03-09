<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions;

use Exception;
use Throwable;

/**
 * Error thrown by a calculation provider when an error occurred during the calculation that is likely caused by an
 * invalid combination of input variables. (Thus re-invoking the calculation with adjusted inputs might result in
 * a successful response).
 *
 * @template TInput The input type of the calculation that throws these errors.
 *
 * @implements CalcException<TInput>
 */
class CalcInputException extends Exception implements CalcException
{

    /**
     * @param TInput $input
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        private readonly mixed $input,
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
