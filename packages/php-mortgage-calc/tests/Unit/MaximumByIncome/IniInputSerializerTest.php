<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Tests\Unit\MaximumByIncome;

use BcMath\Number;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\EnergyLabel;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\IniInputSerializer;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\Input;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\Person;

class IniInputSerializerTest extends TestCase
{
    public static function inputCases(): array
    {

        $base = new Input(
            calculationDate: DateTime::createFromFormat('Y-m-d', '2024-01-01'),
            mainPerson: new Person(
                dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
                yearlyIncome: new Number('0'),
            )
        );

        return [
            'base' => [$base],
            'with_partner_person' => [
                $base->withPartnerPerson(dateOfBirth: DateTime::createFromFormat('Y-m-d', '1999-01-01')),
            ],
            'with_one_private_lease_amounts_for_main_person' => [
                $base->withMainPerson(privateLeaseMonthlyAmounts: [
                    new Number('1.01')
                ])
            ],
            'with_two_private_lease_amounts_for_main_person' => [
                $base->withMainPerson(privateLeaseMonthlyAmounts: [
                    new Number('1.01'),
                    new Number('0.08')
                ])
            ],
            'with_different_interest_percentage' => [
                $base->with(interestPercentage: new Number('2.534425433'))
            ],
            'with_nhg_false' => [
                $base->with(nhg: false)
            ],
            'with_different_duration_in_in_months' => [
                $base->with(durationInMonths: 1)
            ],
            'with_different_energy_label' => [
                $base->with(energyLabel: EnergyLabel::Apppp_WithEnergyPerformanceGuarantee)
            ]
        ];
    }

    #[DataProvider('inputCases')]
    public function test_serializeAndDeserialize_resultInEquivalentInput(Input $input)
    {
        $serializer = new IniInputSerializer(
            defaultInput: new Input(
                calculationDate: DateTime::createFromFormat('Y-m-d', '2025-01-01'),
                mainPerson: new Person(
                    dateOfBirth: DateTime::createFromFormat('Y-m-d', '1970-01-01'),
                    yearlyIncome: new Number('0'),
                )
            )
        );

        $serialized = $serializer->serialize($input);
        $restoredInput = $serializer->deserialize($serialized);

        $this->assertTrue($input->equals($restoredInput));
    }
}
