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
            $remotePath = self::remoteUploadPath($school, $filename);

            Log::info('Uploading school logo via HTTP', [
                'url' => config('services.image_upload_url'),
                'path' => $remotePath,
                'filename' => $filename,
            ]);

            (new ImageUploader())->upload(
                file_get_contents($file->getRealPath()),
                $remotePath,
                $filename
            );

            $publicUrl = self::publicUrl($school, $filename);

            Log::info('School logo uploaded via HTTP', [
                'path' => $remotePath,
                'filename' => $filename,
                'public_url' => $publicUrl,
            ]);

            if ($publicUrl !== null) {
                try {
                    $check = Http::timeout(15)->withoutVerifying()->head($publicUrl);

                    if (!$check->successful()) {
                        Log::warning('School logo not reachable after upload', [
                            'status' => $check->status(),
                            'public_url' => $publicUrl,
                            'remote_path' => $remotePath,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('School logo upload verification failed', [
                        'error' => $e->getMessage(),
                        'public_url' => $publicUrl,
                        'remote_path' => $remotePath,
                    ]);
                }
            }

            return;
        }

        $directory = self::localDirectory($school);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create school logos directory: {$directory}");
        }

        $file->move($directory, $filename);
    }

    public static function delete(School $school, string $filename): void
    {
        $filename = basename($filename);

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
