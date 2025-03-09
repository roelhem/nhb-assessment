<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Ini;

use Exception;
use Throwable;

class IniReaderValueException extends Exception
{

    public readonly ?string $section;

    public function __construct(
        public readonly IniReader $reader,
        public readonly string $key,
        public readonly mixed $value,
        public readonly string $expectedMessage,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->section = $reader->section;
        if($message === null) {
            $message = "Invalid value ".var_export($value, true)." for `$key`";
            if($this->section !== null) {
                $message .= " in section [$this->section]";
            }
            $message .= ". Expected $expectedMessage.";
        }

        parent::__construct($message, $code, $previous);
    }
}
