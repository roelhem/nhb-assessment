<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini;

use Roelhem\NhbTechAssessment\PhpMortgageCalc\Traits\HasImmutableProperties;

readonly class IniOptions
{
    use HasImmutableProperties;

    public string $true;
    public string $false;

    /**
     * @param string[] $trueValues The string values that will be converted to `true`.
     * @param string|null $true The default string value for boolean `true`.
     * @param string[] $falseValues The string values that will be converted to `false`.
     * @param string|null $false The default string value for boolean `false`.
     */
    public function __construct(
        public array $trueValues = ['yes', 'y', 'true', 't', 'ja', 'j'],
        ?string $true = null,
        public array $falseValues = ['no', 'n', 'false', 'f', 'nee'],
        ?string $false = null,
    )
    {

        assert(array_is_list($this->trueValues), 'True values is a list.');
        assert(array_all($this->trueValues, fn($x) => ctype_lower($x)), 'True values are lowercase.');
        assert(count($this->trueValues) > 0, 'At least one true value.');
        $this->true = $true ?? $this->trueValues[0];
        assert(in_array($this->true, $this->trueValues, true), 'True is a valid true value.');

        assert(array_is_list($this->falseValues), 'False values is a list.');
        assert(array_all($this->falseValues, fn($x) => ctype_lower($x)), 'False values are lowercase.');
        assert(count($this->falseValues) > 0, 'At least one false value.');
        $this->false = $false ?? $this->falseValues[0];
        assert(in_array($this->false, $this->falseValues, true), 'False is a valid false value.');

        assert(
            count(array_intersect($this->trueValues, $this->falseValues)) === 0,
            'No overlap between true and false values.'
        );
        assert(in_array($this->false, $this->falseValues, true), 'False is a valid false value.');
    }
}

