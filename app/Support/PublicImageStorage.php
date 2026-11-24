<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PublicImageStorage
{
    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    public static function store(array $files, string $directory): array
    {
        $urls = [];

        foreach ($files as $file) {
            $path = $file->store($directory, 'public');

            if ($path === false) {
                throw new RuntimeException('Failed to store an uploaded image.');
            }

            $urls[] = '/storage/'.$path;
        }

        return $urls;
    }

    /**
     * Delete only files managed by this application in the specified directory.
     *
     * @param  array<int, string>  $urls
     */
    public static function delete(array $urls, string $directory): void
    {
        $diskUrl = Storage::disk('public')->url($directory);
        $diskParts = parse_url($diskUrl);
        $managedPrefix = rtrim(is_array($diskParts) ? ($diskParts['path'] ?? '') : '', '/').'/';

        foreach ($urls as $url) {
            if (! is_string($url)) {
                continue;
            }

            $urlParts = parse_url($url);
            if (! is_array($urlParts)) {
                continue;
            }

            if (isset($urlParts['host']) && (
                ! is_array($diskParts)
                || strtolower($urlParts['host']) !== strtolower($diskParts['host'] ?? '')
                || ($urlParts['port'] ?? null) !== ($diskParts['port'] ?? null)
            )) {
                continue;
            }

            $path = $urlParts['path'] ?? '';
            if (str_starts_with($path, $managedPrefix)) {
                Storage::disk('public')->delete(
                    $directory.'/'.substr($path, strlen($managedPrefix))
                );
            }
        }
    }
}
