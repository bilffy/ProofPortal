<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\ReportRepository;

/**
 * Report Model
 * 
 * This model now delegates all complex SQL queries to the ReportRepository.
 * Static methods are maintained for backward compatibility with existing code
 * (particularly ReportController which uses reflection to call these methods).
 */
class Report extends Model
{
    use HasFactory;
    
    protected $table = "reports";
    protected $fillable = ['id', 'name', 'description', 'query', 'params'];

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
    public static function myGroupPhotoPositionsBySchoolAndFolderForTnjImporting($tsJobId = null, $tsFolderId = null)
    {
        return self::repository()->getGroupPhotoPositionsBySchoolAndFolderForTnjImporting($tsJobId, $tsFolderId);
    }

    /**
     * Get blueprint full change list
     * 
     * @param int|null $tsJobId
     * @return \Illuminate\Support\Collection
     */
    public static function blueprintFullChangeList($tsJobId = null)
    {
        return self::mySchools();
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
