<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;

use BcMath\Number;

interface CalcProvider
{
    public function calcMaximumByIncome(Input $input): Number;
}
