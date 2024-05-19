<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailManager;
use App\Mail\SendinblueTransport;
use GuzzleHttp\Client;

class SendinblueServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->extend('mail.manager', function ($manager) {
            $manager->extend('sendinblue', function () {
                $client = new Client();
                $apiKey = config('services.sendinblue.api_key');

                return new SendinblueTransport($client, $apiKey);
            });

            return $manager;
        });
    }
}
