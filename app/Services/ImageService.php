<?php

namespace App\Services;

use App\Helpers\FilenameFormatHelper;
use App\Helpers\ImageHelper;
use App\Helpers\PhotographyHelper;
use App\Models\Folder;
use App\Models\Image;
use App\Models\SchoolPhotoUpload;
use App\Models\Subject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache; // code by IT
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // code by IT
class ImageService
{   
    // code by IT
    protected static $urlCache = [];
    protected static $existenceCache = [];

    public function clearCache()
    {
        self::$urlCache = [];
        self::$existenceCache = [];
    }
    // code by IT
    /**
     * Get all the years.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllYears()
    {
        return DB::table('seasons')
            ->select('id', 'ts_season_id', 'code as Year')
            ->where('is_default', 1) // code by IT
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
                $visibilityColumn = 'is_visible_for_group'; // code by IT
                break; // code by IT
            case PhotographyHelper::TAB_OTHERS:
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
            ->whereNotNull('folders.ts_folderkey') // code by IT
            ->where(function ($q) use ($visibilityColumn) {
                if (empty($visibilityColumn)) {
                    $q->where('folders.is_visible_for_group', 1)
                        ->orWhere('folders.is_visible_for_portrait', 1);
                } else {
                    $q->where("folders.$visibilityColumn", 1);
                }
            })
            ->where('seasons.is_default', 1); // code by IT
        
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
            ->whereNotNull('folders.ts_folderkey') // code by IT
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
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->whereNotNull('folders.ts_folderkey'); // code by IT
            
        switch($tab) {
            case PhotographyHelper::TAB_GROUPS:
                 // code by IT
                $nullName = 'Class';
                $query->where(function ($q) {
                    $q->where('folder_tags.external_name', '!=', 'Family')
                        ->orWhereNull('folders.folder_tag');
                });
                break;
                 // code by IT
            case PhotographyHelper::TAB_OTHERS:
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
                 // code by IT
                $visibilityColumn = 'is_visible_for_group';
                $nullTag = "Class";
                break;
                 // code by IT
            case PhotographyHelper::TAB_OTHERS:
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $visibilityColumn = 'is_visible_for_portrait';
                $nullTag = "Student";
                break;
        }

        $query = DB::table('schools')
            ->join('jobs', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->where('jobs.ts_season_id', $seasonId)
            ->where("folders.$visibilityColumn", 1)
            ->whereNotNull('folders.ts_folderkey'); // code by IT

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
            ->select('folders.portal_ts_foldername', 'folders.ts_folderkey', 'folders.ts_job_id', 'seasons.code as year')
            ->orderBy('folders.portal_ts_foldername')
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
        ->where('jobs.ts_schoolkey', $schoolKey)
        ->whereNotNull('subjects.ts_subjectkey') // code by IT
        ->whereNotNull('folders.ts_folderkey'); // code by IT

        if($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('subjects.portal_firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.portal_lastname', 'like', "%$searchTerm%")
                    ->orWhere('folders.portal_ts_foldername', 'like', "%$searchTerm%");
            });
        }
        $query->whereIn('folders.ts_folderkey', $folderKeys);
        
        return $query->select('subjects.portal_firstname', 'subjects.portal_lastname', 'subjects.ts_subjectkey', 'folders.portal_ts_foldername');
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
        ->leftJoin('folder_subjects', function ($join) {
            $join->on('folder_subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                 ->where('folder_subjects.is_deleted', 0); // code by IT
        })
        ->join('subjects', function ($join) {
            $join->on('subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                 ->orOn('subjects.ts_subject_id', '=', 'folder_subjects.ts_subject_id');
        })
        ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
        ->where('jobs.ts_season_id', $seasonId)
        ->where('jobs.ts_schoolkey', $schoolKey)
        ->whereNotNull('subjects.ts_subjectkey') // code by IT
        ->whereNotNull('folders.ts_folderkey'); // code by IT
        
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
                $query->where('subjects.portal_firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.portal_lastname', 'like', "%$searchTerm%")
                    ->orWhere('folders.portal_ts_foldername', 'like', "%$searchTerm%");
            });
        }
        $query->whereIn('folders.ts_folderkey', $folderKeys);
        
        return $query
            ->select(
                'subjects.portal_firstname', 
                'subjects.portal_lastname', 
                'subjects.ts_subjectkey', 
                'seasons.code as year',
                'subjects.external_subject_id'
            )
            // ->distinct() //CODE BY Chromedia
            ->distinct('subjects.ts_subjectkey') //CODE BY IT
            ->orderBy('subjects.portal_lastname')
            ->orderBy('subjects.portal_firstname');
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
        ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
        ->where('jobs.ts_season_id', $seasonId)
        ->where('jobs.ts_schoolkey', $schoolKey)
        ->whereNotNull('folders.ts_folderkey') // code by IT
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
            $query->where('folders.portal_ts_foldername', 'like', "%$searchTerm%");
        }
        $query->whereIn('folders.ts_folderkey', $folderKeys);
        
        return $query
            ->select('folders.ts_folderkey', 'folders.portal_ts_foldername', 'seasons.code as year')
            ->distinct('folders.ts_folderkey') //CODE BY IT
            ->orderBy('folders.portal_ts_foldername');
    }

    /**
     * Get images from database using options given
     *
     * @param array $options
     * @param string $tab
     * @return Collection
     */
    // public function getFilteredPhotographyImages(array $options, string $tab = PhotographyHelper::TAB_PORTRAITS): Collection  //CODE BY Chromedia
    public function getFilteredPhotographyImages(array $options, string $tab = PhotographyHelper::TAB_PORTRAITS, $perPage = 30, $page = 1)  //CODE BY IT
    {
        $seasonId = $options['tsSeasonId'];
        $schoolKey = $options['schoolKey'];
        $folderKeys = $options['folderKeys'];
        $search = $options['searchTerm'] ?? '';

        switch ($tab) {
            case PhotographyHelper::TAB_GROUPS:
                $images = $this->getFoldersCollection($seasonId, $schoolKey, $folderKeys, $search); // code by IT
                break; // code by IT
            case PhotographyHelper::TAB_OTHERS:
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $images = $this->getSubjectsCollection($seasonId, $schoolKey, $folderKeys, $search);
                break;
        }

        // return $images->get();   //CODE BY CHROMEDIA
        return $images->paginate($perPage, ['*'], 'page', $page); // code by IT
    }


