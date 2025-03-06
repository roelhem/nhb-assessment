<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByValue;

use BcMath\Number;

interface CalcProvider
{
    public function calcMaximumByValue(Input $input): Number;
}
