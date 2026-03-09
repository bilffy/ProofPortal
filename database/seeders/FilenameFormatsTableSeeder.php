<?php

namespace Database\Seeders;

use App\Models\FilenameFormat;
use Illuminate\Database\Seeder;

class FilenameFormatsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $formats = [
            [
                'name' => 'First Name Last Name',
                'format' => '{subjects.portal_firstname} {subjects.portal_lastname}',
                'format_key' => 1,
                'visibility' => ['portraits'],
                'visibility_options' => ['portraits', 'groups'],
            ],
            [
                'name' => 'Last Name, First Name',
                'format' => '{subjects.portal_lastname}, {subjects.portal_firstname}',
                'format_key' => 2,
                'visibility' => ['portraits'],
                'visibility_options' => ['portraits', 'groups'],
            ],
            [
                'name' => 'Subject Key - Last Name, First Name',
                'format' => '{subjects.ts_subjectkey} - {subjects.portal_lastname}, {subjects.portal_firstname}',
                'format_key' => 3,
                'visibility' => ['portraits'],
                'visibility_options' => ['portraits', 'groups'],
            ],
            [
                'name' => 'Last Name, First Name (Folder Name)',
                'format' => '{subjects.portal_lastname}, {subjects.portal_firstname} ({folders.portal_ts_foldername})',
                'format_key' => 4,
                'visibility' => ['portraits'],
                'visibility_options' => ['portraits', 'groups'],
            ],
            [
                'name' => 'Folder Name',
                'format' => '{folders.portal_ts_foldername}',
                'format_key' => 5,
                'visibility' => ['groups'],
                'visibility_options' => ['portraits', 'groups'],
            ],
            [
                'name' => 'Folder Name Year',
                'format' => '{folders.portal_ts_foldername} {seasons.code}',
                'format_key' => 6,
                'visibility' => ['groups'],
                'visibility_options' => ['portraits', 'groups'],
            ],
            [
                'name' => 'Year Folder Name',
                'format' => '{seasons.code} {folders.portal_ts_foldername}',
                'format_key' => 7,
                'visibility' => ['groups'],
                'visibility_options' => ['portraits', 'groups'],
            ],
        ];

        foreach ($formats as $format) {
            try {
                FilenameFormat::create($format);
                echo "Format created: " . $format['name'] . "\n";
            } catch (\Exception $e) {
                // Handle the exception if needed
                echo "Error creating format [" . $format['name'] . "]: " . $e->getMessage() . "\n";
            }
        }
    }
}
