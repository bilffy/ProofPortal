<?php

namespace App\Services\Storage;

interface FileStorageInterface
{
    public function store(string $path, mixed $file, string $filename = null, array $options = []): string;

    public function retrieve(string $path);

    public function delete(string $path): bool;

    public function exists(string $path): bool;

    public function getUrl(string $path, array $options = []): string;
}