<?php

namespace App\Providers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;            // Flysystem (v3)
use Masbug\Flysystem\GoogleDriveAdapter;                 // Adapter
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('google', function ($app, array $config) {
            // 1) مسار JSON
            $jsonPath = $config['credentials_json']
                ?? env('GOOGLE_DRIVE_CREDENTIALS_JSON')
                ?? storage_path('app/google/credentials.json');

            if (! file_exists($jsonPath)) {
                throw new \RuntimeException("Google Drive credentials JSON not found: {$jsonPath}");
            }

            // 2) تهيئة عميل جوجل
            $client = new Client();
            $client->setAuthConfig($jsonPath);
            $client->addScope(Drive::DRIVE);

            // 3) خدمة درايف + الأدابتر
            $service  = new Drive($client);
            $folderId = $config['folderId'] ?? env('GOOGLE_DRIVE_FOLDER_ID') ?: null;
            $adapter  = new GoogleDriveAdapter($service, $folderId);

            // 4) غلّف الـ Flysystem داخل FilesystemAdapter الخاص بلارافيل
            $flysystem = new Flysystem($adapter, $config['flysystem'] ?? []);

            return new LaravelFilesystemAdapter($flysystem, $adapter, $config);
        });
    }
}
