<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Tests\Unit\MaximumByIncome;

use BcMath\Number;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\Person;

class PersonTest extends TestCase
{
    public static function equivalentPersons(): array
    {
        $base = new Person(
            yearlyIncome: new Number(0),
            dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
        );

        return [
            "same_instance" => [
                'a' => $base,
                'b' => $base,
            ],
            "same_yearly_income" => [
                'a' => $base->with(yearlyIncome: new Number('1')),
                'b' => $base->with(yearlyIncome: new Number('1')),
            ],
            'same_private_lease_amounts_same_order' => [
                'a' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('1'),
                    new Number('2'),
                ]),
                'b' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('1'),
                    new Number('2'),
                ]),
            ],
            'same_private_lease_amounts_different_order' => [
                'a' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('1'),
                    new Number('2'),
                ]),
                'b' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('2'),
                    new Number('1'),
                ]),
            ],
        ];
    }

    #[DataProvider('equivalentPersons')]
    public function test_equals_returnsTrueWhenEquivalent(Person $a, Person $b)
    {
        $this->assertTrue($a->equals($b));
    }

    public static function notEquivalentPersons(): array
    {
        $base = new Person(
            yearlyIncome: new Number(0),
            dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
        );

        return [
            'different_type' => [
                'a' => $base,
                'b' => false,
            ],
            'different_yearly_income' => [
                'a' => $base,
                'b' => $base->with(yearlyIncome: new Number('1')),
            ],
            'different_birth_date' => [
                'a' => $base,
                'b' => $base->with(dateOfBirth: DateTime::createFromFormat('Y-m-d', '1995-01-01')),
            ],
            'different_private_lease_amounts_count' => [
                'a' => $base,
                'b' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('1'),
                ])
            ],
            'different_private_lease_amounts_one_value' => [
                'a' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('1')
                ]),
                'b' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('2'),
                ])
            ],
            'different_private_lease_amounts_one_of_two_values' => [
                'a' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('1'),
                    new Number('2')
                ]),
                'b' => $base->with(privateLeaseMonthlyAmounts: [
                    new Number('1'),
                    new Number('3'),
                ])
            ]
        ];
    }

    #[DataProvider('notEquivalentPersons')]
    public function test_equals_returnsFalseWhenNotEquivalent(Person $a, mixed $b)
    {
        $this->assertFalse($a->equals($b));
    }
}
