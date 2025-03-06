<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc;

use BcMath\Number;
use InvalidArgumentException;

if(!function_exists('toCurrencyNumber')) {

    /**
     * Converts a value to a BCMath Number with a maximum of 2 decimal places
     *
     * @param mixed $value
     * @return Number
     */
    function toCurrencyNumber(mixed $value): Number {
        if($value instanceof Number) {
            return $value->round(2);
        }

        // NOTE: I do not use an assert here, as we probably want this to throw in production as well. I do not always
        //       trust the PHP type cast rules and I assume that errors are usually preferred over to invalid values in
        //       this use case.
        if(is_string($value)) {
            if(preg_match('/^[+-]?[0-9]*(\.[0-9]*)?$/', $value) === false) {
                throw new InvalidArgumentException('Cannot convert "'.$value.'" to a currency number.');
            }

            return new Number($value);
        }

        if(is_float($value)) {
            return new Number(number_format($value, 2, '.', ''));
        }

        if(is_int($value)) {
            return new Number($value);
        }

        throw new InvalidArgumentException('Cannot convert a '.gettype($value).' to a currency number.');
    }
}
