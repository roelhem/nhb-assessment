<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;

use BcMath\Number;
use DateTimeInterface;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Traits\HasImmutableProperties;
use function Roelhem\NhbTechAssessment\PhpMortgageCalc\currencyNumberEquals;
use function Roelhem\NhbTechAssessment\PhpMortgageCalc\dateEquals;

readonly final class Input
{
    use HasImmutableProperties;

    /**
     * @param DateTimeInterface $calculationDate The date for which the calculation should be done.
     * @param Person $mainPerson Information about the main person.
     * @param Person|null $partnerPerson Information about the partner of the main person.
     * @param bool $nhg Should there be a 'Nationale Hypotheek Garantie'?
     * @param int $durationInMonths Duration of the morgage in months.
     * @param Number $interestPercentage Interest percentage (in 100 part).
     * @param int $rateFixationInYears Rate fixation (Rentevaste periode) in years.
     * @param Number $notDeductibleAmount Not deductible amount (niet aftrekbaar bedrag) in Euros.
     * @param Number $groundRentAmount The amount of ground rent (erfpacht) per year in Euros.
     * @param EnergyLabel $energyLabel The energy label.
     */
    public function __construct(
        public DateTimeInterface           $calculationDate,
        public Person                      $mainPerson,
        public Person|null                 $partnerPerson = null,
        public bool                        $nhg = false,
        public int                         $durationInMonths = 360,
        public Number                      $interestPercentage = new Number('1.501'),
        public int                         $rateFixationInYears = 10,
        public Number                      $notDeductibleAmount = new Number(0),
        public Number                      $groundRentAmount = new Number(0),
        public MaximumByIncome\EnergyLabel $energyLabel = MaximumByIncome\EnergyLabel::DEFAULT
    )
    {
        assert($durationInMonths > 0, 'durationInMonths is positive.');
        assert($rateFixationInYears > 0, 'rateFixationInYears is positive.');
        assert($this->interestPercentage->compare(0) > 0, 'percentage is positive.');
        assert($this->notDeductibleAmount->compare(0) >= 0, 'notDeductibleAmount is non-negative.');
        assert($this->notDeductibleAmount->scale <= 2, 'notDeductibleAmount has a maximum precision of 2.');
        assert($this->groundRentAmount->compare(0) >= 0, 'groundRentAmount is non-negative.');
        assert($this->groundRentAmount->scale <= 2, 'groundRentAmount has a maximum precision of 2.');
    }

    /**
     * Convenience method to create a new instance of this request with other properties for the main person.
     *
     * @param mixed ...$values The properties to change for the main person.
     * @return static
     */
    public function withMainPerson(...$values): static
    {
        return $this->with(mainPerson: $this->mainPerson->with(...$values));
    }

    /**
     * Convenience method to create a new instance of this request with other properties for the partner person.
     * Initializes a new partner person if this instance does not have a partner person yet.
     *
     * @param mixed ...$values The properties to change/initialize the partner person.
     * @return static
     */
    public function withPartnerPerson(...$values): static
    {
        return $this->with(partnerPerson: $this->partnerPerson?->with(...$values) ?? new Person(...$values));
    }

    /**
     * Convenience method to create a new instance of this input without a partner person.
     *
     * @return static
     */
    public function withoutPartnerPerson(): static
    {
        return $this->with(partnerPerson: null);
    }

    /**
     * Whether this input instance has a partner person.
     *
     * @return bool
     */
    public function hasPartnerPerson(): bool
    {
        return $this->partnerPerson !== null;
    }

    public function equals(mixed $other): bool
    {
        if(!($other instanceof self)) {
            return false;
        }

        return dateEquals($this->calculationDate, $other->calculationDate)
            && $this->mainPerson->equals($other->mainPerson)
            && (($this->partnerPerson === null && $other->partnerPerson === null)
                || $this->partnerPerson->equals($other->partnerPerson))
            && $this->nhg === $other->nhg
            && $this->durationInMonths === $other->durationInMonths
            && currencyNumberEquals($this->interestPercentage, $other->interestPercentage)
            && $this->rateFixationInYears === $other->rateFixationInYears
            && currencyNumberEquals($this->notDeductibleAmount, $other->notDeductibleAmount)
            && currencyNumberEquals($this->groundRentAmount, $other->groundRentAmount)
            && $this->energyLabel === $other->energyLabel;
    }
}
