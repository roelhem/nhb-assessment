<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\Auth;

use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

readonly class ApiKeyAuthProvider implements AuthProvider
{

    public function __construct(#[SensitiveParameter] private string $apiKey)
    {
    }

    public function authenticateRequest(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('x-api-key', $this->apiKey);
    }
}
