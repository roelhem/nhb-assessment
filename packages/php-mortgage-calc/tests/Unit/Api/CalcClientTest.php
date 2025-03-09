<?php

namespace Roelhem\NhbTechAssessment\PhpMortgageCalc\Tests\Unit\Api;


use BcMath\Number;
use DateTime;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\Auth\EmptyAuthProvider;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Api\CalcClient;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions\CalcErrorException;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Exceptions\CalcInputException;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome\Person;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByValue;

class CalcClientTest extends TestCase
{

    public function tearDown(): void
    {
        M::close();
    }

    public static function maximumByValueInputRequests(): array
    {
        // These expectations are copied from the swagger documentation website at https://api.hypotheekbond.nl/.
        return [
            [
                "input" => new MaximumByValue\Input(
                    objectValue: new Number(1),
                    durationInMonths: 360,
                    notDeducibleInMonths: 0,
                    onlyUseIncludedLabels: false
                ),
                "expectedUrl" => 'https://api.hypotheekbond.nl/calculation/v1/mortgage/maximum-by-value?objectvalue=1&duration=360&not_deductible=0&onlyUseIncludedLabels=false'
            ],
            [
                "input" => new MaximumByValue\Input(
                    objectValue: new Number(1),
                    durationInMonths: 360,
                    notDeducibleInMonths: 0,
                    onlyUseIncludedLabels: true
                ),
                "expectedUrl" => "https://api.hypotheekbond.nl/calculation/v1/mortgage/maximum-by-value?objectvalue=1&duration=360&not_deductible=0&onlyUseIncludedLabels=true"
            ]
        ];
    }

    #[DataProvider('maximumByValueInputRequests')]
    public function test_generatesValidMaximumByValueRequest(
        MaximumByValue\Input $input,
        string $expectedUrl
    ): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->with('GET', $expectedUrl)
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')->andReturn('{"data":{"result":1}}');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);

        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $result = $calcClient->calcMaximumByValue($input);

