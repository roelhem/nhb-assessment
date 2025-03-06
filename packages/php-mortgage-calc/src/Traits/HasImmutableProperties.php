<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Traits;

trait HasImmutableProperties
{
    public function with(...$values): static
    {
        assert(array_all($values, fn($val, $key) => is_string($key)), 'Value parameters are named.');

        return new static(...[
            ...get_object_vars($this),
            ...$values,
        ]);
    }
}
