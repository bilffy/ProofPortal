<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderArtworkFile extends Model
{
    use HasFactory;

    protected $table = 'order_artwork_files';

    protected $fillable = [
        'order_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->path);
    }

    /**
     * False once the file has been removed by the artwork:cleanup command.
     */
    public function getFileExistsAttribute(): bool
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($this->path);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $value < 10 && $i > 0 ? 1 : 0) . ' ' . $units[$i];
    }

    /**
     * Whether this uploaded file is an image we can safely preview inline.
     */
    public function getIsImageAttribute(): bool
    {
        if ($this->mime_type && Str::startsWith($this->mime_type, 'image/')) {
            return true;
        }

        $extension = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }

    /**
     * A FontAwesome icon class representing this file's type, for non-image files.
     */
    public function getFileIconAttribute(): string
    {
        $extension = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'fa-file-pdf',
            'doc', 'docx' => 'fa-file-word',
            default => 'fa-file',
        };
    }
}
