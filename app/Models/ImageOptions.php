<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageOptions extends Model
{
    use HasFactory;
    protected $table = "image_options";

    protected $fillable = [
        'display_name',
    ];

    /**
     * Target pixel size from the `size` column ({"width": 400, "height": 400, "dpi": 72}),
     * or null for the DIGI1 / DIGI2 options, which use a pre-made file as is (see sourceSuffix()).
     *
     * @return array{width: int, height: int, dpi: int}|null
     */
    public function targetSize(): ?array
    {
        $size = json_decode((string) $this->size, true);

        if (!is_array($size) || empty($size['width']) || empty($size['height'])) {
            return null;
        }

        return [
            'width' => (int) $size['width'],
            'height' => (int) $size['height'],
            'dpi' => (int) ($size['dpi'] ?? 72),
        ];
    }

    /**
     * File suffix of the pre-made image served as is: DIGI1 (LowRes) is `_200`, anything else `_400`.
     */
    public function sourceSuffix(): string
    {
        return strtoupper(trim((string) $this->size)) === 'DIGI1' ? '_200' : '_400';
    }
}
