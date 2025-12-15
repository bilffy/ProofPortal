<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolFolder extends Model
{
    use HasFactory;

    protected $table = 'school_folders';

    protected $fillable = [
        'season',
        'name',
        'parent_id',
        'school_id',
        'user_id',
        'position',
    ];

    protected $casts = [
        'season' => 'integer',
        'position' => 'integer',
    ];

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SchoolFolder::class, 'parent_id');
    }

    /**
     * Get all child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(SchoolFolder::class, 'parent_id')->orderBy('position');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the school this folder belongs to
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who created this folder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get root folders (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('position');
    }

    /**
     * Scope to filter by season
     */
    public function scopeBySeason($query, $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Check if this folder is a root folder
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if this folder has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get all ancestors (parent chain)
     */
    public function ancestors()
    {
        $ancestors = collect();
        $folder = $this->parent;

        while ($folder) {
            $ancestors->push($folder);
            $folder = $folder->parent;
        }

        return $ancestors;
    }

    /**
     * Get the full path of the folder
     */
    public function getPath(): string
    {
        $path = collect([$this->name]);
        $folder = $this->parent;

        while ($folder) {
            $path->prepend($folder->name);
            $folder = $folder->parent;
        }

        return $path->implode(' / ');
    }
}