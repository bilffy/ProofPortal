<?php

namespace App\Services;

use App\Helpers\FilenameFormatHelper;
use App\Helpers\ImageHelper;
use App\Helpers\PhotographyHelper;
use App\Helpers\PhotographyJobQueryHelper;
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
     * Scope job queries by portal school_id and Timestone account (franchise).
     * schoolkey alone is not unique across schools in a franchise.
     */
    protected function applySchoolJobScope($query, ?int $schoolId, ?int $tsAccountId = null)
    {
        if ($schoolId !== null) {
            $query->where('jobs.school_id', $schoolId);
        }

        if ($tsAccountId !== null) {
            $query->where('jobs.ts_account_id', $tsAccountId);
        }

        return $query;
    }

    /**
     * Get all the years.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllYears()
    {
        return DB::table('seasons')
            ->select('id', 'ts_season_id', 'code as Year')
            ->where('show_in_portal', 1) // code by IT
            ->orderBy('code', direction: 'desc')
            ->get();
    }

    /**
     * Get all the years.
     *
     * @param string $schoolId
     * @param string $tab
     * @param int|null $tsAccountId
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableYearsForSchool($schoolId, $tab = '', ?int $tsAccountId = null)
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
            ->whereNotNull('folders.ts_folderkey') // code by IT
            ->where('folders.is_deleted', 0)
            ->where(function ($q) use ($visibilityColumn) {
                if (empty($visibilityColumn)) {
                    $q->where('folders.is_visible_for_group', 1)
                        ->orWhere('folders.is_visible_for_portrait', 1);
                } else {
                    $q->where("folders.$visibilityColumn", 1);
                }
            })
            ->where('seasons.show_in_portal', 1); // code by IT

        $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

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
    public function getFolderForView(int $seasonId, int $schoolId, string $operator, string $folderTag)
    {
        return DB::table('schools')
            ->join('jobs', 'jobs.school_id', '=', 'schools.id')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->leftJoin('folder_tags', 'folder_tags.tag', '=', 'folders.folder_tag')
            ->where('jobs.ts_season_id', $seasonId)
            ->where('jobs.school_id', $schoolId)
            ->whereNotNull('folders.ts_folderkey') // code by IT
            ->where('folders.is_deleted', 0)
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
     * @param string $schoolId
     * @param string $tab
     * @return \Illuminate\Support\Collection
     */
    public function getFolderForView2(int $seasonId, ?int $schoolId, string $tab, ?int $tsAccountId = null)
    {

        $query = DB::table('jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->leftJoin('folder_tags', 'folder_tags.tag', '=', 'folders.folder_tag')
            ->where('jobs.ts_season_id', $seasonId)
            ->whereNotNull('folders.ts_folderkey') // code by IT
            ->where('folders.is_deleted', 0);

        $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

        if ($tab === PhotographyHelper::TAB_PORTRAITS) {
            PhotographyJobQueryHelper::applyDownloadAvailableJobFilter($query);
        }

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

                if (auth()->check() && auth()->user()->isSchoolLevel()) {
                    $query->where(function ($q) {
                        $q->where('folder_tags.external_name', '!=', 'Family')
                          ->orWhereNull('folder_tags.external_name');
                    });
                }
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
     * @param string|null $schoolId
     * @param array $selectedTags
     * @param string $tab
     * @return \Illuminate\Support\Collection
     */
    public function getFoldersByTag(int $seasonId, ?int $schoolId, array $selectedTags, string $tab, ?int $tsAccountId = null)
    {
        $folderTagsQuery = DB::table('folder_tags')
            ->whereIn('external_name', $selectedTags);

        if (auth()->check() && auth()->user()->isSchoolLevel()) {
            $folderTagsQuery->where('external_name', '!=', 'Family');
        }

        $folderTags = $folderTagsQuery->select('tag')
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

        $query = DB::table('jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->where('jobs.ts_season_id', $seasonId)
            ->where("folders.$visibilityColumn", 1)
            ->whereNotNull('folders.ts_folderkey') // code by IT
            ->where('folders.is_deleted', 0);

            $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

            if ($tab === PhotographyHelper::TAB_PORTRAITS) {
                PhotographyJobQueryHelper::applyDownloadAvailableJobFilter($query);
            }

            $query->where(function ($query) use ($folderTags, $selectedTags, $nullTag) {
                $query->whereIn('folders.folder_tag', $folderTags);
                if (in_array($nullTag, $selectedTags)) {
                    $query->orWhereNull('folders.folder_tag');
                }
            });

        return $query
            ->select('folders.portal_ts_foldername', 'folders.ts_folderkey', 'folders.ts_job_id', 'seasons.code as year')
            ->distinct()
            ->orderBy('folders.portal_ts_foldername')
            ->get();
    }

    /**
     * Get all the images and subjects of the selected folder.
     *
     * @param int $seasonId
     * @param string $schoolId
     * @param array $folderKeys
     * @param string $searchTerm
     * @return \Illuminate\Database\Query\Builder
     */
    public function getImagesAndSubjectsByFolder(int $seasonId, ?int $schoolId, array $folderKeys, string $searchTerm, ?int $tsAccountId = null)
    {
        $query = DB::table(table: 'jobs')
        ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('subjects', 'subjects.ts_folder_id', '=', 'folders.ts_folder_id')
        ->where('jobs.ts_season_id', $seasonId)
        ->whereNotNull('subjects.ts_subjectkey') // code by IT
        ->whereNotNull('folders.ts_folderkey') // code by IT
        ->where('folders.is_deleted', 0)
        ->where('subjects.is_deleted', 0);

        $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

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
     * @param string $schoolId
     * @param array $folderKeys
     * @param string $searchTerm
     * @return \Illuminate\Database\Query\Builder
     */

    private function applySubjectsCollectionFilters($query, int $seasonId, ?int $schoolId, array $folderKeys, string $searchTerm, ?int $tsAccountId = null)
    {
        $query->where('jobs.ts_season_id', $seasonId)
            ->whereNotNull('subjects.ts_subjectkey')
            ->whereNotNull('folders.ts_folderkey')
            ->where('folders.is_deleted', 0)
            ->where('subjects.is_deleted', 0)
            ->where('folders.is_visible_for_portrait', 1)
            ->whereColumn('subjects.ts_job_id', 'jobs.ts_job_id')
            ->whereIn('folders.ts_folderkey', $folderKeys)
            // Keep subjects even when their image row is soft-deleted (is_deleted = 1);
            // the grid/serve path shows getFallbackNotFoundImage for those.
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('jobs.portrait_download_date')
                        ->whereNotNull('jobs.download_available_date')
                        ->where('jobs.download_available_date', '<=', now());
                })->orWhere(function ($subQuery) {
                    $subQuery->whereNotNull('jobs.portrait_download_date')
                        ->whereNotNull('jobs.download_available_date')
                        ->whereRaw('GREATEST(jobs.portrait_download_date, jobs.download_available_date) <= ?', [now()]);
                });
            });

        $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

        if ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('subjects.portal_firstname', 'like', "%$searchTerm%")
                    ->orWhere('subjects.portal_lastname', 'like', "%$searchTerm%")
                    ->orWhere('folders.portal_ts_foldername', 'like', "%$searchTerm%");
            });
        }

        return $query;
    }

    public function getSubjectsCollection(int $seasonId, ?int $schoolId, array $folderKeys, string $searchTerm, ?int $tsAccountId = null)
{
    $selectColumns = [
        'subjects.portal_firstname',
        'subjects.portal_lastname',
        'subjects.ts_subjectkey',
        'subjects.ts_subject_id',
        'folders.portal_ts_foldername',
        'seasons.code as year',
        'subjects.external_subject_id',
    ];

    // Query 1: Homed Subjects
    $homed = $this->applySubjectsCollectionFilters(
        DB::table('jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('subjects', 'subjects.ts_folder_id', '=', 'folders.ts_folder_id')
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->select($selectColumns)
            ->distinct(),
        $seasonId, $schoolId, $folderKeys, $searchTerm, $tsAccountId
    );

    // Query 2: Attached Subjects
    // Note: Removed the 'orOn' join to maintain performance
    $attached = $this->applySubjectsCollectionFilters(
        DB::table('jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('folder_subjects', function ($join) {
                $join->on('folder_subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                     ->where('folder_subjects.is_deleted', 0);
            })
            ->join('subjects', 'subjects.ts_subject_id', '=', 'folder_subjects.ts_subject_id')
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->select($selectColumns)
            ->distinct(),
        $seasonId, $schoolId, $folderKeys, $searchTerm, $tsAccountId
    );

    // One card per ts_subject_id (homed + attached duplicates collapse).
    // Do not collapse by name or external_subject_id:
    // - null portal_firstname made many people share one name-key
    // - two people with the same name must stay as two cards
    return DB::query()
        ->fromSub($homed->union($attached), 'portrait_subjects')
        ->selectRaw('
            MIN(portal_firstname) as portal_firstname,
            MIN(portal_lastname) as portal_lastname,
            MIN(ts_subjectkey) as ts_subjectkey,
            MIN(portal_ts_foldername) as portal_ts_foldername,
            MIN(year) as year,
            MIN(external_subject_id) as external_subject_id,
            ts_subject_id
        ')
        ->groupBy('ts_subject_id')
        ->orderBy('portal_lastname')
        ->orderBy('portal_firstname');
}

    /**
     * Count of distinct subjects (matched either by homed folder or folder_subjects
     * attachment) that also have a matching row in `images`. Built as its own
     * homed+attached union - with the images join baked into each half before the
     * union - rather than joining onto getSubjectsCollection()'s result, since a
     * join appended after ->union() only applies to the first half.
     */
    public function getSubjectsWithImagesCount(int $seasonId, ?int $schoolId, array $folderKeys, string $searchTerm, ?int $tsAccountId = null): int
    {
        $imagesJoin = function ($join) {
            $join->on('images.ts_job_id', '=', 'jobs.ts_job_id')
                ->on('images.keyvalue', '=', 'subjects.ts_subjectkey')
                ->where(function ($q) {
                    $q->where('images.is_deleted', 0)
                        ->orWhereNull('images.is_deleted');
                });
        };

        $selectColumns = [
            'subjects.ts_subjectkey',
            'subjects.ts_subject_id',
            'subjects.external_subject_id',
            'subjects.portal_firstname',
            'subjects.portal_lastname',
        ];

        $homed = $this->applySubjectsCollectionFilters(
            DB::table('jobs')
                ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
                ->join('subjects', 'subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
                ->join('images', $imagesJoin)
                ->select($selectColumns)
                ->distinct(),
            $seasonId, $schoolId, $folderKeys, $searchTerm, $tsAccountId
        );

        $attached = $this->applySubjectsCollectionFilters(
            DB::table('jobs')
                ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
                ->join('folder_subjects', function ($join) {
                    $join->on('folder_subjects.ts_folder_id', '=', 'folders.ts_folder_id')
                        ->where('folder_subjects.is_deleted', 0);
                })
                ->join('subjects', 'subjects.ts_subject_id', '=', 'folder_subjects.ts_subject_id')
                ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
                ->join('images', $imagesJoin)
                ->select($selectColumns)
                ->distinct(),
            $seasonId, $schoolId, $folderKeys, $searchTerm, $tsAccountId
        );

        return (int) (DB::query()
            ->fromSub($homed->union($attached), 'portrait_subjects_with_images')
            ->selectRaw('COUNT(DISTINCT ts_subject_id) as aggregate')
            ->value('aggregate') ?? 0);
    }
/*
    public function getSubjectsCollection(int $seasonId, string $schoolId, array $folderKeys, string $searchTerm)
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
        ->where('jobs.ts_schoolkey', $schoolId)
        ->whereNotNull('subjects.ts_subjectkey') // code by IT
        ->whereNotNull('folders.ts_folderkey') // code by IT
        ->where('folders.is_deleted', 0)
        ->where('subjects.is_deleted', 0);

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
    }*/

    /**
     * Get all the folders based on query filters.
     *
     * @param int $seasonId
     * @param string $schoolId
     * @param array $folderKeys
     * @param string $searchTerm
     * @return \Illuminate\Database\Query\Builder
     */
    public function getFoldersCollection(int $seasonId, ?int $schoolId, array $folderKeys, string $searchTerm, ?int $tsAccountId = null)
    {
        $query = DB::table(table: 'jobs')
        // $query = DB::table(table: 'images')
        // ->join('jobs', 'jobs.ts_job_id', '=', 'images.ts_job_id')
        ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
        ->where('jobs.ts_season_id', $seasonId)
        ->whereNotNull('folders.ts_folderkey') // code by IT
        ->where('folders.is_deleted', 0)
        // ->where('images.keyorigin', 'Folder')
        ->whereNotExists(function ($subQuery) {
            $subQuery->select(DB::raw(1))
                ->from('images')
                ->whereColumn('images.keyvalue', 'folders.ts_folderkey')
                ->where('images.is_deleted', 1);
        });

        $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

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
        $schoolId = $options['schoolId'] ?? null;
        $folderKeys = $options['folderKeys'];
        $search = $options['searchTerm'] ?? '';
        $tsAccountId = isset($options['tsAccountId']) ? (int) $options['tsAccountId'] : null;

        switch ($tab) {
            case PhotographyHelper::TAB_GROUPS:
                $images = $this->getFoldersCollection($seasonId, $schoolId, $folderKeys, $search, $tsAccountId); // code by IT
                break; // code by IT
            case PhotographyHelper::TAB_OTHERS:
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $images = $this->getSubjectsCollection($seasonId, $schoolId, $folderKeys, $search, $tsAccountId);
                break;
        }

        // return $images->get();   //CODE BY CHROMEDIA
        return $images->paginate($perPage, ['*'], 'page', $page); // code by IT
    }


    /**
     * Get group/folder images from database using options given
     *
     * @param string $schoolId
     * @param string $searchTerm
     * @return Collection
     */
    public function getGroupImages(?int $schoolId, $searchTerm, ?int $tsAccountId = null): Collection
    {
        $query = DB::table(table: 'jobs')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->where('folders.is_visible_for_group', 1)
            ->whereNotNull('folders.ts_folderkey') // code by IT
            ->where('folders.is_deleted', 0)
            ->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('images')
                    ->whereColumn('images.keyvalue', 'folders.ts_folderkey')
                    ->where('images.is_deleted', 1);
            });

        $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

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
     * @param string $schoolId
     * @param string $searchTerm
     * @param string $searchTerm2
     * @param string $subjectKey
     * @param string $externalSubjectId
     * @return Collection
     */
    public function getSubjectImages(?int $schoolId, $searchTerm, $searchTerm2, $subjectKey, $externalSubjectId = null, ?int $tsAccountId = null): Collection
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
        ->where('folders.is_visible_for_portrait', 1)
        ->whereNotNull('subjects.ts_subjectkey') // code by IT
        ->whereNotNull('folders.ts_folderkey') // code by IT
        ->where('folders.is_deleted', 0)
        ->where('subjects.is_deleted', 0);
        // Subjects with soft-deleted images still appear; serve uses not-found fallback.

        $this->applySchoolJobScope($query, $schoolId, $tsAccountId);

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
                'folders.portal_ts_foldername',
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

        $isSubject = $category !== 'FOLDER';
        $lookupKeys = $images->pluck($key)->filter()->unique()->values()->all();

        $subjectsByKey = collect();
        $foldersByKey = collect();
        $uploadsBySubjectId = collect();
        $uploadsByFolderId = collect();
        $existingImageKeys = collect();

        if ($lookupKeys !== []) {
            $existingImageKeys = Image::query()
                ->whereIn('keyvalue', $lookupKeys)
                ->notDeleted()
                ->pluck('keyvalue')
                ->flip();
        }

        if ($isSubject && $lookupKeys !== []) {
            $subjectsByKey = Subject::query()
                ->whereIn('ts_subjectkey', $lookupKeys)
                ->where('is_deleted', 0)
                ->with('folder')
                ->get()
                ->keyBy('ts_subjectkey');

            $subjectIds = $subjectsByKey->pluck('id')->filter()->values();
            if ($subjectIds->isNotEmpty()) {
                $uploadsBySubjectId = SchoolPhotoUpload::query()
                    ->whereIn('subject_id', $subjectIds)
                    ->whereNull('deleted_at')
                    ->pluck('subject_id')
                    ->flip();
            }
        } elseif (!$isSubject && $lookupKeys !== []) {
            $foldersByKey = Folder::query()
                ->whereIn('ts_folderkey', $lookupKeys)
                ->where('is_deleted', 0)
                ->get()
                ->keyBy('ts_folderkey');

            $folderIds = $foldersByKey->pluck('id')->filter()->values();
            if ($folderIds->isNotEmpty()) {
                $uploadsByFolderId = SchoolPhotoUpload::query()
                    ->whereIn('folder_id', $folderIds)
                    ->whereNull('deleted_at')
                    ->pluck('folder_id')
                    ->flip();
            }
        }

        $toData = function ($image) use ($key, $category, $tab, $isSubject, $subjectsByKey, $foldersByKey, $uploadsBySubjectId, $uploadsByFolderId, $existingImageKeys) {
            $imgKey = $image->$key;
            // Trust images table for grid flags; actual file fetch corrects checkbox via X-Photography-Source.
            $hasPhoto = $imgKey && isset($existingImageKeys[$imgKey]);
            $classGroup = '';
            if ($isSubject) {
                $subject = $subjectsByKey->get($image->$key);
                if ($subject) {
                    $uploadExists = isset($uploadsBySubjectId[$subject->id]);
                    $uploaded = $uploadExists && $hasPhoto;
                    $folderName = $subject->folder?->portal_ts_foldername
                        ?? $image->portal_ts_foldername
                        ?? $image->ts_foldername
                        ?? '';
                } else {
                    $uploaded = false;
                    $folderName = $image->portal_ts_foldername
                        ?? $image->ts_foldername
                        ?? '';
                }
                $classGroup = FilenameFormatHelper::removeYearAndDelimiter($folderName, $image->year ?? null);
            } else {
                $folder = $foldersByKey->get($image->$key);
                if ($folder) {
                    $uploadExists = isset($uploadsByFolderId[$folder->id]);
                    $uploaded = $uploadExists && $hasPhoto;
                    $folderName = $folder->portal_ts_foldername ?? $image->portal_ts_foldername ?? '';
                } else {
                    $uploaded = false;
                    $folderName = $image->portal_ts_foldername ?? $image->ts_foldername ?? '';
                }
                $classGroup = FilenameFormatHelper::removeYearAndDelimiter($folderName, $image->year ?? null);
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
        return $this->getImageServeResult($key, $resolutionId, $tab)['content'];
    }

    /**
     * Resolve image bytes and how they were resolved (for AJAX serve + checkbox UI).
     *
     * @return array{content: ?string, source: 'file'|'absent'|'not-found'}
     */
    public function getImageServeResult(string $key, $resolutionId = null, $tab = ''): array
    {
        $imageRecordExists = Image::where('keyvalue', $key)->notDeleted()->exists();

        if (!$imageRecordExists) {
            // Soft-deleted image row(s) still mean "photo was known" → not-found placeholder.
            // No image row at all → absent placeholder.
            $hasDeletedImage = Image::where('keyvalue', $key)->where('is_deleted', 1)->exists();

            return [
                'content' => $hasDeletedImage
                    ? $this->getFallbackNotFoundImage()
                    : $this->getFallbackAbsentImage(),
                'source' => $hasDeletedImage ? 'not-found' : 'absent',
            ];
        }

        $urls = $this->getImageUrls($key, $resolutionId, $tab);

        // Single GET per candidate URL — avoid separate HEAD/Range existence probes.
        foreach ($urls as $url) {
            $binary = $this->fetchImageBinary($url);
            if ($binary !== null) {
                return [
                    'content' => base64_encode($binary),
                    'source' => 'file',
                ];
            }
        }

        return [
            'content' => $this->getFallbackNotFoundImage(),
            'source' => 'not-found',
        ];
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
     * Check if at least one image exists for the key.
     * Uses the images table only — no remote HEAD/GET probes (those happen on serve).
     */
    public function getIsImageFound(string $key, $tab = ''): bool
    {
        if (!$key) {
            return false;
        }

        $upperTab = strtoupper((string)($tab ?? ''));
        $cacheKey = "photography_exists_{$key}_{$upperTab}";

        return Cache::remember($cacheKey, 600, function () use ($key) {
            return Image::where('keyvalue', $key)->notDeleted()->exists();
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
     * Fetch image bytes from a remote URL once. Returns null on failure.
     */
    private function fetchImageBinary(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);

        $binary = @file_get_contents($url, false, $context);
        if ($binary === false || $binary === '') {
            return null;
        }

        if (isset($http_response_header[0]) && !preg_match('/\s(200|206)\s/', $http_response_header[0])) {
            return null;
        }

        return $binary;
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
