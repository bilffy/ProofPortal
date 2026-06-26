<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\ReportRepository;

/**
 * Report Model
 *
 * The reports.query column stores the SSRS report name/path used for downloads.
 * ReportRepository methods (via countResults()) are used only for portal preview counts.
 */
class Report extends Model
{
    use HasFactory;
    
    protected $table = "reports";
    protected $fillable = ['id', 'name', 'description', 'query', 'params', 'is_deleted'];

    /**
     * Get the repository instance
     * 
     * @return ReportRepository
     */
    protected static function repository(): ReportRepository
    {
        return app(ReportRepository::class);
    }

    // ========================================================================
    // BACKWARD COMPATIBILITY METHODS
    // These static methods delegate to the repository while maintaining
    // the same interface expected by ReportController
    // ========================================================================

    /**
     * Fetch Jobs (Schools) IDs by franchiseAccountID and syncStatus
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function mySchoolsIds($tsJobId = null)
    {
        return self::repository()->getSchoolsIds($tsJobId);
    }

    /**
     * Fetch Folders IDs by franchiseAccountID and syncStatus
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function myFoldersIds($tsJobId = null)
    {
        return self::repository()->getFoldersIds($tsJobId);
    }

    /**
     * Fetch Folders IDs by school (job)
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function myFoldersIdsBySchool($tsJobId = null)
    {
        return self::repository()->getFoldersIdsBySchool($tsJobId);
    }

    /**
     * Get franchises accessible by the current user
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function myFranchises()
    {
        return self::repository()->getFranchises();
    }

    /**
     * Get schools (jobs) accessible by the current user
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function mySchools()
    {
        return self::repository()->getSchools();
    }

    /**
     * Get folders accessible by the current user
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function myFolders()
    {
        return self::repository()->getFolders();
    }

    /**
     * Get folders by school
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function myFoldersBySchool($tsJobId = null)
    {
        return self::repository()->getFoldersBySchool($tsJobId);
    }

    /**
     * Get subjects accessible by the current user
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function mySubjects()
    {
        return self::repository()->getSubjects();
    }

    /**
     * Get subjects by school
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function mySubjectsBySchool($tsJobId = null)
    {
        return self::repository()->getSubjectsBySchool($tsJobId);
    }

    /**
     * Get subjects by folder
     * 
     * @param int|null $tsJobId Note: This is actually a folder ID for backward compatibility
     * @return \Illuminate\Support\Collection
     */
    public static function mySubjectsByFolder($tsJobId = null)
    {
        return self::repository()->getSubjectsByFolder($tsJobId);
    }

    /**
     * Get subjects by school and folder
     * 
     * @param int|null $tsJobId
     * @param int|null $tsFolderId
     * @return \Illuminate\Support\Collection
     */
    public static function mySubjectsBySchoolAndFolder($tsJobId = null, $tsFolderId = null)
    {
        return self::repository()->getSubjectsBySchoolAndFolder($tsJobId, $tsFolderId);
    }

    /**
     * Get photo coordinators accessible by the current user
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function myPhotocoordinators()
    {
        return self::repository()->getPhotoCoordinators();
    }

    /**
     * Get teachers accessible by the current user
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function myTeachers()
    {
        return self::repository()->getTeachers();
    }

    /**
     * Get folder changes by school
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function myFolderChangesBySchool($tsJobId = null)
    {
        return self::repository()->getFolderChangesBySchool($tsJobId);
    }

    /**
     * Get folder changes by school and folder
     * 
     * @param int|null $tsJobId
     * @param int|null $tsFolderId
     * @return \Illuminate\Support\Collection
     */
    public static function myFolderChangesBySchoolAndFolder($tsJobId = null, $tsFolderId = null)
    {
        return self::repository()->getFolderChangesBySchoolAndFolder($tsJobId, $tsFolderId);
    }

    /**
     * Get subject changes by school
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function mySubjectChangesBySchool($tsJobId = null)
    {
        return self::repository()->getSubjectChangesBySchool($tsJobId);
    }

    /**
     * Get subject changes by school and folder
     * 
     * @param int|null $tsJobId
     * @param int|null $tsFolderId
     * @return \Illuminate\Support\Collection
     */
    public static function mySubjectChangesBySchoolAndFolder($tsJobId = null, $tsFolderId = null)
    {
        return self::repository()->getSubjectChangesBySchoolAndFolder($tsJobId, $tsFolderId);
    }