    /**
     * Get group/folder images from database using options given
     *
     * @param string $schoolKey
     * @param string $searchTerm
     * @return Collection
     */
    public function getGroupImages($schoolKey, $searchTerm): Collection
    {
        $query = DB::table(table: 'jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where('folders.is_visible_for_group', 1)
            ->whereNotNull('folders.ts_folderkey'); // code by IT

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
            $query->where('folders.portal_ts_foldername', 'like', "$searchTerm%");
        }

        return $query
            ->select('folders.ts_folderkey', 'folders.portal_ts_foldername', 'seasons.code as year')
            ->orderBy('folders.portal_ts_foldername')
            ->get();
    }

    /**
     * Get images from database using options given
     *
     * @param string $schoolKey
     * @param string $searchTerm
     * @param string $searchTerm2
     * @param string $subjectKey
     * @param string $externalSubjectId
     * @return Collection
     */
    public function getSubjectImages($schoolKey, $searchTerm, $searchTerm2, $subjectKey, $externalSubjectId = null): Collection
    {
        $query = DB::table(table: 'jobs')
        ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
        ->leftJoin('folder_subjects', function ($join) {
            $join->on('folder_subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                 ->where('folder_subjects.is_deleted', 0); // code by IT
        })
        ->join('subjects', function ($join) {
            $join->on('subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                 ->orOn('subjects.ts_subject_id', '=', 'folder_subjects.ts_subject_id');
        })
        ->where('jobs.ts_schoolkey', $schoolKey)
        ->where('folders.is_visible_for_portrait', 1)
        ->whereNotNull('subjects.ts_subjectkey') // code by IT
        ->whereNotNull('folders.ts_folderkey'); // code by IT
        
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
                $query->where('subjects.portal_firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.lastname', 'like', "%$searchTerm2%");
            });
        }

