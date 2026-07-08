<?php

namespace App\Helpers;

use App\Models\School;
use App\Services\Proofing\ImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SchoolLogoHelper
{
    public static function relativeDirectory(School $school): string
    {
        return sprintf(
            'school_logos/%s/%s/%s',
            self::resolveAlphacode($school),
            $school->schoolkey,
            $school->id
        );
    }

    public static function relativePath(School $school, string $filename): string
    {
        return self::relativeDirectory($school) . '/' . basename($filename);
    }

    public static function publicUrl(School $school, string $filename): ?string
    {
        $base = config('services.exportImageLocation');

        if (!filled($base) || !self::isUrl($base)) {
            return null;
        }

        return rtrim($base, '/') . '/' . self::relativePath($school, $filename);
    }

    public static function fetchRemoteContents(School $school, string $filename): ?string
    {
        $url = self::publicUrl($school, $filename);

        if ($url === null) {
            return null;
        }

        try {
            $response = Http::timeout(15)->withoutVerifying()->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function resolveLocalPath(School $school, string $filename): ?string
    {
        if (self::canUseFilesystemStorage()) {
            $filesystemPath = self::filesystemPath($school, $filename);

            if (is_file($filesystemPath)) {
                return $filesystemPath;
            }
        }

        $structuredPath = self::localDirectory($school) . '/' . basename($filename);

        if (is_file($structuredPath)) {
            return $structuredPath;
        }

        $legacyPath = storage_path('app/public/school_logos/' . basename($filename));

        return is_file($legacyPath) ? $legacyPath : null;
    }

    public static function store(UploadedFile $file, School $school, string $filename): void
    {
        $filename = basename($filename);

        if (self::canUseHttpUpload()) {
            self::storeViaHttp($file, $school, $filename);

            return;
        }

        if (self::canUseFilesystemStorage()) {
            self::storeOnFilesystem($file, $school, $filename);

            Log::info('School logo stored on proofing cache filesystem', [
                'path' => self::filesystemPath($school, $filename),
                'public_url' => self::publicUrl($school, $filename),
            ]);

            return;
        }

        $directory = self::localDirectory($school);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create school logos directory: {$directory}");
        }

        $file->move($directory, $filename);
    }

    private static function storeViaHttp(UploadedFile $file, School $school, string $filename): void
    {
        $remotePath = self::remoteUploadPath($school, $filename);
        $fileContent = file_get_contents($file->getRealPath());

        Log::info('Uploading school logo via HTTP batch', [
            'url' => config('services.image_upload_url'),
            'remote_path' => $remotePath,
            'filename' => $filename,
        ]);

        $response = (new ImageUploader())->uploadBatchWithDetails([[
            'content' => $fileContent,
            'filename' => $filename,
            'remote_path' => $remotePath,
        ]]);

        Log::info('School logo HTTP upload response', [
            'remote_path' => $remotePath,
            'filename' => $filename,
            'status' => $response['status'],
            'body' => $response['body'],
            'metadata' => $response['metadata'],
            'public_url' => self::publicUrl($school, $filename),
        ]);

        if (!self::verifyPublicUrl($school, $filename)) {
            throw new RuntimeException(
                'School logo upload was accepted by prod02 but the file is not reachable at '
                . self::publicUrl($school, $filename)
                . '. The /uat upload handler may need to allow nested school_logos paths.'
            );
        }
    }

    private static function storeOnFilesystem(UploadedFile $file, School $school, string $filename): void
    {
        $directory = self::filesystemDirectory($school);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create school logos directory: {$directory}");
        }

        $file->move($directory, $filename);
    }

    private static function verifyPublicUrl(School $school, string $filename): bool
    {
        $publicUrl = self::publicUrl($school, $filename);

        if ($publicUrl === null) {
            return self::isFileOnFilesystem($school, $filename);
        }

        try {
            $response = Http::timeout(15)->withoutVerifying()->get($publicUrl);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('School logo upload verification failed', [
                'error' => $e->getMessage(),
                'public_url' => $publicUrl,
            ]);

            return self::isFileOnFilesystem($school, $filename);
        }
    }

    private static function isFileOnFilesystem(School $school, string $filename): bool
    {
        if (!self::canUseFilesystemStorage()) {
            return false;
        }

        return is_file(self::filesystemPath($school, $filename));
    }

    private static function filesystemPath(School $school, string $filename): string
    {
        return self::filesystemDirectory($school) . '/' . basename($filename);
    }

    private static function filesystemDirectory(School $school): string
    {
        return rtrim((string) config('services.export_image_path'), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, self::relativeDirectory($school));
    }

    public static function delete(School $school, string $filename): void
    {
        $filename = basename($filename);

        if (self::canUseFilesystemStorage()) {
            $filesystemPath = self::filesystemPath($school, $filename);

            if (is_file($filesystemPath)) {
                @unlink($filesystemPath);
            }
        }

        $structuredPath = self::localDirectory($school) . '/' . $filename;

        if (is_file($structuredPath)) {
            @unlink($structuredPath);
        }

        $legacyPath = storage_path('app/public/school_logos/' . $filename);

        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }

    public static function resolveAlphacode(School $school): string
    {
        $franchise = $school->relationLoaded('franchises')
            ? $school->franchises->first()
            : $school->franchises()->first();

        if ($franchise === null || !filled($franchise->alphacode)) {
            throw new RuntimeException('School has no associated franchise alphacode.');
        }

        return $franchise->alphacode;
    }

    private static function remoteUploadPath(School $school, string $filename): string
    {
        // Match export/group uploads: path is relative to the /uat upload root
        // (GODRGLDYXVCDXGM/UAT), not prefixed with that segment again.
        return self::relativePath($school, $filename);
    }

    private static function localDirectory(School $school): string
    {
        return storage_path('app/public/' . self::relativeDirectory($school));
    }

    private static function canUseFilesystemStorage(): bool
    {
        $path = config('services.export_image_path');

        return filled($path) && !self::isUrl((string) $path) && is_dir((string) $path);
    }

    private static function canUseHttpUpload(): bool
    {
        return filled(config('services.image_upload_url'))
            && filled(config('services.image_upload_key'));
    }

    private static function isUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://#i', $value);
    }
}
