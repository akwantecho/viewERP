<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use App\Services\HtmlSanitizer;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Masbug\Flysystem\GoogleDriveAdapter;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;

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

    }
}
