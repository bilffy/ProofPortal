<?php

namespace App\Services\Storage;

class StorageFactory
{
    public static function make(): StorageServiceInterface
    {
        $lookup = strtolower(env('IMAGE_STORAGE_LOOKUP', 'local'));
        
        switch ($lookup) {
            case 's3':
            case 'aws':
                return new S3StorageService('s3');
            case 'file':
            default:
                return new FileStorageService();
        }
    }
}