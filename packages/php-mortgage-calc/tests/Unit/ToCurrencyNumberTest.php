<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Tests\Unit;

use BcMath\Number;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use function Roelhem\NhbTechAssessment\PhpMortgageCalc\toCurrencyNumber;

class ToCurrencyNumberTest extends TestCase
{

    public static function validInputs(): array
    {
        return [
            'zero_int' => [
                'input' => 0,
                'expected' => '0',
            ],
            'zero_float' => [
                'input' => 0.0,
                'expected' => '0',
            ],
            'zero_string' => [
                'input' => '0',
                'expected' => '0',
            ],
            'positive_string' => [
                'input' => '+1',
                'expected' => '1',
            ],
            'negative_string' => [
                'input' => '-1',
                'expected' => '-1',
            ],
            'empty_string' => [
                'input' => '',
                'expected' => '0',
            ],
            'dot_only_string' => [
                'input' => '.',
                'expected' => '0',
            ],
            'only_fraction_decimals_string' => [
                'input' => '.12',
                'expected' => '0.12',
            ],
            'zero_string_fractional' => [
                'input' => '0.00000',
                'expected' => '0',
            ],
            'high_prec_float' => [
                'input' => 2750673.7726766206,
                'expected' => '2750673.77',
            ],
            'high_prec_string' => [
                'input' => '2750673.7726766206',
                'expected' => '2750673.77',
            ],
            'high_prec_float_round_up' => [
                'input' => 2750673.7788766206,
                'expected' => '2750673.78',
            ],
            'high_prec_string_round_up' => [
                'input' => '2750673.7788766206',
                'expected' => '2750673.78',
            ],
        ];
    }

    #[DataProvider('validInputs')]
    public function test_validInput(mixed $input, Number|int|string $expected): void
    {
        $result = toCurrencyNumber($input);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertEquals(0, $result->compare($expected));
    }

    public static function invalidInputs(): array
    {
        $values = [
            'null' => null,
            'true' => true,
            'false' => false,
            'letter' => 'a',
            'comma seperated' => '0,0',
            'double negative' => '--1',
            'array' => [],
            'object' => new stdClass(),
        ];

        return array_map(fn($val) => [$val], $values);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalidInput_throwsInvalidArgumentException(mixed $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        toCurrencyNumber($input);
    }
}
