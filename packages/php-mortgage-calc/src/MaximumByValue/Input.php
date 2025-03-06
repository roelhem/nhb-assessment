<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByValue;

use BcMath\Number;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Traits\HasImmutableProperties;

readonly class Input
{
    use HasImmutableProperties;

    public function __construct(
        public Number $objectValue,
        public int $durationInMonths = 360,
        public int $notDeducibleInMonths = 0,
        public bool $onlyUseIncludedLabels = false,
    )
    {
        assert($objectValue->compare(0) > 0, 'objectValue is positive.');
        assert($objectValue->scale <= 2, 'objectValue has a maximum precision of 2.');
        assert($this->durationInMonths > 0, 'durationInMonths is positive.');
        assert($this->notDeducibleInMonths >= 0, 'notDeducibleInMonths is non-negative.');
    }
}
