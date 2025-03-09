<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Tests\Unit\MaximumByIncome;

use BcMath\Number;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\Input;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\Person;
use Tests\TestCase;

class InputTest extends TestCase
{

    public static function equivalentInputs(): array
    {
        $basePerson = new Person(
            yearlyIncome: new Number(0),
            dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
        );

        $base = new Input(
            calculationDate: DateTime::createFromFormat('Y-m-d', '2025-01-01'),
            mainPerson: $basePerson,
        );

        return [
            'same_instance' => [
                'a' => $base,
                'b' => $base
            ],
            'same_values' => [
                'a' => $base,
                'b' => $base->with()
            ],
            'same_main_person' => [
                'a' => $base->withMainPerson(yearlyIncome: new Number(0)),
                'b' => $base->withMainPerson(yearlyIncome: new Number(0)),
            ],
            'with_partner_person' => [
                'a' => $base->withPartnerPerson(
                    yearlyIncome: new Number(0),
                    dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
                ),
                'b' => $base->withPartnerPerson(
                    yearlyIncome: new Number(0),
                    dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
                )
            ],
        ];
    }

    #[DataProvider('equivalentInputs')]
    public function test_equals_returnsTrueWhenEquivalent(Input $a, Input $b)
    {
        $this->assertTrue($a->equals($b));
    }

    public static function nonEquivalentInputs(): array
    {
        $basePerson = new Person(
            yearlyIncome: new Number(0),
            dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
        );

        $base = new Input(
            calculationDate: DateTime::createFromFormat('Y-m-d', '2025-01-01'),
            mainPerson: $basePerson,
        );

        return [
            'different_calculation_date' => [
                'a' => $base,
                'b' => $base->with(calculationDate: DateTime::createFromFormat('Y-m-d', '2024-01-01')),
            ],
            'different_main_person' => [
                'a' => $base->withMainPerson(yearlyIncome: new Number(0)),
                'b' => $base->withMainPerson(yearlyIncome: new Number(1)),
            ],
            'one_without_partner_person' => [
                'a' => $base->withPartnerPerson(
                    yearlyIncome: new Number(0),
                    dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01')
                ),
                'b' => $base->withoutPartnerPerson(),
            ],
            'different_partner_person' => [
                'a' => $base->withPartnerPerson(
                    yearlyIncome: new Number(0),
                    dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01')
                ),
                'b' => $base->withPartnerPerson(
                    yearlyIncome: new Number(1),
                    dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01')
                ),
            ],
        ];
    }

    #[DataProvider('nonEquivalentInputs')]
    public function test_equals_returnsFalseWhenNotEquivalent(Input $a, Input $b)
    {
        $this->assertFalse($a->equals($b));
    }
}
