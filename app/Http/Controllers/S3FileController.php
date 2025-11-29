<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\Flysystem\StorageAttributes;

class S3FileController extends Controller
{
    public function index(Request $request): View
    {
        if (!config('filesystems.disks.s3')) {
            return view('files.s3', [
                'diskReady'   => false,
                'currentDir'  => '',
                'parentDir'   => null,
                'directories' => collect(),
                'files'       => collect(),
                'breadcrumbs' => collect(),
                'error'       => null,
            ]);
        }

        $dir = trim($request->get('dir', ''), '/');
        $disk = Storage::disk('s3');
        $error = null;
        $directories = collect();
        $files = collect();

        try {
            $listing = collect($disk->listContents($dir === '' ? '' : $dir, false))
                ->map(function (StorageAttributes $item) {
                    $data = [
                        'type' => $item->isDir() ? 'dir' : 'file',
                        'path' => trim($item->path(), '/'),
                    ];
                    if ($item->isFile()) {
                        try {
                            $data['fileSize'] = $item->fileSize();
                        } catch (\Throwable $e) {
                            $data['fileSize'] = null;
                        }
                        try {
                            $data['lastModified'] = $item->lastModified();
                        } catch (\Throwable $e) {
                            $data['lastModified'] = null;
                        }
                    }
                    return $data;
                });

            $directories = $listing->where('type', 'dir')
                ->map(fn ($entry) => [
                    'name' => basename($entry['path']) ?: $entry['path'],
                    'path' => $entry['path'],
                ])
                ->sortBy('name')
                ->values();

            $files = $listing->where('type', 'file')
                ->map(function ($entry) use ($disk) {
                    $path = $entry['path'];
                    $url = null;
                    try {
                        $url = $disk->temporaryUrl($path, now()->addMinutes(5));
                    } catch (\Throwable $e) {
                        try {
                            $url = $disk->url($path);
                        } catch (\Throwable $ignored) {
                            $url = null;
                        }
                    }

                    return [
                        'name'         => basename($path),
                        'path'         => $path,
                        'size'         => $entry['fileSize'],
                        'lastModified' => $entry['lastModified'],
                        'url'          => $url,
                    ];
                })
                ->sortBy('name')
                ->values();
        } catch (\Throwable $e) {
            report($e);
            $error = 'Unable to read bucket contents: ' . $e->getMessage();
        }

        $breadcrumbs = $this->buildBreadcrumbs($dir);

        return view('files.s3', [
            'diskReady'   => true,
            'currentDir'  => $dir,
            'parentDir'   => $this->parentDir($dir),
            'directories' => $directories,
            'files'       => $files,
            'breadcrumbs' => $breadcrumbs,
            'error'       => $error,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file'     => ['required', 'file'],
            'dir'      => ['nullable', 'string'],
            'filename' => ['nullable', 'string', 'max:255'],
            'replace'  => ['nullable', 'boolean'],
        ]);

        if (!config('filesystems.disks.s3')) {
            return back()->with('error', 'S3 disk is not configured.');
        }

        $disk = Storage::disk('s3');
        $dir = trim($request->input('dir', ''), '/');
        $file = $request->file('file');
        $targetName = trim($request->input('filename') ?: $file->getClientOriginalName());
        $targetName = $targetName === '' ? $file->getClientOriginalName() : $targetName;

        $targetPath = ltrim(($dir ? $dir . '/' : '') . $targetName, '/');

        if (!$request->boolean('replace') && $disk->exists($targetPath)) {
            $targetName = $this->makeUniqueName($dir, $targetName, $disk);
            $targetPath = ltrim(($dir ? $dir . '/' : '') . $targetName, '/');
        }

        $disk->putFileAs($dir === '' ? '' : $dir, $file, $targetName);

        return redirect()->route('storage.files.index', ['dir' => $dir])
            ->with('success', 'File uploaded successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
            'dir'  => ['nullable', 'string'],
        ]);

        if (!config('filesystems.disks.s3')) {
            return back()->with('error', 'S3 disk is not configured.');
        }

        $disk = Storage::disk('s3');
        $disk->delete($request->string('path')->value());

        return redirect()->route('storage.files.index', ['dir' => $request->input('dir')])
            ->with('success', 'File deleted successfully.');
    }

    private function makeUniqueName(string $dir, string $name, $disk): string
    {
        $directory = trim($dir, '/');
        $base = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        $counter = 1;
        do {
            $candidate = $base . '-' . $counter . ($extension ? '.' . $extension : '');
            $path = ltrim(($directory ? $directory . '/' : '') . $candidate, '/');
            if (!$disk->exists($path)) {
                return $candidate;
            }
            $counter++;
        } while ($counter < 200);

        return $base . '-' . Str::random(5) . ($extension ? '.' . $extension : '');
    }

    private function buildBreadcrumbs(string $dir): Collection
    {
        if ($dir === '') {
            return collect();
        }

        $segments = explode('/', $dir);
        $crumbs = collect();
        $path = '';
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            $path = ltrim(($path ? $path . '/' : '') . $segment, '/');
            $crumbs->push([
                'label' => $segment,
                'path'  => $path,
            ]);
        }

        return $crumbs;
    }

    private function parentDir(string $dir): ?string
    {
        if ($dir === '') {
            return null;
        }

        if (!Str::contains($dir, '/')) {
            return '';
        }

        return trim(Str::beforeLast($dir, '/'), '/');
    }
}
