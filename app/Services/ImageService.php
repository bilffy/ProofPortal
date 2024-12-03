<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class ImageService
{
    /**
     * Query the Folder model.
     *
     * @param array $conditions
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function queryFolder(array $options)
    {
        return Folder::where($options)->get();
    }

    /**
     * Query the Image model.
     *
     * @param array $conditions
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function queryImage(array $options)
    {
        return Image::where($options)->get();
    }

    /**
     * Get images from the local drive and return them as base64 strings.
     *
     * @param array $conditions
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getImagesAsBase64(array $options, int $perPage = 15)
    {
        $images = $this->queryImage($options, $perPage);
        $base64Images = $images->getCollection()->map(function ($image) {
            $path = $image->path; // Assuming the Image model has a 'path' attribute
            if (Storage::exists($path)) {
                $fileContent = Storage::get($path);
                return base64_encode($fileContent);
            }
            return null;
        })->filter();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $base64Images,
            $images->total(),
            $images->perPage(),
            $images->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get all the folders based on the selected tag.
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param string $selectedTag
     * @return array
     */
    public function getFoldersByTag(int $seasonId, string $schoolKey, string $selectedTag)
    {
        return DB::select("
            SELECT folders.ts_foldername, folders.ts_folderkey
            FROM schools
            JOIN jobs ON jobs.ts_schoolkey = schools.schoolkey
            JOIN folders ON folders.ts_job_id = jobs.ts_job_id
            WHERE jobs.ts_season_id = ? AND jobs.ts_schoolkey = ? AND folders.is_visible_for_portrait = 1 AND folders.folder_tag = ?
        ", [$seasonId, $schoolKey, $selectedTag]);
    }

    /**
     * Get all the images and subjects of the selected folder.
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param string $folderKey
     * @return array
     */
    public function getImagesAndSubjectsByFolder(int $seasonId, string $schoolKey, string $folderKey)
    {
        return DB::select("
            SELECT subjects.firstname, subjects.lastname, subjects.ts_subjectkey, images.*
            FROM jobs
            JOIN folders ON folders.ts_job_id = jobs.ts_job_id
            JOIN subjects ON subjects.ts_folder_id = folders.ts_folder_id
            JOIN images ON images.keyvalue = subjects.ts_subject_id
            WHERE jobs.ts_season_id = ? AND jobs.ts_schoolkey = ? AND folders.ts_folderkey = ?
        ", [$seasonId, $schoolKey, $folderKey]);
    }
}