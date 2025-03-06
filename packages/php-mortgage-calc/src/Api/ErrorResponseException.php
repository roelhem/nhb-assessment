<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api;

use Throwable;

interface ErrorResponseException extends Throwable
{
    public function getErrorMessage(): string;

    public function getErrorCode(): int;
}
