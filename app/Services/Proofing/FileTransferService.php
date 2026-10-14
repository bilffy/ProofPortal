<?php

namespace App\Services\Proofing;

use App\Models\Order as OrderModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Zips an order's uploaded artwork files and hands the zip to the
 * external file-transfer API in the same two-step way the reference
 * (Python) client does it:
 *
 *   1. POST {FILE_TRANSFER_URL}/message/attachments/upload?filename=...
 *      with the zip's raw bytes as the body (application/octet-stream,
 *      NOT multipart) -> {"attachment": {"id": ...}}
 *   2. POST {FILE_TRANSFER_URL}/link with a JSON body asking for a
 *      single-download, no-auth-required link for that attachment,
 *      expiring FILE_TRANSFER_EXPIRY_DAY days from now
 *      -> {"link": {"url": ...}}
 *
 * Both calls use HTTP Basic Auth (FILE_TRANSFER_APIKEY as the username,
 * blank password) and retry up to 3 times on a thrown exception
 * (connection failure), matching the reference client's @async_retry.
 * That returned link.url is what the order notification email's
 * download button points to, instead of one link per uploaded file.
 */
class FileTransferService
{
    protected const MAX_ATTEMPTS = 3;
    protected const RETRY_DELAY_MS = 200;

    /**
     * @return string|null the shareable download link, or null if there
     *     were no files, the service isn't configured, or either API
     *     call failed. Callers should degrade gracefully - this must
     *     never block an order from submitting successfully.
     */
    public function uploadOrderArtwork(OrderModel $order): ?string
    {
        return $this->uploadOrderArtworkLinks($order, 1)[0] ?? null;
    }

    /**
     * Zips and uploads the order's artwork once, then creates $linkCount
     * separate single-download links to it (one per email recipient, so
     * one person's click can't use up another's link).
     *
     * @return string[] up to $linkCount urls, empty on failure/no files
     */
    public function uploadOrderArtworkLinks(OrderModel $order, int $linkCount = 1): array
    {
        $order->loadMissing('artworkFiles');

        if ($order->artworkFiles->isEmpty()) {
            return [];
        }

        $transferUrl = rtrim(trim((string) config('services.file_transfer.url')), '/');
        $apiKey = trim((string) config('services.file_transfer.api_key'));

        if ($transferUrl === '' || $apiKey === '') {
            Log::warning('FILE_TRANSFER_URL/FILE_TRANSFER_APIKEY not configured - skipping artwork file transfer.', [
                'order_id' => $order->id,
            ]);

            return [];
        }

        $expiryDays = (int) config('services.file_transfer.expiry_days', 7);
        $zipPath = $this->zipArtworkFiles($order);

        if ($zipPath === null) {
            return [];
        }

        try {
            $zipName = "order-{$order->order_number}-artwork.zip";

            $attachmentId = $this->uploadAttachment($transferUrl, $apiKey, $zipPath, $zipName, $order->id);

            if ($attachmentId === null) {
                return [];
            }

            $links = [];
            for ($i = 0; $i < max(1, $linkCount); $i++) {
                $url = $this->createSecureLink($transferUrl, $apiKey, $attachmentId, $expiryDays, $order->id);

                if ($url !== null) {
                    $links[$i] = $url;
                }
            }

            return $links;
        } finally {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
        }
    }

    /**
     * Step 1: upload the zip's raw bytes and get back its attachment id.
     */
    protected function uploadAttachment(string $transferUrl, string $apiKey, string $zipPath, string $filename, int $orderId): ?string
    {
        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->withHeaders(['Accept' => 'application/json'])
                ->withBody(file_get_contents($zipPath), 'application/octet-stream')
                ->retry(self::MAX_ATTEMPTS, self::RETRY_DELAY_MS)
                ->post($transferUrl . '/message/attachments/upload?' . http_build_query(['filename' => $filename]));

            Log::debug('File transfer upload response', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
            ]);

            if (!$response->successful()) {
                Log::error('File transfer upload failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $attachmentId = $response->json('attachment.id');

            if (empty($attachmentId)) {
                Log::error('File transfer upload succeeded but no attachment.id in the response', [
                    'order_id' => $orderId,
                    'body' => $response->body(),
                ]);

                return null;
            }

            return (string) $attachmentId;
        } catch (\Throwable $e) {
            Log::error('File transfer upload threw', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Step 2: create a single-download, no-auth-required link for the
     * uploaded attachment, expiring FILE_TRANSFER_EXPIRY_DAY days out.
     */
    protected function createSecureLink(string $transferUrl, string $apiKey, string $attachmentId, int $expiryDays, int $orderId): ?string
    {
        $expiresAt = Carbon::now('UTC')->addDays($expiryDays)->format('Y-m-d');

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->withHeaders(['Accept' => 'application/json'])
                ->retry(self::MAX_ATTEMPTS, self::RETRY_DELAY_MS)
                ->post($transferUrl . '/link', [
                    'link' => [
                        'attachment' => $attachmentId,
                        'expires_at' => $expiresAt,
                        'download_receipt' => false,
                        'downloads_limit' => 1,
                        'require_authentication' => false,
                    ],
                ]);

            Log::debug('File transfer link response', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
            ]);

            if (!$response->successful()) {
                Log::error('File transfer link creation failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $linkUrl = $response->json('link.url');

            if (empty($linkUrl)) {
                Log::error('File transfer link creation succeeded but no link.url in the response', [
                    'order_id' => $orderId,
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $linkUrl;
        } catch (\Throwable $e) {
            Log::error('File transfer link creation threw', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Zips every uploaded artwork file for the order into one temp file
     * under storage/app/tmp, using each file's original name inside the
     * zip (de-duplicated, in case two uploads shared the same filename).
     * Returns the temp zip's absolute path, or null if it couldn't be
     * built. Callers are responsible for deleting the temp file once
     * they're done with it.
     */
    protected function zipArtworkFiles(OrderModel $order): ?string
    {
        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir . '/order-' . $order->id . '-' . uniqid() . '.zip';

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::warning('Could not create zip for order artwork files', ['order_id' => $order->id]);

            return null;
        }

        $usedNames = [];

        foreach ($order->artworkFiles as $file) {
            $absolutePath = Storage::disk('public')->path($file->path);

            if (!is_file($absolutePath)) {
                continue;
            }

            $name = $file->original_name;
            $suffix = 1;

            while (in_array($name, $usedNames, true)) {
                $name = pathinfo($file->original_name, PATHINFO_FILENAME) . " ({$suffix})." . pathinfo($file->original_name, PATHINFO_EXTENSION);
                $suffix++;
            }

            $usedNames[] = $name;

            $zip->addFile($absolutePath, $name);
        }

        $zip->close();

        return $zipPath;
    }
}
