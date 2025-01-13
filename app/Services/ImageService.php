<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Image;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
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
            ->select('id', 'code as Year')
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
            ->leftJoin('folder_tags', 'folder_tags.tag', '=', 'folders.folder_tag')
            ->where('jobs.ts_season_id', $seasonId)
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where(function ($query) use ($operator, $folderTag) {
                $query->where('folders.folder_tag', $operator, $folderTag)
                    ->orWhereNull('folders.folder_tag');
            })
            ->select(DB::raw('COALESCE(folder_tags.external_name, "Student") as external_name'))
            ->distinct()
            ->get();
    }
    
    /**
     * Get all the folders based on the selected tag of selected column visibility. 
     *
     * @param int $seasonId
     * @param string|null $schoolKey
     * @param string $selectedTag
     * @return \Illuminate\Support\Collection
     */
    public function getFoldersByTag(int $seasonId, string|null $schoolKey, string $selectedTag, string $visibilityColumn)
    {
        $folderTags = DB::table('folder_tags')
            ->where('external_name', $selectedTag)
            ->select('tag')
            ->get()
            ->pluck('tag')
            ->toArray();

        $query = DB::table('schools')
            ->join('jobs', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_season_id', operator: $seasonId)
            ->where("folders.$visibilityColumn", 1);

            if ($schoolKey) {
                $query->where('jobs.ts_schoolkey', $schoolKey);
            }
            
            if ('ALL' !== $selectedTag) {
                $query->where(function ($query) use ($folderTags, $selectedTag) {
                    $query->whereIn('folders.folder_tag', $folderTags);
                    if ("Student" === $selectedTag) {
                        $query->orWhereNull('folders.folder_tag');
                    }
                });
            }

        return $query->select('folders.ts_foldername', 'folders.ts_folderkey')->get();
    }

    /**
     * Get all the images and subjects of the selected folder.
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param array $folderKeys
     * @param string $searchTerm
     * @return \Illuminate\Database\Query\Builder
     */
    public function getImagesAndSubjectsByFolder(int $seasonId, string $schoolKey, array $folderKeys, string $searchTerm)
    {
        $query = DB::table(table: 'jobs')
        ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('subjects', 'subjects.ts_folder_id', '=', 'folders.ts_folder_id')
        ->join('images', 'images.keyvalue', '=', 'subjects.ts_subjectkey')
        ->where('jobs.ts_season_id', operator: $seasonId)
        ->where('jobs.ts_schoolkey', $schoolKey);

        if($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('subjects.firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.lastname', 'like', "%$searchTerm%")
                    ->orWhere('folders.ts_foldername', 'like', "%$searchTerm%");
            });
        }

        if (!empty($folderKeys)) {
            $query->whereIn('folders.ts_folderkey', $folderKeys);
        }
        return $query->select('subjects.firstname', 'subjects.lastname', 'subjects.ts_subjectkey', 'images.*', 'folders.ts_foldername');
    }

    /**
     * Get images from the local drive and return them as base64 strings.
     *
     * @param array $conditions
     * @return Collection
     */
    public function getImagesAsBase64(array $options)
    {
        $seasonId = $options['tsSeasonId'];
        $schoolKey = $options['schoolKey'];
        $folderKeys = $options['folderKeys'];
        $search = $options['searchTerm'] ?? '';
        
        $images = $this->getImagesAndSubjectsByFolder($seasonId, $schoolKey, $folderKeys, $search);
        
        $base64Images = $images->get()->map(function ($image) {
            $path = $this->getPath($image->ts_subjectkey.".jpg"); 
            if (Storage::disk('local')->exists($path)) {
                $fileContent = Storage::disk('local')->get($path);
                $dimensions = getimagesizefromstring($fileContent);
                
                return [
                    'id' => $image->ts_subjectkey,
                    'base64' => null,//base64_encode($fileContent),
                    'firstname' => $image->firstname,
                    'lastname' => $image->lastname,
                    'isPortrait' => $dimensions[0] < $dimensions[1],
                    'classGroup' => $image->ts_foldername,
                ];
            }

            return [
                'id' => $image->ts_subjectkey,
                'base64' => null, //base64_encode(Storage::disk('local')->get("/not_found.jpg")),
                'firstname' => $image->firstname,
                'lastname' => $image->lastname,
                'classGroup' => $image->ts_foldername,
            ];
        });

        return $base64Images;
    }

    /**
     * This method is used to get the path of the image.
     * The directory is defined in the .env file.
     * @return string
     */
    public function getPath(string $filename)
    {
        return '/' . $filename;
    }

    /**
     * Paginate a given collection.
     *
     * @param \Illuminate\Support\Collection $items
     * @param int $perPage
     * @param int|null $page
     * @param array $options
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginate(Collection $items, int $perPage, $page = null, array $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            $options
        );
    }
}