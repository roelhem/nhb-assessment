<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions;

use Throwable;

/**
 * @template TInput The input type of the calculation.
 */
interface CalcException extends Throwable
{
    /**
     * Gives the input object of the performed calculation that resulted in this exception.
     *
     * @return TInput
     */
    public function getCalcInput(): mixed;
}
