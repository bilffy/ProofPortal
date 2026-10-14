<?php

namespace App\Services\Proofing;

/**
 * Pulls the displayable HTML out of a stored emails.email_content value.
 *
 * email_content is normally a full EML (headers + encoded body), but older
 * rows can already be plain HTML. Shared by the franchise email viewer
 * (EmailController::view) and the admin order detail page.
 */
class EmailBodyExtractor
{
    public function extract(string $eml): string
    {
        if ($eml === '') {
            return '<p class="text-muted mb-0">No email content.</p>';
        }

        // Already HTML (legacy / pre-rendered rows)
        if (preg_match('/^\s*(<!DOCTYPE\s+html|<html[\s>])/i', $eml)) {
            return $eml;
        }

        $parts = preg_split("/\r\n\r\n|\n\n/", $eml, 2);
        if (count($parts) < 2) {
            return '<pre class="mb-0" style="white-space: pre-wrap;">' . e($eml) . '</pre>';
        }

        [$headers, $body] = $parts;
        $encoding = null;
        if (preg_match('/Content-Transfer-Encoding:\s*([^\r\n;]+)/i', $headers, $match)) {
            $encoding = strtolower(trim($match[1]));
        }

        $contentType = '';
        if (preg_match('/Content-Type:\s*([^\r\n;]+)/i', $headers, $match)) {
            $contentType = strtolower(trim($match[1]));
        }

        // Multipart: find the text/html section
        if (str_starts_with($contentType, 'multipart/') && preg_match('/boundary=("?)([^";\r\n]+)\1/i', $headers, $boundaryMatch)) {
            $boundary = $boundaryMatch[2];
            $segments = preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?/', $body);
            foreach ($segments as $segment) {
                $segment = ltrim($segment, "\r\n");
                if ($segment === '' || !str_contains(strtolower($segment), 'text/html')) {
                    continue;
                }
                $html = $this->extract($segment);
                if ($html !== '' && !str_contains($html, 'No email content')) {
                    return $html;
                }
            }
        }

        $decoded = match ($encoding) {
            'base64' => base64_decode(preg_replace('/\s+/', '', $body), true),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };

        if ($decoded === false || $decoded === '') {
            return '<pre class="mb-0" style="white-space: pre-wrap;">' . e($eml) . '</pre>';
        }

        if (preg_match('/<html[\s>]|<!DOCTYPE\s+html/i', $decoded) || str_contains($contentType, 'text/html')) {
            return $decoded;
        }

        return '<pre class="mb-0" style="white-space: pre-wrap;">' . e($decoded) . '</pre>';
    }
}
