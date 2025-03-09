<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc;

use BcMath\Number;
use DateTimeInterface;
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
            if(preg_match('/^[+-]?[0-9]*(\.[0-9]*)?$/', $value) === 0) {
                throw new InvalidArgumentException('Cannot convert "'.$value.'" to a currency number.');
            }

            return new Number($value)->round(2); // LONG LIVE THE NEW PHP 8.4 PARSER!!!!
        }

        if(is_float($value)) {
            return new Number(number_format($value, 2, '.', ''));
        }

        if(is_int($value)) {
            return new Number($value);
        }

        throw new InvalidArgumentException('Cannot convert a '.gettype($value).' to a currency number.');
    }

    /**
     * Checks whether two currency numbers are equal to each other (after rounding.)
     *
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    function currencyNumberEquals(mixed $a, mixed $b): bool
    {
        $a = toCurrencyNumber($a);
        $b = toCurrencyNumber($b);

        return 0 === $a->compare($b, 2);
    }

    /**
     * Checks whether the provided values are at the same day.
     *
     * @param DateTimeInterface|null $a
     * @param DateTimeInterface|null $b
     * @return bool
     */
    function dateEquals(DateTimeInterface|null $a, DateTimeInterface|null $b): bool
    {
        return $a?->format('Ymd') === $b?->format('Ymd');
    }
}
