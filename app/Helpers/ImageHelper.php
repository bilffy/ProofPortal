<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public const FLAG_STRICT_PATTERN = 0;
    public const FLAG_SOFT_PATTERN = 1;
    public const IMAGE_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'bmp',
        // 'tiff', 'heic'
    ];

    public const NOT_FOUND_IMG = '/absent-image.jpg';

    public static function getImagePath($path, $base = '', $patternLeniency = self::FLAG_SOFT_PATTERN): string
    {
        switch ($patternLeniency) {
            case self::FLAG_STRICT_PATTERN:
                $pathMatches = self::findImageFilesStrict($path, $base);
                break;
            case self::FLAG_SOFT_PATTERN:
            default:
                $pathMatches = self::findImageFiles($path, $base);
                break;
        }
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
        $imageExtensions = self::getValidImageExtensions(false);
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

    public static function findImageFilesStrict($path, $base = ''): array
    {
        $basePath = self::getStorageBasePath($base);
        // Define common image extensions
        $imageExtensions = self::getValidImageExtensions(false);
        $extensions = implode(',', $imageExtensions);
        $files = glob($basePath . $path . '.{' . $extensions . '}', GLOB_BRACE) ?: [];
        // Remove duplicates and return
        return array_unique($files);
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