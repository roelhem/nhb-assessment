<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\Auth;

use Psr\Http\Message\RequestInterface;

readonly class EmptyAuthProvider implements AuthProvider
{
    public function authenticateRequest(RequestInterface $request): RequestInterface
    {
        return $request;
    }
}
