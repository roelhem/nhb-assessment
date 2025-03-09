<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;

use BcMath\Number;
use DateTimeInterface;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Traits\HasImmutableProperties;
use function Roelhem\NhbTechAssessment\PhpMortgageCalc\currencyNumberEquals;
use function Roelhem\NhbTechAssessment\PhpMortgageCalc\dateEquals;

readonly final class Person
{
    use HasImmutableProperties;

    /**
     * @param DateTimeInterface $dateOfBirth The date of birth of this person.
     * @param Number $yearlyIncome The yearly income of this person in Euros.
     * @param Number $alimonyPerYear The total amount of alimony (allimentatie) in Euros that this person needs to pay
     *                               in a year.
     * @param Number $totalLoansAmount The total amount of loans (leningen) of this person in Euros.
     * @param Number $studentLoanAmount The currently open amount of student loan (studie schuld) of this person in
     *                                  Euros.
     * @param Number $studentLoanMonthlyAmount The monthly student loan (studie schuld) repayment of this person in
     *                                         Euros.
     * @param DateTimeInterface|null $studentLoanStartDate The start date of the student loan.
     * @param array<int, Number> $privateLeaseMonthlyAmounts Collection of all private lease amounts per month in Euros.
     */
    public function __construct(
        public DateTimeInterface $dateOfBirth,
        public Number $yearlyIncome = new Number(0),
        public Number $alimonyPerYear = new Number(0),
        public Number $totalLoansAmount = new Number(0),
        public Number $studentLoanAmount = new Number(0),
        public Number $studentLoanMonthlyAmount = new Number(0),
        public DateTimeInterface|null $studentLoanStartDate = null,
        public array $privateLeaseMonthlyAmounts = []
    )
    {
        assert($this->yearlyIncome->compare(0) >= 0, 'yearlyIncome is non-negative.');
        assert($this->yearlyIncome->scale <= 2, 'yearlyIncome has a maximum precision of 2.');
        assert($this->alimonyPerYear->compare(0) >= 0, 'alimonyPerYear is non-negative.');
        assert($this->alimonyPerYear->scale <= 2, 'alimonyPerYear has a maximum precision of 2.');
        assert($this->totalLoansAmount->compare(0) >= 0, 'totalLoansAmount is non-negative.');
        assert($this->totalLoansAmount->scale <= 2, 'totalLoansAmount has a maximum precision of 2.');
        assert($this->studentLoanAmount->compare(0) >= 0, 'studentLoanAmount is non-negative.');
        assert($this->studentLoanAmount->scale <= 2, 'studentLoanAmount has a maximum precision of 2.');
        assert($this->studentLoanMonthlyAmount->compare(0) >= 0, 'studentLoanMonthlyAmount is non-negative.');
        assert($this->studentLoanMonthlyAmount->scale <= 2, 'studentLoanMonthlyAmount has a maximum precision of 2.');
        assert(array_is_list($this->privateLeaseMonthlyAmounts), 'privateLeaseMonthlyAmounts is a list.');
        assert(
            array_all($this->privateLeaseMonthlyAmounts, fn($privateLeaseAmount) => $privateLeaseAmount instanceof Number),
            'Entries of privateLeaseMonthlyAmounts are numbers.'
        );
        assert(
            array_all($this->privateLeaseMonthlyAmounts, fn($privateLeaseAmount) => $privateLeaseAmount->compare(0) >= 0),
            'Entries of privateLeaseMonthlyAmounts are non-negative.'
        );
        assert(
            array_all($this->privateLeaseMonthlyAmounts, fn($privateLeaseAmount) => $privateLeaseAmount->scale <= 2),
            'Entries of privateLeaseMonthlyAmounts have a maximum precision of 2.'
        );
    }

    /**
     * Computes the yearly income of from the monthly income and returns a new instance with the `yearlyIncome` set
     * to this new amount.
     *
     * NOTE: This is just an example on how I would usually define convenience functions on these kind of
     *       immutable classes.
     *       I know this is not the right place to put this logic if we would follow the OOP-rules very strictly.
     *       That said, I still think this is often the right place to put calculations like this because of the
     *       following:
     *         1. It results in a public API that is WAY easier to read.
     *         2. It is a lot more discoverable for others when you define this kind of logic as a method on a
     *            class that they will always encounter.
     *         3. It is very clear what this method computes (i.e. all values and branches are defined in this method.
     *            There is no need to jump between multiple files if you want to read all executed steps.)
     *         4. I assume that it is very unlikely that logic like this ever changes, so there is no need
     *            to use complicated injections of parameters. This is just a convenience method, so if there will ever
     *            be a case where this definition is not sufficient, you can still use the `with(yearlyIncome: ...)`
     *            method directly.
     *
     * @param Number|string|int $monthlyIncome The new monthly income of this person in euros.
     * @param bool|Number|string $holidayAllowance Whether this person receives 'vakantiegeld'. Can also be set to a
     *                                             multiplier for the yearly income (defaults to `"1.08"` when set to
     *                                             `true`).
     * @param bool $thirteenthMonth Whether this person receives a thirteenth month of payment.
     * @return static
     */
    public function withMonthlyIncome(
        Number|string|int $monthlyIncome,
        bool|Number|string $holidayAllowance = true,
        bool $thirteenthMonth = false
    ): static
    {
        if(!($monthlyIncome instanceof Number)) {
            $monthlyIncome = new Number($monthlyIncome);
        }

        assert($monthlyIncome->compare(0) >= 0, 'monthlyIncome is non-negative.');
        assert($monthlyIncome->scale <= 2, 'monthlyIncome has a maximum precision of 2.');


        if($holidayAllowance === true) {
            $holidayAllowance = '1.08';
        } else if ($holidayAllowance === false) {
            $holidayAllowance = '1';
        }

        if(!($holidayAllowance instanceof Number)) {
            $holidayAllowance = new Number($holidayAllowance);
        }

        assert($holidayAllowance->compare(1) >= 0, 'holidayAllowance is >= 1.');
        assert($holidayAllowance->compare(1) < 2, 'holidayAllowance less than 2.');


        $monthCount = $thirteenthMonth ? 13 : 12;
        $yearlyIncome = $monthlyIncome
            ->mul($monthCount, scale: 2)
            ->mul($holidayAllowance, scale: 2);

        return $this->with(yearlyIncome: $yearlyIncome);
    }

    /**
     * Checks whether this person is equivalent to the $other person.
     *
     * @param mixed $other
     * @return bool
     */
    public function equals(mixed $other): bool
    {
        // Ensure the other type also is a person.
        if(!($other instanceof self)) return false;


        // Compare private lease amounts.
        $thisPrivateLeaseAmounts = $this->privateLeaseMonthlyAmounts;
        $otherPrivateLeaseAmounts = $other->privateLeaseMonthlyAmounts;
        if(count($thisPrivateLeaseAmounts) !== count($otherPrivateLeaseAmounts)) {
            return false;
        }

        usort($thisPrivateLeaseAmounts, fn(Number $a, Number $b) => $a->compare($b));
        usort($otherPrivateLeaseAmounts, fn(Number $a, Number $b) => $a->compare($b));
        for ($i=0; $i<count($thisPrivateLeaseAmounts); $i++) {
            if(!currencyNumberEquals($thisPrivateLeaseAmounts[$i], $otherPrivateLeaseAmounts[$i])) {
                return false;
            }
        }


        // Compare other values.
        return currencyNumberEquals($this->yearlyIncome, $other->yearlyIncome)
            && dateEquals($this->dateOfBirth, $other->dateOfBirth)
            && currencyNumberEquals($this->alimonyPerYear, $other->alimonyPerYear)
            && currencyNumberEquals($this->totalLoansAmount, $other->totalLoansAmount)
            && currencyNumberEquals($this->studentLoanAmount, $other->studentLoanAmount)
            && currencyNumberEquals($this->studentLoanMonthlyAmount, $other->studentLoanMonthlyAmount)
            && dateEquals($other->studentLoanStartDate, $other->studentLoanStartDate);
    }
}
