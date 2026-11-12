<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Snapshot of the current image_options table, generated on 2026-11-12 14:52:09 (7 row(s)).
 *
 * Re-runnable: rows are upserted by id, so running it again updates the existing rows
 * instead of duplicating them. Rows that no longer exist are not deleted.
 *
 * `size` is DIGI1 / DIGI2 for the pre-made LowRes / HighRes files, otherwise a JSON
 * {"width","height","dpi"} that the download is cut to (see ImageOptions::targetSize()).
 * `visibility` is a JSON list of the tabs the option is offered on.
 *
 * Run with: php artisan db:seed --class=ImageOptionsSeeder
 *
 * NOTE: the create_image_options migrations do not create the name, size, isDefault and
 * visibility columns; this seeder needs them to exist in the table.
 */
class ImageOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'id' => 1,
                'display_name' => 'Screen Quality - resolution: 200 height: 72 dpi',
                'name' => 'LowRes',
                'size' => 'DIGI1',
                'isDefault' => '0',
                'visibility' => '[""]',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 2,
                'display_name' => 'Screen Quality - resolution: 400 height: 72 dpi',
                'name' => 'HighRes',
                'size' => 'DIGI2',
                'isDefault' => '1',
                'visibility' => '["portraits"]',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 3,
                'display_name' => 'Screen Quality - resolution: 400 X 400: 72 dpi',
                'name' => 'OneSchool',
                'size' => '{"width": 400, "height": 400, "dpi": 72}',
                'isDefault' => '0',
                'visibility' => '["portraits"]',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 4,
                'display_name' => 'Screen Quality - resolution: 150 X 200: 72 dpi',
                'name' => 'iLINQ',
                'size' => '{"width": 150, "height": 200, "dpi": 72}',
                'isDefault' => '0',
                'visibility' => '["portraits"]',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 5,
                'display_name' => 'Screen Quality - resolution: 100 X 125: 72 dpi',
                'name' => 'Oliver',
                'size' => '{"width": 100, "height": 125, "dpi": 72}',
                'isDefault' => '0',
                'visibility' => '["portraits"]',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 6,
                'display_name' => 'Screen Quality - resolution: 500 X 625: 72 dpi',
                'name' => 'TASS Thumbnails',
                'size' => '{"width": 500, "height": 625, "dpi": 72}',
                'isDefault' => '0',
                'visibility' => '["portraits"]',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 7,
                'display_name' => 'Screen Quality - resolution: 500 X 700: 72 dpi',
                'name' => 'TASS',
                'size' => '{"width": 500, "height": 700, "dpi": 72}',
                'isDefault' => '0',
                'visibility' => '["portraits"]',
                'created_at' => null,
                'updated_at' => null,
            ],
        ];

        DB::table('image_options')->upsert($rows, ['id'], array_values(array_diff(array_keys($rows[0]), ['id'])));
    }
}
