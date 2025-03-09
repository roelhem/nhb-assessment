<?php

namespace App\Providers;

use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Console\Input\InputOption;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if($this->app->runningInConsole()) {
            $this->bootExtraOptions();
        }

    }

    /**
     * Register extra options to the cli. Doing this here allows us to change the app configuration before any
     * configurable services are resolved from the service container.
     *
     * @return void
     */
    private function bootExtraOptions(): void
    {
        Event::listen(ArtisanStarting::class, function (ArtisanStarting $event) {
            $event->artisan
                ->getDefinition()
                ->addOption(new InputOption(
                    'api-key',
                    'k',
                    InputOption::VALUE_REQUIRED,
                    'The api key for the calculation api. Defaults to $MORTGAGE_CALC_API_KEY'
                ));

            $event->artisan
                ->getDefinition()
                ->addOption(new InputOption(
                    'api-url',
                    'u',
                    InputOption::VALUE_REQUIRED,
                    'The base url to the calculation api. Defaults to $MORTGAGE_CALC_API_URL'
                ));
        });

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $apiKey = $event->input->getOption('api-key');
            if($apiKey !== null) {
                config(['mortgage-calc.api.api_key' => $apiKey]);
            }

            $url = $event->input->getOption('api-url');
            if($url !== null) {
                config(['mortgage-calc.api.url' => $url]);
            }
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClientInterface::class, \GuzzleHttp\Client::class);
        $this->app->bind(RequestFactoryInterface::class, \GuzzleHttp\Psr7\HttpFactory::class);
    }
}
