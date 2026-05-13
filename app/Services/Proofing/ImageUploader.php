<?php

namespace App\Services\Proofing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ImageUploader
{
    public function upload($fileContent, $remotePath, $filename)
    {
        $url = config('services.image_upload_url');
        $apiKey = config('services.image_upload_key');

        if (!$url || !$apiKey) {
            throw new Exception("Upload config missing");
        }

        try {
            $response = Http::timeout(60)
                ->withoutVerifying() // UAT ONLY (SSL issue)
                ->attach('image', $fileContent, $filename)
                ->post($url, [
                    'api_key' => $apiKey,
                    'path'    => $remotePath,
                ]);

            if (!$response->successful()) {
                Log::error("Upload failed", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                throw new Exception("Upload failed: " . $response->body());
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Upload exception: " . $e->getMessage());
            throw $e;
        }
    }
}