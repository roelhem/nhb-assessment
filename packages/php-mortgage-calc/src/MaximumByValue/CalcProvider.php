<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByValue;

use BcMath\Number;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions\CalcErrorException;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions\CalcInputException;

interface CalcProvider
{
    /**
     * @param Input $input
     * @return Number
     * @throws CalcErrorException<Input>
     * @throws CalcInputException<Input>
     */
    public function calcMaximumByValue(Input $input): Number;
}
