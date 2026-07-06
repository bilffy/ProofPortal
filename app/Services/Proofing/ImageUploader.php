<?php

namespace App\Services\Proofing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ImageUploader
{
    /**
     * Upload a single image file (Kept for backward compatibility).
     *
     * @param string $fileContent
     * @param string $remotePath
     * @param string $filename
     * @return bool
     * @throws Exception
     */
    public function upload($fileContent, $remotePath, $filename)
    {
        $url = config('services.image_upload_url');
        $apiKey = config('services.image_upload_key');

        if (!$url || !$apiKey) {
            throw new Exception("Upload config missing");
        }

        try {
            $response = Http::timeout(60)
                ->withoutVerifying()
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

        } catch (Exception $e) {
            Log::error("Upload exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload a batch of multiple images in a single multi-part HTTP request.
     *
     * @param array $filesData Array of items, each containing ['content' => ..., 'filename' => ..., 'remote_path' => ...]
     * @return bool
     * @throws Exception
     */
    public function uploadBatch(array $filesData)
    {
        $url = config('services.image_upload_url');
        $apiKey = config('services.image_upload_key');

        if (!$url || !$apiKey) {
            throw new Exception("Upload config missing");
        }

        try {
            // Initialize a multipart HTTP request with an extended safe timeout for larger batch transfers
            $request = Http::timeout(90)
                ->withoutVerifying();

            $metaData = [];

            // Iterate through the array payload to attach each individual file to the request structure
            foreach ($filesData as $file) {
                $request->attach(
                    'images[]', 
                    $file['content'], 
                    $file['filename']
                );

                // Build mapping metadata so the destination server knows the intended target subdirectory paths
                $metaData[] = [
                    'filename'    => $file['filename'],
                    'remote_path' => $file['remote_path']
                ];
            }

            // Post the request payload alongside the security credentials and serialized JSON metadata
            $response = $request->post($url, [
                'api_key'  => $apiKey,
                'metadata' => json_encode($metaData)
            ]);

            if (!$response->successful()) {
                Log::error("Batch Upload failed", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                throw new Exception("Batch Upload failed: " . $response->body());
            }

            return true;

        } catch (Exception $e) {
            Log::error("Batch Upload exception: " . $e->getMessage());
            throw $e;
        }
    }
}