    /**
     * Get subject changes by school for Timestone import
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function mySubjectChangesBySchoolForTimestoneImport($tsJobId = null)
    {
        return self::repository()->getSubjectChangesBySchoolForTimestoneImport($tsJobId);
    }

    /**
     * Get group photo positions by school for TNJ importing
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function myGroupPhotoPositionsBySchoolForTnjImporting($tsJobId = null)
    {
        return self::repository()->getGroupPhotoPositionsBySchoolForTnjImporting($tsJobId);
    }

    /**
     * Get group photo positions by folder for TNJ importing
     * 
     * @param int|null $tsJobId Note: This is actually a folder ID for backward compatibility
     * @return \Illuminate\Support\Collection
     */
    public static function myGroupPhotoPositionsByFolderForTnjImporting($tsJobId = null)
    {
        return self::repository()->getGroupPhotoPositionsByFolderForTnjImporting($tsJobId);
    }

    /**
     * Get group photo positions by school and folder for TNJ importing
     * 
     * @param int|null $tsJobId
     * @param int|null $tsFolderId
     * @return \Illuminate\Support\Collection
     */
    public static function myGroupPhotoPositionsBySchoolAndFolder($tsJobId = null, $tsFolderId = null)
    {
        return self::repository()->getGroupPhotoPositionsBySchoolAndFolder($tsJobId, $tsFolderId);
    }

    /**
     * Get blueprint full change list
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function blueprintFullChangeList($tsJobId = null)
    {
        return self::repository()->getBlueprintFullChangeList($tsJobId);
    }

    // ========================================================================
    // MAGIC METHODS
    // ========================================================================

    /**
     * Handle dynamic static method calls into the model.
     * Maps invalid PHP method names required for SSRS endpoints to valid PHP methods.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    /**
     * Maps the reports.query value (SSRS report path/name) to the portal count method.
     */
    protected static function ssrsQueryToCountMethodMap(): array
    {
        return [
            'MyFolderChangesBySchool' => 'myFolderChangesBySchool',
            'MyFolderChangesBySchoolAndFolder' => 'myFolderChangesBySchoolAndFolder',
            'MySubjectChangesBySchool' => 'mySubjectChangesBySchool',
            'MySubjectChangesBySchoolAndFolder' => 'mySubjectChangesBySchoolAndFolder',
            'MySubjectChangesBySchoolForTimestoneImport' => 'mySubjectChangesBySchoolForTimestoneImport',
            'MyGroupPhotoPositionsBySchoolAndFolder' => 'myGroupPhotoPositionsBySchoolAndFolder',
            'BlueprintFullChangeList' => 'blueprintFullChangeList',
        ];
    }

    /**
     * Resolve the portal repository method used to count preview results.
     */
    public static function resolveCountMethod(string $queryName): ?string
    {
        $map = self::ssrsQueryToCountMethodMap();

        if (isset($map[$queryName])) {
            return $map[$queryName];
        }

        if (method_exists(static::class, $queryName)) {
            return $queryName;
        }

        $camelCase = lcfirst($queryName);
        if (method_exists(static::class, $camelCase)) {
            return $camelCase;
        }

        return null;
    }

    /**
     * Count portal preview results. SSRS report downloads use reports.query directly.
     */
    public static function countResults(string $queryName, array $parameters = []): int
    {
        $method = self::resolveCountMethod($queryName);

        if ($method === null) {
            return 0;
        }

        return self::repository()->countForReport($method, $parameters);
    }

    /**
     * @deprecated Use countResults() for preview counts. Downloads are served from SSRS via reports.query.
     */
    public static function runQuery(string $queryName, array $parameters = [])
    {
        $method = self::resolveCountMethod($queryName);

        if ($method === null) {
            return collect();
        }

        return call_user_func_array([static::class, $method], $parameters);
    }

    // ========================================================================
    // ELOQUENT RELATIONSHIPS
    // ========================================================================

    /**
     * Get the report roles associated with this report
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function report_roles()
    {
        return $this->hasMany('App\Models\ReportRole', 'report_id', 'id');
    }
}
