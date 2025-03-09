<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;

use BcMath\Number;
use DateTime;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini\IniOptions;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini\IniReader;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini\IniReaderValueException;

readonly class IniInputSerializer implements InputSerializer
{
    public function __construct(
        public Input $defaultInput,
        public IniOptions $iniOptions = new IniOptions,
    )
    {
    }


    protected function serializeBool(bool $value): string
    {
        return $value ? $this->iniOptions->true : $this->iniOptions->false;
    }

    public function serialize(Input $input): string
    {
        $generalStr = $this->serializeGeneral($input);
        $mainPersonStr = $this->serializePerson(
            person: $input->mainPerson,
            headerPrefix: 'PERSOONSGEGEVENS'
        );

        $partnerPerson = $input->partnerPerson;
        $partnerPersonStr = $this->serializePerson(
            person: $partnerPerson ?? new Person(
                dateOfBirth: DateTime::createFromFormat('Y-m-d', '1996-01-01'),
                yearlyIncome: new Number(0),
            ),
            headerPrefix: 'PERSOONSGEGEVENS PARTNER'
        );
        $partnerPersonEnabledStr = $this->serializeBool($input->hasPartnerPerson());

        return <<<INI
            $generalStr

            ;; -- PERSOONSGEGEVENS --
            ;; Stel hieronder de gegevens in van de persoon die de hypotheek afsluit.
            [main_person]
            $mainPersonStr

            ;; -- PERSOONSGEGEVENS PARTNER --
            ;; Stel hieronder de gegevens in van de partner van de bovenstaande persoon.
            [partner_person]
            ;; Moeten de persoonsgegevens van de partner meegenomen worden in de berekening?
            enable = $partnerPersonEnabledStr
            $partnerPersonStr
            INI;

    }

    private function serializeGeneral(Input $input): string
    {
        $calculationDateStr = $input->calculationDate->format('Y-m-d');
        $nhgStr = $this->serializeBool($input->nhg);
        $durationInMonthsStr = strval($input->durationInMonths);
        $interestPercentageStr = $input->interestPercentage->value;
        $rateFixationInYearsStr = strval($input->rateFixationInYears);
        $notDeductibleAmountStr = $input->notDeductibleAmount->value;
        $groundRentAmountStr = $input->groundRentAmount->value;
        $energyLabelsStr = implode("\n", array_map(
            function(EnergyLabel $energyLabel) use ($input) {
                $str = "object.energy_label = $energyLabel->value";
                if($input->energyLabel === $energyLabel) {
                    return $str;
                } else {
                    return "; $str";
                }
            },
            EnergyLabel::cases()
        ));

        return <<<INI
            [top]
            ;; -- BEREKENING --
            ;; De datum voor berekening (yyyy-mm-dd).
            calculation.date = $calculationDateStr

            ;; -- HYPOTHEEK --
            ;; Is de 'nationale hypotheek garantie' van toepassing?
            mortgage.nhg                    = $nhgStr
            ;; De duur van de hypotheek in maanden.
            mortgage.duration_in_months     = $durationInMonthsStr
            ;; Het rentepercentage van de hypotheek.
            mortgage.interest_percentage    = $interestPercentageStr
            ;; De rentevaste periode van de hypotheek in jaren.
            mortgage.rate_fixation_in_years = $rateFixationInYearsStr
            ;; Het niet aftrekbare deel van de hypotheek in euros.
            mortgage.not_deductible_amount  = $notDeductibleAmountStr

            ;; -- OBJECT --
            ;; De jaarlijkste erfpacht van het gebouw per jaar.
            object.ground_rent.amount_per_year = $groundRentAmountStr
            ;; Het energielabel van het object. Zet een ';' voor de regels die NIET van toepassing zijn.
            $energyLabelsStr
            INI;
    }

    private function serializePerson(Person $person, string $headerPrefix): string
    {
        $yearlyIncomeStr = $person->yearlyIncome->value;
        $dateOfBirthStr = $person->dateOfBirth->format('Y-m-d');
        $alimonyPerYearStr = $person->alimonyPerYear->value;
        $totalLoansAmountStr = $person->totalLoansAmount->value;
        $studentLoanAmountStr = $person->studentLoanAmount->value;
        $studentLoanMonthlyAmountStr = $person->studentLoanMonthlyAmount->value;
        $studentLoanStartDateStr = $person->studentLoanStartDate?->format('Y-m-d') ?? '';
        $privateLeaseMonthlyAmounts = implode(' ', array_map(
            fn(Number $number) => $number->value,
            $person->privateLeaseMonthlyAmounts
        ));

        return <<<INI
            ;; De geboortedatum van deze persoon (yyyy-mm-dd).
            date_of_birth = $dateOfBirthStr

            ;; -- $headerPrefix: INKOMEN --
            ;; Het jaarinkomen van deze persoon in euros.
            income.yearly.amount = $yearlyIncomeStr
            ;; In plaats van het jaarinkomen kun je ook het maandinkomen invullen.
            ;; Plaats hiervoor een ';' voor 'income.yearly' en haal de ';' weg voor de
            ;; onderstaande regels:
            ; income.monthly.amount             = 0.00
            ; income.monthly.holliday_allowance = yes
            ; income.monthly.thirtheenth_month  = no

            ;; -- $headerPrefix: ALLUMENTATIE --
            ;; De hoeveelheid allumentatie die deze persoon moet betalen per jaar in euros.
            alimony.amount_per_year = $alimonyPerYearStr

            ;; -- $headerPrefix: STUDIESCHULD --
            ;; De totale studieschuld van deze persoon in euros.
            student_loan.total_amount               = $studentLoanAmountStr
            ;; De hoogte van de maandelijkse aflossing van studieschuld van deze persoon in euros.
            student_loan.repayment_amount_per_month = $studentLoanMonthlyAmountStr
            ;; De startdatum van de studieschuld.
            student_loan.start_date                 = $studentLoanStartDateStr

            ;; -- $headerPrefix: OVERIGE SCHULDEN --
            ;; De totale hoeveelheid in openstaande leningen van deze persoon in euros.
            loans.total_amount = $totalLoansAmountStr

            ;; -- $headerPrefix: PRIVATE LEASE --
            ;; De hoeveelheid prive-lease betalingen per maand in euros. Meerdere waarden
            ;; kunnen gegeven worden door ze te scheiden met een spatie.
            private_lease.amounts_per_month = $privateLeaseMonthlyAmounts
            INI;
    }

    /**
     * @throws IniReaderValueException
     */
    private function readPerson(IniReader $reader, ?Person $default): Person
    {
        $result = new Person(
            dateOfBirth: $reader->readRequiredDate('date_of_birth'),
            yearlyIncome: $reader->readNonNegativeCurrencyNumber(
                'income.yearly.amount',
                $default?->yearlyIncome
            ),
            alimonyPerYear: $reader->readNonNegativeCurrencyNumber(
                'income.amount_per_year',
                $default?->alimonyPerYear
            ),
            totalLoansAmount: $reader->readNonNegativeCurrencyNumber(
                'loans.total_amount',
                $default?->totalLoansAmount
            ),
            studentLoanAmount: $reader->readNonNegativeCurrencyNumber(
                'student_loan.total_amount',
                $default?->studentLoanAmount
            ),
            studentLoanMonthlyAmount: $reader->readNonNegativeCurrencyNumber(
                'student_loan.repayment_amount_per_month',
                $default?->studentLoanMonthlyAmount
            ),
            studentLoanStartDate: $reader->readDate(
                'student_loan.start_date',
                $default?->studentLoanStartDate
            ),
            privateLeaseMonthlyAmounts: $reader->readNonNegativeCurrencyNumbers('private_lease.amounts_per_month'),
        );

        if(!$reader->hasValueFor('income.yearly.amount') && $reader->hasValueFor('income.monthly.amount')) {
            return $result->withMonthlyIncome(
                monthlyIncome: $reader->readNonNegativeCurrencyNumber('income.monthly.amount'),
                holidayAllowance: $reader->readBool('income.monthly.holiday_allowance', true),
                thirteenthMonth: $reader->readBool('income.monthly.thirteenth_month'),
            );
        } else {
            return $result;
        }
    }

    /**
     * @throws IniReaderValueException
     */
    public function deserialize(string $input): Input
    {
        $reader = IniReader::fromString($input, $this->iniOptions);
        $r = $reader->section('top');

        $mainPerson = $this->readPerson(
            $reader->section('main_person'),
            $this->defaultInput->mainPerson
        );

        $partnerPerson = null;
        $partnerPersonSection = $reader->section('partner_person');
        if($partnerPersonSection->readBool('enable')) {
            $partnerPerson = $this->readPerson(
                $partnerPersonSection,
                $this->defaultInput->partnerPerson
            );
        }

        return new Input(
            calculationDate: $r->readDate(
                'calculation.date',
                $this->defaultInput->calculationDate
            ),
            mainPerson: $mainPerson,
            partnerPerson: $partnerPerson,
            nhg: $r->readBool('mortgage.nhg', $this->defaultInput->nhg),
            durationInMonths: $r->readPositiveInt(
                'mortgage.duration_in_months',
                $this->defaultInput->durationInMonths,
            ),
            interestPercentage: $r->readPositiveNumber(
                'mortgage.interest_percentage',
                $this->defaultInput->interestPercentage,
            ),
            rateFixationInYears: $r->readPositiveInt(
                'mortgage.rate_fixation_in_years',
                $this->defaultInput->rateFixationInYears,
            ),
            notDeductibleAmount: $r->readNonNegativeCurrencyNumber(
                'mortgage.not_deductible_amount',
                $this->defaultInput->notDeductibleAmount,
            ),
            groundRentAmount: $r->readNonNegativeCurrencyNumber(
                'object.ground_rent.amount_per_year',
                $this->defaultInput->groundRentAmount,
            ),
            energyLabel: $r->readEnumValue(
                EnergyLabel::class,
                'object.energy_label',
                $this->defaultInput->energyLabel
            ),
        );
    }
}
