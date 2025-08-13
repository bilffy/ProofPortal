<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService implements FileStorageInterface
{
    protected string $driver;

    public function __construct(?string $driver = null)
    {
        // Use env variable, then constructor param, then Laravel default
        $this->driver = env('FILE_IMAGE_UPLOAD_STORAGE', $driver ?? config('filesystems.default', 'local'));
    }

    /**
     * Store a file in the given path.
     *
     * @param string $path Folder path in storage
     * @param mixed  $file Uploaded file (Illuminate\Http\UploadedFile or raw content)
     * @param string|null $filename Desired filename (with extension)
     * @param array  $options Additional storage options (e.g., visibility)
     *
     * @return string Path where file was stored
     */
    public function store(string $path, mixed $file, ?string $filename = null, array $options = []): string
    {
        // Determine filename
        if (!$filename) {
            if (method_exists($file, 'getClientOriginalName')) {
                // Sanitize original filename
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $filename = Str::slug($originalName) . '.' . strtolower($extension);
            } else {
                // Fallback random name
                $filename = Str::random(40) . '.dat';
            }
        }

        $fullPath = trim($path, '/') . '/' . $filename;

        // Store file content
        Storage::disk($this->driver)->put(
            $fullPath,
            file_get_contents($file),
            $options['visibility'] ?? 'public' // Default is public now
        );

        return $fullPath;
    }

    public function retrieve(string $path)
    {
        if (!$this->exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        return Storage::disk($this->driver)->get($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->driver)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->driver)->exists($path);
    }

    public function getUrl(string $path, array $options = []): string
    {
        return Storage::disk($this->driver)->url($path);
    }

    /**
     * Retrieve file contents as Base64 string.
     *
     * @param string $path
     * @return string Base64 encoded data URI
     * @throws \Exception
     */
    public function getBase64(string $path): string
    {
        if (!$this->exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        $contents = Storage::disk($this->driver)->get($path);

        // Guess MIME type from extension
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mime = $this->guessMimeType($extension);

        return "data:{$mime};base64," . base64_encode($contents);
    }

    /**
     * Guess MIME type based on file extension.
     */
    protected function guessMimeType(string $extension): string
    {
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf'
        ];

        return $map[strtolower($extension)] ?? 'application/octet-stream';
    }
}