<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api;

use Psr\Http\Message\RequestInterface;

interface AuthProvider
{
    public function authenticateRequest(RequestInterface $request): RequestInterface;
}
