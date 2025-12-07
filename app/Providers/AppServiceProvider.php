<?php

namespace App\Providers;

use App\Services\HtmlSanitizer;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HtmlSanitizer::class, static fn () => new HtmlSanitizer());
    }

    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            $client = new GoogleClient();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);

            $service = new GoogleDrive($client);
            $adapter = new GoogleDriveAdapter($service, $config['folder'] ?? '/');
            $driver  = new Filesystem($adapter);

            return new FilesystemAdapter($driver, $adapter);
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by(strtolower($email) . '|' . $request->ip());
        });

        RateLimiter::for('sync', function (Request $request) {
            $key = optional($request->user())->getKey() ?? $request->ip();

            return Limit::perMinute(10)->by('sync|' . $key);
        });

    }
}
