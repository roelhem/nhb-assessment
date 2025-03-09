<?php

namespace App\Providers;

use BcMath\Number;
use DateTime;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\Api;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByIncome;
use Roelhem\NhbTechAssessment\PhpMortgageCalc\MaximumByValue;

class MortgageCalcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerInputSerializers();
        $this->registerApiServices();
        $this->registerDefaultCalcProviders();
    }

    private function registerInputSerializers(): void
    {
        $this->app->bind(MaximumByIncome\IniInputSerializer::class, function (Application $app, array $config) {

            return new MaximumByIncome\IniInputSerializer(
                defaultInput: $config['defaultInput'] ?? new MaximumByIncome\Input(
                calculationDate: Carbon::now(),
                mainPerson: new MaximumByIncome\Person(
                    dateOfBirth: DateTime::createFromFormat('Y-m-d', '2000-01-01'),
                    yearlyIncome: new Number(0),
                )
            )
            );
        });

        $this->app->bindIf(
            MaximumByIncome\InputSerializer::class,
            MaximumByIncome\IniInputSerializer::class
        );
    }

    private function registerApiServices(): void
    {
        $this->app->bindIf(Api\Auth\AuthProvider::class, Api\Auth\ApiKeyAuthProvider::class);

        $this->app->when(Api\Auth\ApiKeyAuthProvider::class)
            ->needs('$apiKey')
            ->giveConfig('mortgage-calc.api.api_key');

        $this->app->when(Api\CalcClient::class)
            ->needs('$calculationApiBaseUrl')
            ->giveConfig('mortgage-calc.api.url', 'https://api.hypotheekbond.nl/calculation');
    }

    private function registerDefaultCalcProviders(): void
    {
        $this->app->bindIf(MaximumByIncome\CalcProvider::class, Api\CalcClient::class);
        $this->app->bindIf(MaximumByValue\CalcProvider::class, Api\CalcClient::class);
    }
}
