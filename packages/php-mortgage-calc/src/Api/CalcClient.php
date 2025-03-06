<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Api;

use BcMath\Number;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByValue;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;
use stdClass;
use function Roelhem\NhbTechAssessment\PhpMortgageCalc\toCurrencyNumber;

readonly class CalcClient implements MaximumByIncome\CalcProvider, MaximumByValue\CalcProvider
{
    public string $calculationApiBaseUrl;

    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private ?AuthProvider $authProvider = null,
        string $calculationApiBaseUrl = 'https://api.hypotheekbond.nl/calculation',
    )
    {
        $this->calculationApiBaseUrl = rtrim($calculationApiBaseUrl, '/');
        assert(
            filter_var($this->calculationApiBaseUrl, FILTER_VALIDATE_URL) !== false,
            "Calculation base url is a valid URL."
        );
    }

    // ------------------------------------------------------------------------------------------------------------ //
    //  Maximum by Income                                                                                           //
    // ------------------------------------------------------------------------------------------------------------ //

    /**
     * Helper method to create the parameters for a Person.
     *
     * @param MaximumByIncome\Person $person
     * @return array
     */
    private function personToQueryParams(MaximumByIncome\Person $person): array
    {
        $params = [
            "income" => strval($person->yearlyIncome),
            "dateOfBirth" => $person->dateOfBirth->format('Y-m-d'),
            "alimony" => strval($person->alimonyPerYear),
            "loans" => strval($person->totalLoansAmount),
            "studentLoans" => strval($person->studentLoanAmount),
            "studentLoanMonthlyAmount" => strval($person->studentLoanMonthlyAmount),
            "privateLeaseAmounts" => array_map(strval(...), $person->privateLeaseAmounts),
        ];

        if($person->studentLoanStartDate !== null) {
            $params['loanStartDate'] = $person->studentLoanStartDate->format('Y-m-d');
        }

        return $params;
    }

    /**
     * Computes the MaximumByIncome using a request to the hypotheekbond calculations API.
     *
     * @param MaximumByIncome\Input $input
     * @return Number The maximum morgage in euros.
     * @throws ClientExceptionInterface
     * @throws UnexpectedResponseException
     */
    public function calcMaximumByIncome(MaximumByIncome\Input $input): Number
    {
        // Build request uri
        $personParams = [
            $this->personToQueryParams($input->mainPerson)
        ];
        if($input->partnerPerson !== null) {
            $personParams[1] = $this->personToQueryParams($input->partnerPerson);
        }

        $params = [
            "calculationDate" => $input->calculationDate->format('Y-m-d'),
            "nhg" => $input->nhg,
            "duration" => $input->durationInMonths,
            "percentage" => strval($input->percentage),
            "rateFixation" => $input->rateFixationInYears,
            "notDeductible" => strval($input->notDeductibleAmount),
            "groundRent" => strval($input->groundRentAmount),
            "energyLabel" => $input->energyLabel->value,
            "person" => $personParams,
        ];
        $queryParams = http_build_query($params, '', '&');

        $uri = "{$this->calculationApiBaseUrl}/v1/mortgage/maximum-by-income?$queryParams";

        $httpRequest = $this->requestFactory->createRequest('GET', $uri);

        // Send request
        $responseData = $this->handleHttpRequest($httpRequest);

        // Parse response.
        $result = $responseData['result'] ?? throw new UnexpectedResponseException(
            request: $httpRequest,
            responseData: $responseData,
            message: '`result` is missing from response.',
        );

        try {
            return toCurrencyNumber($result);
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedResponseException(
                request: $httpRequest,
                responseData: $responseData,
                message: '`result` is not a valid currency number.',
                previous: $e
            );
        }
    }


    // ------------------------------------------------------------------------------------------------------------ //
    //  Maximum by Income                                                                                           //
    // ------------------------------------------------------------------------------------------------------------ //


    /**
     * Computes MaximumByBalue using a request to the hypotheekbond calculations API.
     *
     * @param MaximumByValue\Input $input
     * @return Number The maximum morgage in euros.
     * @throws ClientExceptionInterface
     * @throws UnexpectedResponseException
     */
    public function calcMaximumByValue(MaximumByValue\Input $input): Number
    {
        // Build request
        $params = [
            "objectvalue" => strval($input->objectValue),
            "duration" => $input->durationInMonths,
            "not_deductible" => $input->notDeducibleInMonths,
            "onlyUseIncludedLabels" => $input->onlyUseIncludedLabels,
        ];

        $queryParams = http_build_query($params, '', '&');

        $uri = "{$this->calculationApiBaseUrl}/v1/mortgage/maximum-by-value?$queryParams";

        $httpRequest = $this->requestFactory->createRequest('GET', $uri);

        // Send request
        $responseData = $this->handleHttpRequest($httpRequest);

        // Parse response.
        $result = $responseData->result ?? throw new UnexpectedResponseException(
            request: $httpRequest,
            responseData: $responseData,
            message: '`result` is missing from response.',
        );

        try {
            return toCurrencyNumber($result);
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedResponseException(
                request: $httpRequest,
                responseData: $responseData,
                message: '`result` is not a valid currency number.',
                previous: $e
            );
        }
    }

    // ------------------------------------------------------------------------------------------------------------ //
    //  Handling Requests                                                                                           //
    // ------------------------------------------------------------------------------------------------------------ //

    /**
     * @throws ClientExceptionInterface
     * @throws UnexpectedResponseException
     * @throws ErrorResponseException
     */
    private function handleHttpRequest(RequestInterface $request): stdClass
    {
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $this->authProvider?->authenticateRequest($request) ?? $request;

        // NOTE: We cannot do anything with client exceptions (invalid request, network error, etc.) and we have no
        //       useful debugging context to add to these exceptions in this method itself. These exceptions
        //       cannot be handled gracefully either, as they are probably caused by some invalid configuration.
        //       Therefore, I think it is better to let them be thrown normally so that they can be logged at some
        //       higher level. This keeps the stack traces a lot more readable to.
        $response = $this->httpClient->sendRequest($request);

        $statusCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();

        try {
            $contents = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnexpectedResponseException(
                request: $request,
                response: $response,
                responseData: $contents,
                message: "Response body is not a valid json.",
                previous: $e
            );
        }

        switch ($statusCode) {
            case 200:
                if(!isset($contents->data) || !($contents->data instanceof stdClass)) {
                    throw new UnexpectedResponseException(
                        request: $request,
                        response: $response,
                        responseData: $contents,
                        message: "Success response data does not have a root level 'data' key that is an object.",
                    );
                }

                return $contents->data;
            case 400:
                if(!isset($contents->error) || !($contents->error instanceof stdClass)) {
                    throw new UnexpectedResponseException(
                        request: $request,
                        response: $response,
                        responseData: $contents,
                        message: "Error response does not have a root level 'error' key that is an object.",
                    );
                }

                $errorData = $contents->error;

                throw new InvalidInputErrorResponseException(
                    request: $request,
                    errorMessage: $errorData->message ?? null,
                    errorCode: $errorData->code ?? null,
                );
            case 500:
                // NOTE: I purposely repeated this part of the code because I do not know you API conventions. If you
                //       have the API convention that all error responses have this exact format, I would have moved
                //       this to a separate private method.
                if(!isset($contents->error) || !($contents->error instanceof stdClass)) {
                    throw new UnexpectedResponseException(
                        request: $request,
                        response: $response,
                        responseData: $contents,
                        message: "Error response does not have a root level 'error' key that is an object.",
                    );
                }

                $errorData = $contents->error;

                throw new UnexpectedErrorResponseException(
                    request: $request,
                    errorMessage: $errorData->message ?? null,
                    errorCode: $errorData->code ?? null,
                );
            default:
                throw new UnexpectedResponseException(
                    request: $request,
                    response: $response,
                    responseData: $contents,
                    message: "Unexpected response status code `{$statusCode}`.",
                );
        }
    }
}
