<?php

namespace App\Services;

use App\Helpers\PhotographyHelper;
use App\Models\Folder;
use App\Models\Image;
use App\Models\SchoolPhotoUpload;
use App\Models\Subject;
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
            ->select('id', 'ts_season_id', 'code as Year')
            ->orderBy('code', direction: 'desc')
            ->get();
    }

    /**
     * Get all the years.
     * 
     * @param string $schoolKey
     * @param string $tab
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableYearsForSchool($schoolKey, $tab = '')
    {
        switch ($tab) {
            case PhotographyHelper::TAB_GROUPS:
            case PhotographyHelper::TAB_OTHERS:
                $visibilityColumn = 'is_visible_for_group';
                break;
            case PhotographyHelper::TAB_PORTRAITS:
                $visibilityColumn = 'is_visible_for_portrait';
                break;
            default:
                $visibilityColumn = '';
                break;
        }
        
        $query = DB::table('seasons')
            ->join('jobs', 'jobs.ts_season_id', '=', 'seasons.ts_season_id')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where(function ($q) use ($visibilityColumn) {
                if (empty($visibilityColumn)) {
                    $q->where('folders.is_visible_for_group', 1)
                        ->orWhere('folders.is_visible_for_portrait', 1);
                } else {
                    $q->where("folders.$visibilityColumn", 1);
                }
            })
        ;
        
        return $query
            ->select('seasons.id', 'seasons.ts_season_id', 'seasons.code as Year')
            ->orderBy('code', 'desc')
            ->distinct()
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
     * Get all the folder for views based on the selected season and school of selected folder tag.
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param string $tab
     * @return \Illuminate\Support\Collection
     */
    public function getFolderForView2(int $seasonId, string $schoolKey, string $tab)
    {

        $query = DB::table('schools')
            ->join('jobs', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->leftJoin('folder_tags', 'folder_tags.tag', '=', 'folders.folder_tag')
            ->where('jobs.ts_season_id', $seasonId)
            ->where('jobs.ts_schoolkey', $schoolKey);
            
        switch($tab) {
            case PhotographyHelper::TAB_GROUPS:
            case PhotographyHelper::TAB_OTHERS:
                $nullName = 'Class';
                $query->where(function ($q) {
                    $q->where('folder_tags.external_name', '!=', 'Family')
                        ->orWhereNull('folders.folder_tag');
                });
                break;
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $nullName = 'Student';
                $query->where(function ($q) {
                    $q->where('folders.folder_tag', '!=', 'SP')
                        ->orWhereNull('folders.folder_tag');
                });
                break;
        }

        return $query->select(DB::raw("COALESCE(folder_tags.external_name, \"$nullName\") as external_name"))
            ->distinct()
            ->get();
    }
    
    /**
     * Get all the folders based on the selected tag of selected column visibility. 
     *
     * @param int $seasonId
     * @param string|null $schoolKey
     * @param array $selectedTags
     * @param string $tab
     * @return \Illuminate\Support\Collection
     */
    public function getFoldersByTag(int $seasonId, string|null $schoolKey, array $selectedTags, string $tab)
    {
        $folderTags = DB::table('folder_tags')
            ->whereIn('external_name', $selectedTags)
            ->select('tag')
            ->get()
            ->pluck('tag')
            ->toArray();

        switch ($tab) {
            case PhotographyHelper::TAB_GROUPS:
            case PhotographyHelper::TAB_OTHERS:
                $visibilityColumn = 'is_visible_for_group';
                $nullTag = "Class";
                break;
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $visibilityColumn = 'is_visible_for_portrait';
                $nullTag = "Student";
                break;
        }

        $query = DB::table('schools')
            ->join('jobs', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_season_id', $seasonId)
            ->where("folders.$visibilityColumn", 1);

            if ($schoolKey) {
                $query->where('jobs.ts_schoolkey', $schoolKey);
            }
            
            $query->where(function ($query) use ($folderTags, $selectedTags, $nullTag) {
                $query->whereIn('folders.folder_tag', $folderTags);
                if (in_array($nullTag, $selectedTags)) {
                    $query->orWhereNull('folders.folder_tag');
                }
            });

        return $query
            ->select('folders.ts_foldername', 'folders.ts_folderkey', 'folders.ts_job_id')
            ->orderBy('folders.ts_foldername')
            ->get();
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
        ->where('jobs.ts_season_id', $seasonId)
        ->where('jobs.ts_schoolkey', $schoolKey);

        if($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('subjects.firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.lastname', 'like', "%$searchTerm%")
                    ->orWhere('folders.ts_foldername', 'like', "%$searchTerm%");
            });
        }
        $query->whereIn('folders.ts_folderkey', $folderKeys);
        
        return $query->select('subjects.firstname', 'subjects.lastname', 'subjects.ts_subjectkey', 'folders.ts_foldername');
    }

    /**
     * Get all the subjects of the selected folder(s).
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param array $folderKeys
     * @param string $searchTerm
     * @return \Illuminate\Database\Query\Builder
     */
    public function getSubjectsCollection(int $seasonId, string $schoolKey, array $folderKeys, string $searchTerm)
    {
        $query = DB::table(table: 'jobs')
        ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->leftJoin('folder_subjects', 'folder_subjects.ts_folder_id', '=', 'folders.ts_folder_id')
        ->join('subjects', function ($join) {
            $join->on('subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                 ->orOn('subjects.ts_subject_id', '=', 'folder_subjects.ts_subject_id');
        })
        ->where('jobs.ts_season_id', $seasonId)
        ->where('jobs.ts_schoolkey', $schoolKey);
        
        $query->where(function ($query) {
            $query->where(function ($subQuery) {
                // Case where 'portrait_download_date' is NULL, but 'download_available_date' is valid
                $subQuery->whereNull('jobs.portrait_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->where('jobs.download_available_date', '<=', now());
            });
            // in case portrait_download_date and download_available_date are both non-NULL
            // and the most recent date of the two dates is less than or equal to now
            $query->orWhere(function ($subQuery) {
                $subQuery->whereNotNull('jobs.portrait_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->whereRaw('GREATEST(jobs.portrait_download_date, jobs.download_available_date) <= ?', [now()]);
            });
        });

        if($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('subjects.firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.lastname', 'like', "%$searchTerm%")
                    ->orWhere('folders.ts_foldername', 'like', "%$searchTerm%");
            });
        }
        $query->whereIn('folders.ts_folderkey', $folderKeys);
        
        return $query
            ->select('subjects.firstname', 'subjects.lastname', 'subjects.ts_subjectkey')
            ->distinct()
            ->orderBy('subjects.lastname')
            ->orderBy('subjects.firstname');
    }

    /**
     * Get all the folders based on query filters.
     *
     * @param int $seasonId
     * @param string $schoolKey
     * @param array $folderKeys
     * @param string $searchTerm
     * @return \Illuminate\Database\Query\Builder
     */
    public function getFoldersCollection(int $seasonId, string $schoolKey, array $folderKeys, string $searchTerm)
    {
        $query = DB::table(table: 'jobs')
        // $query = DB::table(table: 'images')
        // ->join('jobs', 'jobs.ts_job_id', '=', 'images.ts_job_id')
        ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->where('jobs.ts_season_id', $seasonId)
        ->where('jobs.ts_schoolkey', $schoolKey)
        // ->where('images.keyorigin', 'Folder')
        ;
        
        $query->where(function ($query) {
            $query->where(function ($subQuery) {
                // Case where 'group_download_date' is NULL, but 'download_available_date' is valid
                $subQuery->whereNull('jobs.group_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->where('jobs.download_available_date', '<=', now());
            });
            // in case group_download_date and download_available_date are both non-NULL
            // and the most recent date of the two dates is less than or equal to now
            $query->orWhere(function ($subQuery) {
                $subQuery->whereNotNull('jobs.group_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->whereRaw('GREATEST(jobs.group_download_date, jobs.download_available_date) <= ?', [now()]);
            });
        });
        
        if($searchTerm) {
            $query->where('folders.ts_foldername', 'like', "%$searchTerm%");
        }
        $query->whereIn('folders.ts_folderkey', $folderKeys);
        
        return $query
            ->select('folders.ts_folderkey', 'folders.ts_foldername')
            ->orderBy('folders.ts_foldername');
    }

    /**
     * Get images from database using options given
     *
     * @param array $options
     * @param string $tab
     * @return Collection
     */
    public function getFilteredPhotographyImages(array $options, string $tab = PhotographyHelper::TAB_PORTRAITS): Collection
    {
        $seasonId = $options['tsSeasonId'];
        $schoolKey = $options['schoolKey'];
        $folderKeys = $options['folderKeys'];
        $search = $options['searchTerm'] ?? '';

        switch ($tab) {
            case PhotographyHelper::TAB_GROUPS:
            case PhotographyHelper::TAB_OTHERS:
                $images = $this->getFoldersCollection($seasonId, $schoolKey, $folderKeys, $search);
                break;
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $images = $this->getSubjectsCollection($seasonId, $schoolKey, $folderKeys, $search);
                break;
        }

        return $images->get();
    }

    /**
     * Get group/folder images from database using options given
     *
     * @param string $schoolKey
     * @param string $searchTerm
     * @param string $searchTerm2
     * @return Collection
     */
    public function getGroupImages($schoolKey, $searchTerm): Collection
    {
        $query = DB::table(table: 'jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where('folders.is_visible_for_group', 1)
        ;

        $query->where(function ($query) {
            $query->where(function ($subQuery) {
                // Case where 'portrait_download_date' is NULL, but 'download_available_date' is valid
                $subQuery->whereNull('jobs.portrait_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->where('jobs.download_available_date', '<=', now());
            });
            // in case portrait_download_date and download_available_date are both non-NULL
            // and the most recent date of the two dates is less than or equal to now
            $query->orWhere(function ($subQuery) {
                $subQuery->whereNotNull('jobs.portrait_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->whereRaw('GREATEST(jobs.portrait_download_date, jobs.download_available_date) <= ?', [now()]);
            });
        });

        if($searchTerm) {
            $query->where('folders.ts_foldername', 'like', "%$searchTerm%");
        }

        return $query
            ->select('folders.ts_folderkey', 'folders.ts_foldername', 'seasons.code as year')
            ->orderBy('folders.ts_foldername')
            ->get();
    }

    /**
     * Get images from database using options given
     *
     * @param string $schoolKey
     * @param string $searchTerm
     * @param string $searchTerm2
     * @return Collection
     */
    public function getSubjectImages($schoolKey, $searchTerm, $searchTerm2): Collection
    {
        $query = DB::table(table: 'jobs')
        ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
        ->leftJoin('folder_subjects', 'folder_subjects.ts_folder_id', '=', 'folders.ts_folder_id')
        ->join('subjects', function ($join) {
            $join->on('subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                 ->orOn('subjects.ts_subject_id', '=', 'folder_subjects.ts_subject_id');
        })
        ->where('jobs.ts_schoolkey', $schoolKey)
        ->where('folders.is_visible_for_portrait', 1)
        ;
        
        $query->where(function ($query) {
            $query->where(function ($subQuery) {
                // Case where 'portrait_download_date' is NULL, but 'download_available_date' is valid
                $subQuery->whereNull('jobs.portrait_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->where('jobs.download_available_date', '<=', now());
            });
            // in case portrait_download_date and download_available_date are both non-NULL
            // and the most recent date of the two dates is less than or equal to now
            $query->orWhere(function ($subQuery) {
                $subQuery->whereNotNull('jobs.portrait_download_date')
                    ->whereNotNull('jobs.download_available_date')
                    ->whereRaw('GREATEST(jobs.portrait_download_date, jobs.download_available_date) <= ?', [now()]);
            });
        });

        if($searchTerm) {
            $query->where(function ($query) use ($searchTerm, $searchTerm2) {
                $query->where('subjects.firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.lastname', 'like', "%$searchTerm2%");
            });
        }
        
        return $query
            ->select('subjects.firstname', 'subjects.lastname', 'subjects.ts_subjectkey', 'seasons.code as year')
            ->distinct()
            ->orderBy('year')
            ->orderBy('subjects.lastname')
            ->orderBy('subjects.firstname')
            ->get();
    }

    /**
     * Get images from the local drive and return them as base64 strings.
     *
     * @param Collection
     * @return Collection
     */
    public function getImagesAsBase64($images, $tab = PhotographyHelper::TAB_PORTRAITS): Collection
    {
        switch ($tab) {
            case PhotographyHelper::TAB_GROUPS:
            case PhotographyHelper::TAB_OTHERS:
                $key = 'ts_folderkey';
                $category = 'FOLDER';
                break;
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $key = 'ts_subjectkey';
                $category = 'SUBJECT';
                break;
        }

        $toData = function ($image) use ($key, $category) {
            $isSubject = $category != 'FOLDER';
            $imgKey = $image->$key;
            if ($isSubject) {
                $subject = Subject::where('ts_subjectkey', $image->$key)->first();
                $img = Image::where('keyvalue', $image->$key)->where('keyorigin', (strtolower($category)))->first();
                if ($subject && $img) {
                    $uploaded = SchoolPhotoUpload::where('subject_id', $subject->id)->where('image_id', $img->id)->exists();
                } else {
                    $uploaded = false;
                }
            } else {
                $folder = Folder::where('ts_folderkey', $image->$key)->first();
                $img = Image::where('keyvalue', $image->$key)->where('keyorigin', (strtolower($category)))->first();
                if ($folder && $img) {
                    $uploaded = SchoolPhotoUpload::where('folder_id', $folder->id)->where('image_id', $img->id)->exists();
                } else {
                    $uploaded = false;
                }
            }
            $fileContent = $this->getImageContent($imgKey);
            
            $dimensions = getimagesizefromstring($fileContent);

            if ($isSubject) {
                $subject = Subject::where('ts_subjectkey', $image->$key)->first();
                $classGroup = $subject->folder->ts_foldername ?? '';
            } else {
                $classGroup = $image->ts_foldername;
            }
                
            return [
                'id' => base64_encode(base64_encode($image->$key)),
                'firstname' => $isSubject ? $image->firstname : '',
                'lastname' => $isSubject ? $image->lastname : '',
                'isPortrait' => $dimensions[0] <= $dimensions[1],
                'classGroup' => $classGroup,
                'year' => $image->year ?? 0,
                'category' => $category,
                'isUploaded' => $uploaded,
            ];
        };

        return $images->map($toData);
    }

    /**
     * Get File Content based on $key value
     * @param string $key
     * @return string|null
     */
    public function getImageContent($key)
    {
        $path = $this->getPath($key.".jpg");
        if (Storage::disk('local')->exists($path)) {
            $isFound = true;
        } else {
            $img = Image::where('keyvalue', $key)->first();
            if ($img) {
                $path = $this->getPath("uploaded_images/" . $img->ts_imagekey . ".jpg");
                $isFound = Storage::disk('local')->exists($path);
            } else {
                $isFound = false;
            }
        }
        $fileContent = Storage::disk('local')->get($isFound ? $path : "/not_found.jpg");

        return $fileContent;
    }

    /**
     * Check if image is found based on $key value
     * @param string $key
     * @return boolean
     */
    public function getIsImageFound($key)
    {

        $path = $this->getPath($key.".jpg");
        if (Storage::disk('local')->exists($path)) {
            return true;
        } else {
            $img = Image::where('keyvalue', $key)->first();
            if ($img) {
                $path = $this->getPath("uploaded_images/" . $img->ts_imagekey . ".jpg");
            } else {
                return false;
            }
        }
        
        return Storage::disk('local')->exists($path);
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
    public function paginate(Collection $items, int $perPage, $page = null, array $options = [])
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
    
    /**
     * Get all the images based on the selected portal subject id.
     *
     * @param string $subjectId
     * 
     */
    public function getPortraitImagesByPortalSubjectId(string $subjectId)
    {
        // TODO: This should return images based on the subjectId  
    }

    /**
     * Get all the Image Options.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getImageOptions()
    {
        return DB::table('image_options')
            ->select('id', 'display_name')
            ->orderBy('id', 'asc')
            ->get();
    }
}