        if ($externalSubjectId) {
            $query->whereNotNull('subjects.external_subject_id')
                ->where('subjects.external_subject_id', $externalSubjectId);
        } else {
            $query->where('subjects.ts_subjectkey', $subjectKey);
        }
        
        return $query
            ->select('subjects.portal_firstname', 
                'subjects.portal_lastname', 
                'subjects.ts_subjectkey', 
                'seasons.code as year',
                'subjects.external_subject_id'
            )
            ->distinct()
            ->orderBy('year')
            ->orderBy('subjects.portal_lastname')
            ->orderBy('subjects.portal_firstname')
            ->orderBy('subjects.ts_subjectkey') // code by IT
            ->get();
    }

    /**
     * Get images from the local drive and return them as base64 strings.
     *
     * @param Collection
     * @return Collection
     */
    //CODE BY IT
    public function getImagesAsBase64($images, $tab = PhotographyHelper::TAB_PORTRAITS): Collection
    {
        switch ($tab) {
            case PhotographyHelper::TAB_GROUPS:
                $key = 'ts_folderkey'; // code by IT
                $category = 'FOLDER'; // code by IT
                break; // code by IT
            case PhotographyHelper::TAB_OTHERS:
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $key = 'ts_subjectkey';
                $category = 'SUBJECT';
                break;
        }

        $toData = function ($image) use ($key, $category, $tab) {
            $isSubject = $category != 'FOLDER';
            $imgKey = $image->$key;
            if ($isSubject) {
                $subject = Subject::where('ts_subjectkey', $image->$key)->first();
                if ($subject) {
                    $hasPhoto = $this->getIsImageFound($imgKey, $tab);
                    $uploadExists = SchoolPhotoUpload::where('subject_id', $subject->id)->whereNull('deleted_at')->exists();
                    $uploaded = $uploadExists && $hasPhoto;
                    $classGroup = FilenameFormatHelper::removeYearAndDelimiter($subject->folder->portal_ts_foldername, $image->year ?? null); //CODE BY IT
                } else {
                    $hasPhoto = $this->getIsImageFound($imgKey, $tab);
                    $uploaded = false;
                    $classGroup = FilenameFormatHelper::removeYearAndDelimiter($image->portal_ts_foldername, $image->year ?? null); //CODE BY IT
                }
            } else {
                $folder = Folder::where('ts_folderkey', $image->$key)->first();
                if ($folder) {
                    $hasPhoto = $this->getIsImageFound($imgKey, $tab);
                    $uploadExists = SchoolPhotoUpload::where('folder_id', $folder->id)->whereNull('deleted_at')->exists();
                    $uploaded = $uploadExists && $hasPhoto;
                } else {
                    $hasPhoto = $this->getIsImageFound($imgKey, $tab);
                    $uploaded = false;
                }
            }
            // Skip full image network download during initial grid compilation for massive speed boost
            $isPortrait = $isSubject; // Default portrait for subjects, landscape for folders

            return [
                'id' => base64_encode(base64_encode($image->$key)),
                'firstname' => $isSubject ? $image->portal_firstname : '',
                'lastname' => $isSubject ? $image->portal_lastname : '',
                'isPortrait' => $isPortrait,
                'classGroup' => $classGroup,
                'year' => $image->year ?? 0,
                'category' => $category,
                'isUploaded' => $uploaded,
                'hasPhoto' => $hasPhoto,
                'externalSubjectId' => $isSubject ? $image->external_subject_id : null,
            ];
        };

        return $images->map($toData);
    }

    public function getImageContent(string $key, $resolutionId = null, $tab = ''): ?string
    {
        $imageRecordExists = Image::where('keyvalue', $key)->exists();

        if (!$imageRecordExists) {
            return $this->getFallbackAbsentImage();
        }

        $urls = $this->getImageUrls($key, $resolutionId, $tab);
    
        foreach ($urls as $url) {
            if ($this->urlExists($url)) {
                $binary = @file_get_contents($url);
                if ($binary !== false) {
                    return base64_encode($binary);
                }
            }
        }
    
        return $this->getFallbackNotFoundImage();
    }
    
    private function getFallbackNotFoundImage(): ?string
    {
        $notFoundPath = ImageHelper::NOT_FOUND_IMG;
        if (Storage::disk('local')->exists($notFoundPath)) {
            $binary = Storage::disk('local')->get($notFoundPath);
            return base64_encode($binary);
        }

        return null;
    }
    
    private function getFallbackAbsentImage(): ?string
    {
        $notFoundPath = ImageHelper::ABSENT_IMG;
        if (Storage::disk('local')->exists($notFoundPath)) {
            $binary = Storage::disk('local')->get($notFoundPath);
            return base64_encode($binary);
        }

        return null;
    }
    
    /**
     * Check if at least one image exists for the key
     */
    public function getIsImageFound(string $key, $tab = ''): bool
    {
        if (!$key) {
            return false;
        }

        $upperTab = strtoupper((string)($tab ?? ''));
        $cacheKey = "photography_exists_{$key}_{$upperTab}";

        return Cache::remember($cacheKey, 600, function() use ($key, $upperTab) {
            $imageRecordExists = Image::where('keyvalue', $key)->exists();

            if (!$imageRecordExists) {
                return false;
            }

            $urls = $this->getImageUrls($key, null, $upperTab);

            foreach ($urls as $url) {
                if ($this->urlExists($url)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function getImageUrls(string $key, $resolutionId = null, $tab = ''): array
    {
        $upperTab = strtoupper((string)($tab ?? ''));
        $resKey = (string)($resolutionId ?? 'any');
        $cacheKey = "photography_urls_{$key}_{$resKey}_{$upperTab}";
        
        return Cache::remember($cacheKey, 600, function() use ($key, $resolutionId, $upperTab) {
            $baseImage = env('PORTRAITIMAGELOCATION')."{$key[0]}/{$key[1]}/{$key}";
            $baseGroup = env('GROUPIMAGELOCATION')."{$key[0]}/{$key[1]}/{$key}";

            // --- DEFINE PORTRAIT URLS ---
            if ($resolutionId == 1) { // High Quality
                $portraitUrls = ["{$baseImage}_1600.jpg"];
            } else { 
                // Default to 400 if resolutionId is 2 OR null
                $portraitUrls = ["{$baseImage}_400.jpg"];
            }

            // --- DEFINE GROUP URLS ---
            if ($resolutionId == 1) { // High Quality
                $groupUrls = ["{$baseGroup}_1600.jpg"];
            } else { 
                // Default to 400 if resolutionId is 2 OR null
                $groupUrls = ["{$baseGroup}_400.jpg"];
            }

            // --- FINAL FILTERING BASED ON TAB ---
            if ($upperTab === PhotographyHelper::TAB_PORTRAITS) {
                $result = $portraitUrls;
            } else if ($upperTab === PhotographyHelper::TAB_GROUPS) {
                $result = $groupUrls;
            } else {
                // If no specific tab, show the 400px versions of both
                $result = array_merge($portraitUrls, $groupUrls);
            }

            return $result;
        });
    }

    /**
     * Check if URL exists (HTTP 200)
     */
    private function urlExists(string $url): bool
    {
        // Use HEAD instead of GET to only fetch headers and prevent downloading mb image payloads
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 2
            ]
        ]);
        
        $headers = @get_headers($url, 0, $context);
        return $headers && strpos($headers[0], '200') !== false;
    }
    //CODE BY IT

    /**
     * Get File Content based on $key value
     * @param string $key
     * @return string|null
     */
    //CODE BY Chromedia
    // public function getImagesAsBase64($images, $tab = PhotographyHelper::TAB_PORTRAITS): Collection
    // {
    //     switch ($tab) {
    //         case PhotographyHelper::TAB_GROUPS:
    //         case PhotographyHelper::TAB_OTHERS:
    //             $key = 'ts_folderkey';
    //             $category = 'FOLDER';
    //             break;
    //         case PhotographyHelper::TAB_PORTRAITS:
    //         default:
    //             $key = 'ts_subjectkey';
    //             $category = 'SUBJECT';
    //             break;
    //     }

    //     $toData = function ($image) use ($key, $category) {
    //         $isSubject = $category != 'FOLDER';
    //         $imgKey = $image->$key;
    //         if ($isSubject) {
    //             $subject = Subject::where('ts_subjectkey', $image->$key)->first();
    //             if ($subject) {
    //                 $uploadExists = SchoolPhotoUpload::where('subject_id', $subject->id)->whereNull('deleted_at')->exists();
    //                 $uploaded = $uploadExists && $this->getIsImageFound($imgKey);
    //             } else {
    //                 $uploaded = false;
    //             }
    //         } else {
    //             $folder = Folder::where('ts_folderkey', $image->$key)->first();
    //             if ($folder) {
    //                 $uploadExists = SchoolPhotoUpload::where('folder_id', $folder->id)->whereNull('deleted_at')->exists();
    //                 $uploaded = $uploadExists && $this->getIsImageFound($imgKey);
    //             } else {
    //                 $uploaded = false;
    //             }
    //         }
    //         $fileContent = $this->getImageContent($imgKey);
            
    //         $dimensions = getimagesizefromstring($fileContent);

    //         if ($isSubject) {
    //             $subject = Subject::where('ts_subjectkey', $image->$key)->first();
    //             $classGroup = FilenameFormatHelper::removeYearAndDelimiter($subject->folder->ts_foldername, $image->year ?? null);
    //         } else {
    //             $classGroup = FilenameFormatHelper::removeYearAndDelimiter($image->ts_foldername, $image->year ?? null);
    //         }

    //         return [
    //             'id' => base64_encode(base64_encode($image->$key)),
    //             'firstname' => $isSubject ? $image->firstname : '',
    //             'lastname' => $isSubject ? $image->lastname : '',
    //             'isPortrait' => $dimensions[0] <= $dimensions[1],
    //             'classGroup' => $classGroup,
    //             'year' => $image->year ?? 0,
    //             'category' => $category,
    //             'isUploaded' => $uploaded,
    //             'externalSubjectId' => $isSubject ? $image->external_subject_id : null,
    //         ];
    //     };

    //     return $images->map($toData);
    // }

    // /**
    //  * Get File Content based on $key value
    //  * @param string $key
    //  * @return string|null
    //  */
    // public function getImageContent($key)
    // {
    //     $path = ImageHelper::getImagePath($key);
    //     $fileContent = Storage::disk('local')->get(empty($path) ? ImageHelper::NOT_FOUND_IMG : $path);
    //     return $fileContent;
    // }

    // /**
    //  * Check if image is found based on $key value
    //  * @param string $key
    //  * @return boolean
    //  */
    // public function getIsImageFound($key)
    // {
    //     $path = ImageHelper::getImagePath($key);
    //     if ($path === '') {
    //         return false;
    //     }
    //     return Storage::disk('local')->exists($path);
    // }
    //CODE BY Chromedia
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