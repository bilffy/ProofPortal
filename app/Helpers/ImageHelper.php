<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public const IMAGE_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'bmp',
        // 'tiff', 'heic'
    ];

    public const NOT_FOUND_IMG = '/not_found.jpg';

    public static function getImagePath($path, $base = ''): string
    {
        $pathMatches = self::findImageFiles($path, $base);
        if (!empty($pathMatches)) {
            $basePath = self::getStorageBasePath($base);
            $path = str_replace($basePath, "", $pathMatches[0]);
            return $path;
        }
        return '';
    }

    public static function findImageFiles($path, $base = ''): array
    {
        $basePath = self::getStorageBasePath($base);
        // Define common image extensions
        $imageExtensions = self::getValidImageExtensions(true);
        $extensions = implode(',', $imageExtensions);
        
        // Create patterns for both base path and subdirectories
        $patterns = [
            $basePath . $path,           // Direct in base path
            $basePath . "*/" . $path,    // One level deep from base path
        ];
        
        $allFiles = [];
        foreach ($patterns as $pattern) {
            $files = glob($pattern . '.{' . $extensions . '}', GLOB_BRACE) ?: [];
            $allFiles = array_merge($allFiles, $files/*, $filesUpper*/);
        }
        
        // Remove duplicates and return
        return array_unique($allFiles);
    }

    public static function getStorageBasePath($basePath = ''): string
    {
        return empty($basePath) ? Storage::disk('local')->path('') : $basePath;
    }

    // return valid image (uppercase and lowercase) extensions
    public static function getValidImageExtensions($includeUpper = true): array
    {
        $validExtensions = self::IMAGE_EXTENSIONS;
        if ($includeUpper) {
            $validExtensions = array_merge($validExtensions, array_map('strtoupper', $validExtensions));
        }

        return $validExtensions;
    }

    public static function getExtensionsAsString($prefix = ''): string
    {
        $extensions = self::IMAGE_EXTENSIONS;
        if (empty($prefix)) {
            return implode(',', $extensions);
        }
        $extensionsWithPrefix = self::prefixExtensions($prefix);
        return implode(',', $extensionsWithPrefix);
    }

    public static function prefixExtensions(string $prefix = ''): array
    {
        if (empty($prefix)) {
            return self::IMAGE_EXTENSIONS;
        }
        return array_map(fn($ext) => $prefix . $ext, self::IMAGE_EXTENSIONS);
    }
}