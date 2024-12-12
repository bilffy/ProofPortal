<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class ImageService
{   
    /**
     * Get all the years.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllYears()
    {
        return DB::table('seasons')
            ->select('code as Year')
            ->orderBy('code', 'desc')
            ->get();
    }
    
    /**
     * Get all the folder for views based on the selected season and school of selected folder tag.
     *
     * @param array $conditions
     * @return \Illuminate\Support\Collection
     */
    public function getFolderForView(int $seasonId, string $schoolKey, string $operator, string $folderTag)
    {
        return DB::table('schools')
            ->join('jobs', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('folder_tags', 'folder_tags.tag', '=', 'folders.folder_tag')
            ->where('jobs.ts_season_id', $seasonId)
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where('folders.folder_tag', $operator, $folderTag)
            ->select('folders.ts_foldername', 'folders.ts_folderkey')
            ->get();
    }
    
    /**
     * Get all the folders based on the selected tag of selected column visibility. 
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param string $selectedTag
     * @return \Illuminate\Support\Collection
     */
    public function getFoldersByTag(int $seasonId, string $schoolKey, string $selectedTag, string $visibilityColumn)
    {
        return DB::table('schools')
            ->join('jobs', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_season_id', $seasonId)
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where("folders.$visibilityColumn", 1)
            ->where('folders.folder_tag', $selectedTag)
            ->select('folders.ts_foldername', 'folders.ts_folderkey')
            ->get();
    }

    /**
     * Get all the images and subjects of the selected folder.
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param string $folderKey
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getImagesAndSubjectsByFolder(int $seasonId, string $schoolKey, string $folderKey, int $perPage = 15)
    {
        return DB::table('jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('subjects', 'subjects.ts_folder_id', '=', 'folders.ts_folder_id')
            ->join('images', 'images.keyvalue', '=', 'subjects.ts_subject_id')
            ->where('jobs.ts_season_id', $seasonId)
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where('folders.ts_folderkey', $folderKey)
            ->select('subjects.firstname', 'subjects.lastname', 'subjects.ts_subjectkey', 'images.*')
            ->paginate($perPage);
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
        $seasonId = $options['seasonId'];
        $schoolKey = $options['schoolKey'];
        $folderKey = $options['folderKey'];
        
        $images = $this->getImagesAndSubjectsByFolder($seasonId, $schoolKey, $folderKey, $perPage);
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
}