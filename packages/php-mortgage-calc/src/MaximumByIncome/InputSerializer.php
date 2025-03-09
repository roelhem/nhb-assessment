<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;

interface InputSerializer
{
    public function serialize(Input $input): string;

    public function deserialize(string $input): Input;
}