        $this->assertEquals(0, $result->compare(1));
    }

    public static function maximumByIncomeRequests(): array
    {
        // These expectations are adjusted copies from the swagger documentation website.
        return [
            "basic" => [
                "input" => new MaximumByIncome\Input(
                    calculationDate: new DateTime('2025-01-01'),
                    mainPerson: new Person(
                        yearlyIncome: new Number('100000'),
                        dateOfBirth: new DateTime('2000-01-01'),
                    ),
                ),
                "expectedUrl" => "https://api.hypotheekbond.nl/calculation/v1/mortgage/maximum-by-income?calculationDate=2025-01-01&nhg=false&duration=360&percentage=1.501&rateFixation=10&notDeductible=0&groundRent=0&energyLabel=E&person%5B0%5D%5Bincome%5D=100000&person%5B0%5D%5BdateOfBirth%5D=2000-01-01&person%5B0%5D%5Balimony%5D=0&person%5B0%5D%5Bloans%5D=0&person%5B0%5D%5BstudentLoans%5D=0&person%5B0%5D%5BstudentLoanMonthlyAmount%5D=0"
            ],
            "with different energy label" => [
                "input" => new MaximumByIncome\Input(
                    calculationDate: new DateTime('2025-01-01'),
                    mainPerson: new Person(
                        yearlyIncome: new Number('100000'),
                        dateOfBirth: new DateTime('2000-01-01'),
                    ),
                    energyLabel: MaximumByIncome\EnergyLabel::Apppp_WithEnergyPerformanceGuarantee
                ),
                "expectedUrl" => "https://api.hypotheekbond.nl/calculation/v1/mortgage/maximum-by-income?calculationDate=2025-01-01&nhg=false&duration=360&percentage=1.501&rateFixation=10&notDeductible=0&groundRent=0&energyLabel=A%2B%2B%2B%2B_WITH_ENERGY_PERFORMANCE_GUARANTEE&person%5B0%5D%5Bincome%5D=100000&person%5B0%5D%5BdateOfBirth%5D=2000-01-01&person%5B0%5D%5Balimony%5D=0&person%5B0%5D%5Bloans%5D=0&person%5B0%5D%5BstudentLoans%5D=0&person%5B0%5D%5BstudentLoanMonthlyAmount%5D=0"
            ],
            "with different percentage" => [
                "input" => new MaximumByIncome\Input(
                    calculationDate: new DateTime('2025-01-01'),
                    mainPerson: new Person(
                        yearlyIncome: new Number('100000'),
                        dateOfBirth: new DateTime('2000-01-01'),
                    ),
                    interestPercentage: new Number('8.253')
                ),
                "expectedUrl" => "https://api.hypotheekbond.nl/calculation/v1/mortgage/maximum-by-income?calculationDate=2025-01-01&nhg=false&duration=360&percentage=8.253&rateFixation=10&notDeductible=0&groundRent=0&energyLabel=E&person%5B0%5D%5Bincome%5D=100000&person%5B0%5D%5BdateOfBirth%5D=2000-01-01&person%5B0%5D%5Balimony%5D=0&person%5B0%5D%5Bloans%5D=0&person%5B0%5D%5BstudentLoans%5D=0&person%5B0%5D%5BstudentLoanMonthlyAmount%5D=0"
            ],
            "with main person private lease amounts" => [
                "input" => new MaximumByIncome\Input(
                    calculationDate: new DateTime('2025-01-01'),
                    mainPerson: new Person(
                        yearlyIncome: new Number('100000'),
                        dateOfBirth: new DateTime('2000-01-01'),
                        privateLeaseMonthlyAmounts: [
                            new Number('253'),
                            new Number('41')
                        ]
                    ),
                ),
                "expectedUrl" => "https://api.hypotheekbond.nl/calculation/v1/mortgage/maximum-by-income?calculationDate=2025-01-01&nhg=false&duration=360&percentage=1.501&rateFixation=10&notDeductible=0&groundRent=0&energyLabel=E&person%5B0%5D%5Bincome%5D=100000&person%5B0%5D%5BdateOfBirth%5D=2000-01-01&person%5B0%5D%5Balimony%5D=0&person%5B0%5D%5Bloans%5D=0&person%5B0%5D%5BstudentLoans%5D=0&person%5B0%5D%5BstudentLoanMonthlyAmount%5D=0&person%5B0%5D%5BprivateLeaseAmounts%5D%5B0%5D=253&person%5B0%5D%5BprivateLeaseAmounts%5D%5B1%5D=41"
            ],
            "with partner person" => [
                "input" => new MaximumByIncome\Input(
                    calculationDate: new DateTime('2025-01-01'),
                    mainPerson: new Person(
                        yearlyIncome: new Number('100000'),
                        dateOfBirth: new DateTime('2000-01-01')
                    ),
                    partnerPerson: new Person(
                        yearlyIncome: new Number('0'),
                        dateOfBirth: new DateTime('2005-01-01')
                    )
                ),
                "expectedUrl" => "https://api.hypotheekbond.nl/calculation/v1/mortgage/maximum-by-income?calculationDate=2025-01-01&nhg=false&duration=360&percentage=1.501&rateFixation=10&notDeductible=0&groundRent=0&energyLabel=E&person%5B0%5D%5Bincome%5D=100000&person%5B0%5D%5BdateOfBirth%5D=2000-01-01&person%5B0%5D%5Balimony%5D=0&person%5B0%5D%5Bloans%5D=0&person%5B0%5D%5BstudentLoans%5D=0&person%5B0%5D%5BstudentLoanMonthlyAmount%5D=0&person%5B1%5D%5Bincome%5D=0&person%5B1%5D%5BdateOfBirth%5D=2005-01-01&person%5B1%5D%5Balimony%5D=0&person%5B1%5D%5Bloans%5D=0&person%5B1%5D%5BstudentLoans%5D=0&person%5B1%5D%5BstudentLoanMonthlyAmount%5D=0"
            ]
        ];
    }

    #[DataProvider('maximumByIncomeRequests')]
    public function test_generatesValidMaximumByIncomeRequest(
        MaximumByIncome\Input $input,
        string $expectedUrl
    ): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->with('GET', $expectedUrl)
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')->andReturn('{"data":{"result":1}}');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);

        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $result = $calcClient->calcMaximumByIncome($input);

        $this->assertEquals(0, $result->compare(1));
    }

    public function test_throwsCalcErrorException_whenServerResponseHadInvalidResult(): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')->andReturn('{"data":{"result":"Helemaal niks! Je bent een arme sloeber!"}}');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);


        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $input = new MaximumByIncome\Input(
            calculationDate: new DateTime('2025-01-01'),
            mainPerson: new Person(
                yearlyIncome: new Number('0'),
                dateOfBirth: new DateTime('2000-01-01'),
            ),
        );

        $this->expectException(CalcErrorException::class);

        $calcClient->calcMaximumByIncome($input);
    }

    public function test_throwsCalcErrorException_whenServerSuccessResponseHasNoRootDataObject(): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')->andReturn('{"result":1.242}');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);


        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $input = new MaximumByIncome\Input(
            calculationDate: new DateTime('2025-01-01'),
            mainPerson: new Person(
                yearlyIncome: new Number('1'),
                dateOfBirth: new DateTime('2000-01-01'),
            ),
        );

        $this->expectException(CalcErrorException::class);

        $calcClient->calcMaximumByIncome($input);
    }

    public function test_throwsCalcErrorException_whenServerRespondsWithInvalidJson(): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')
            ->andReturn('Mega veel... Maar geen bank die zoveel geld ook daadwerkelijk heeft....');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);


        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $input = new MaximumByIncome\Input(
            calculationDate: new DateTime('2025-01-01'),
            mainPerson: new Person(
                yearlyIncome: new Number('100000000000'),
                dateOfBirth: new DateTime('2000-01-01'),
            ),
        );

        $this->expectException(CalcErrorException::class);

        $calcClient->calcMaximumByIncome($input);
    }

    public function test_throwsCalcErrorException_whenServerHasErrorResponse(): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')
            ->andReturn('{"error":{"message":"Ik weet het ook allemaal niet meer...", "code":54411}}');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(500);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);


        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $input = new MaximumByIncome\Input(
            calculationDate: new DateTime('2025-01-01'),
            mainPerson: new Person(
                yearlyIncome: new Number('10000'),
                dateOfBirth: new DateTime('2000-01-01'),
            ),
        );

        $this->expectException(CalcErrorException::class);

        $calcClient->calcMaximumByIncome($input);
    }

    public function test_throwsCalcErrorException_whenServerHasForbiddenResponse(): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')
            ->andReturn('{"error":{"message":"Mag niet!", "code":3222}}');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(403);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);


        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $input = new MaximumByIncome\Input(
            calculationDate: new DateTime('2025-01-01'),
            mainPerson: new Person(
                yearlyIncome: new Number('1'),
                dateOfBirth: new DateTime('2000-01-01'),
            ),
        );

        $this->expectException(CalcErrorException::class);

        $calcClient->calcMaximumByIncome($input);
    }

    public function test_throwsCalcInputException_whenServerRespondsWithInvalidInputStatusCode(): void
    {
        $request = M::instanceMock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();

        $requestFactory = M::mock(RequestFactoryInterface::class);
        $requestFactory
            ->shouldReceive('createRequest')
            ->andReturn($request);

        $responseBody = M::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')->andReturn('{"error":{"message":"Zo rijk is niemand!"}}');

        $response = M::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(400);
        $response->shouldReceive('getBody')->andReturn($responseBody);

        $httpClient = M::mock(ClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);


        $calcClient = new CalcClient(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            authProvider: new EmptyAuthProvider,
            calculationApiBaseUrl: 'https://api.hypotheekbond.nl/calculation'
        );

        $input = new MaximumByIncome\Input(
            calculationDate: new DateTime('2025-01-01'),
            mainPerson: new Person(
                yearlyIncome: new Number('99999999999999999999'),
                dateOfBirth: new DateTime('2000-01-01'),
            ),
        );

        $this->expectException(CalcInputException::class);

        $calcClient->calcMaximumByIncome($input);
    }
}
