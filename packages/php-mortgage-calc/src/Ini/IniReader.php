<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini;

use BackedEnum;
use BcMath\Number;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;
use function Roelhem\NhbTechAssessment\PhpMortgageCalc\toCurrencyNumber;

readonly class IniReader
{
    private function __construct(
        private array $data,
        public ?string $section,
        public IniOptions $options,
    )
    {
    }

    public static function fromArray(array $data, ?IniOptions $options = null): IniReader
    {
        return new self($data, null, $options ?? new IniOptions);
    }

    public static function fromString(string $data, ?IniOptions $options = null): IniReader
    {
        return static::fromArray(
            parse_ini_string($data, true, INI_SCANNER_RAW),
            $options
        );
    }

    public function section(string $section): IniReader
    {
        assert($this->section === null, 'Only the ini root has sections');
        return new IniReader($this->data[$section] ?? [], $section, $this->options);
    }

    protected function newValueException(
        string $key,
        string $expectationMessage,
        ?Throwable $previous = null
    ): IniReaderValueException
    {
        return new IniReaderValueException(
            reader: $this,
            key: $key,
            value: $this->data[$key] ?? null,
            expectedMessage: $expectationMessage,
            previous: $previous
        );
    }

    protected function newValueExceptionWithAllowedValues(string $key, array $values): IniReaderValueException
    {
        $valueStrs = array_map(
            function($value) {
                if($value instanceof BackedEnum) {
                    return $value->value;
                } else {
                    return $value;
                }
            },
            $values
        );

        return $this->newValueException($key, implode('|', $valueStrs));
    }

    /**
     * @throws IniReaderValueException
     */
    public function readString(string $key, string $default = ''): string
    {
        $value = $this->data[$key] ?? $default;
        if(!is_string($value)) {
            throw $this->newValueException($key, 'a value');
        }
        return trim($value);
    }

    /**
     * @throws IniReaderValueException
     */
    public function readRequiredString(string $key): string
    {
        $value = $this->readString($key);
        if($value === '') {
            throw $this->newValueException($key, 'a non-empty value');
        }
        return $value;
    }

    /**
     * @throws IniReaderValueException
     */
    public function readBool(string $key, bool $default = false): bool
    {
        $value = strtolower($this->readString($key));
        if($value === '') {
            return $default;
        }

        if(in_array($value, $this->options->trueValues)) {
            return true;
        } else if(in_array($value, $this->options->falseValues)) {
            return false;
        }

        throw $this->newValueExceptionWithAllowedValues($key, [
            ...$this->options->trueValues,
            ...$this->options->falseValues,
        ]);
    }

    /**
     * @throws IniReaderValueException
     */
    public function readNonNegativeInt(string $key, int $default = 0): int
    {
        $value = $this->readString($key);
        if($value === '') {
            return $default;
        }

        if(!ctype_digit($value)) {
            throw $this->newValueException($key, 'a non-negative integer');
        }

        return intval($value);
    }

    /**
     * @throws IniReaderValueException
     */
    public function readPositiveInt(string $key, int $default = 1): int
    {
        $value = $this->readNonNegativeInt($key, $default);
        if($value <= 0) {
            throw $this->newValueException($key, 'a positive integer');
        }
        return $value;
    }

    public function hasValueFor(string $key): bool
    {
        if(!array_key_exists($key, $this->data)) return false;
        $value = $this->data[$key];
        if(!is_string($value)) return false;
        return trim($value) !== '';
    }

    /**
     * @throws IniReaderValueException
     */
    public function readNonNegativeNumber(string $key, mixed $default = 0): Number
    {
        $value = $this->readString($key);
        if($value === '') {
            if($default instanceof Number) {
                return $default;
            } else {
                return new Number($default);
            }
        }

        if(preg_match('/^[0-9]*([.,][0-9]*)?$/', $value) === 0) {
            throw $this->newValueException($key, 'a non-negative number');
        }

        $value = str_replace(',', '.', $value);
        return new Number($value);
    }

    /**
     * @throws IniReaderValueException
     */
    public function readPositiveNumber(string $key, mixed $default = 1): Number
    {
        $value = $this->readNonNegativeNumber($key, $default);
        if($value->compare(0) <= 0) {
            throw $this->newValueException($key, 'a positive number');
        }
        return $value;
    }

    /**
     * @throws IniReaderValueException
     */
    public function readNonNegativeCurrencyNumber(string $key, mixed $default = null): Number
    {
        $value = $this->readString($key);
        if($value === '') {
            return toCurrencyNumber($default ?? 0);
        }

        if(preg_match('/^[0-9]*([.,][0-9]{0,2})?$/', $value) === 0) {
            throw $this->newValueException($key, 'a non-negative number with at most two decimal places');
        }
        $value = str_replace(',', '.', $value);

        return new Number($value);
    }

    /**
     * @throws IniReaderValueException
     */
    public function readNonNegativeCurrencyNumbers(string $key, string $separator = ' '): array
    {
        $values = $this->readString($key);
        $result = [];

        foreach (explode($separator, $values) as $value) {
            $value = trim($value);
            if($value !== '') {
                if(preg_match('/^[0-9]*([.,][0-9]{0,2})?$/', $value) === 0) {
                    throw $this->newValueException($key, 'a collection of non-negative numbers with at most two decimal places');
                }
                $value = str_replace(',', '.', $value);
                $result[] = new Number($value);
            }
        }

        return $result;
    }

    /**
     * @throws IniReaderValueException
     */
    public function readDate(string $key, ?DateTimeInterface $default = null): ?DateTimeInterface
    {
        $value = $this->readString($key);
        if($value === '') {
            return $default;
        }
        $parsedValue = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if($parsedValue === false) {
            throw $this->newValueException($key, 'a date in (yyyy-mm-dd) format');
        }
        return $parsedValue;
    }

    /**
     * @throws IniReaderValueException
     */
    public function readRequiredDate(string $key): DateTimeInterface
    {
        $value = $this->readRequiredString($key);
        $parsedValue = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if($parsedValue === false) {
            throw $this->newValueException($key, 'a date in (yyyy-mm-dd) format');
        }
        return $parsedValue;
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     * @param string $key
     * @param T $default
     * @return T
     * @throws IniReaderValueException
     */
    public function readEnumValue(string $enumClass, string $key, mixed $default): mixed
    {
        assert(enum_exists($enumClass), 'EnumClass is an enum class string.');
        assert(is_a($default, $enumClass), 'Default is an instance of the enum class.');

        $value = $this->readString($key);
        if($value === '') {
            return $default;
        }

        $enumValue = $enumClass::tryFrom($value);
        if($enumValue === null) {
            throw $this->newValueExceptionWithAllowedValues($key, $enumClass::cases());
        }

        return $enumValue;
    }
}
