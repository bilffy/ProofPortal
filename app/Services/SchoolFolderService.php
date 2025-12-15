<?php

namespace App\Services;

use App\Models\SchoolFolder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SchoolFolderService
{
    /**
     * Get all root folders for a school
     */
    public function getRootFolders(int $schoolId, ?int $season = null): Collection
    {
        $query = SchoolFolder::roots()->where('school_id', $schoolId);

        if ($season) {
            $query->bySeason($season);
        }

        return $query->with('user')->get();
    }

    /**
     * Get folder with its children
     */
    public function getFolderWithChildren(int $folderId): ?SchoolFolder
    {
        return SchoolFolder::with(['children', 'user', 'school'])->find($folderId);
    }

    /**
     * Get folder with all descendants
     */
    public function getFolderWithDescendants(int $folderId): ?SchoolFolder
    {
        return SchoolFolder::with(['descendants', 'user', 'school'])->find($folderId);
    }

    /**
     * Create a new folder
     */
    public function create(array $data): SchoolFolder
    {
        // If no position is provided, set it to the end
        if (!isset($data['position'])) {
            $data['position'] = $this->getNextPosition($data['school_id'], $data['parent_id'] ?? null);
        }

        return SchoolFolder::create($data);
    }

    /**
     * Update an existing folder
     */
    public function update(int $folderId, array $data): SchoolFolder
    {
        $folder = SchoolFolder::findOrFail($folderId);

        // Prevent setting parent to itself or its descendants
        if (isset($data['parent_id']) && $data['parent_id']) {
            $this->validateParent($folderId, $data['parent_id']);
        }

        $folder->update($data);

        return $folder->fresh();
    }

    /**
     * Delete a folder and optionally its children
     */
    public function delete(int $folderId, bool $deleteChildren = true): bool
    {
        $folder = SchoolFolder::findOrFail($folderId);

        if ($deleteChildren) {
            // Cascade delete will handle children due to foreign key constraint
            return $folder->delete();
        } else {
            // Move children to parent before deleting
            DB::transaction(function () use ($folder) {
                SchoolFolder::where('parent_id', $folder->id)
                    ->update(['parent_id' => $folder->parent_id]);

                $folder->delete();
            });

            return true;
        }
    }

    /**
     * Move folder to a new parent
     */
    public function moveFolder(int $folderId, ?int $newParentId, ?int $position = null): SchoolFolder
    {
        $folder = SchoolFolder::findOrFail($folderId);

        // Validate the new parent
        if ($newParentId) {
            $this->validateParent($folderId, $newParentId);
        }

        DB::transaction(function () use ($folder, $newParentId, $position) {
            $folder->parent_id = $newParentId;

            if ($position !== null) {
                $folder->position = $position;
            } else {
                $folder->position = $this->getNextPosition($folder->school_id, $newParentId);
            }

            $folder->save();
        });

        return $folder->fresh();
    }

    /**
     * Reorder folders by updating their positions
     */
    public function reorder(array $folderPositions): void
    {
        DB::transaction(function () use ($folderPositions) {
            foreach ($folderPositions as $folderId => $position) {
                SchoolFolder::where('id', $folderId)->update(['position' => $position]);
            }
        });
    }

    /**
     * Get folder tree structure (hierarchical)
     */
    public function getFolderTree(int $schoolId, ?int $season = null): Collection
    {
        $query = SchoolFolder::roots()
            ->where('school_id', $schoolId)
            ->with(['descendants.user']);

        if ($season) {
            $query->bySeason($season);
        }

        return $query->get();
    }

    /**
     * Search folders by name
     */
    public function search(int $schoolId, string $searchTerm, ?int $season = null): Collection
    {
        $query = SchoolFolder::where('school_id', $schoolId)
            ->where('name', 'like', "%{$searchTerm}%");

        if ($season) {
            $query->bySeason($season);
        }

        return $query->with(['parent', 'user'])->get();
    }

    /**
     * Get the next available position for a folder
     */
    private function getNextPosition(int $schoolId, ?int $parentId): int
    {
        $maxPosition = SchoolFolder::where('school_id', $schoolId)
            ->where('parent_id', $parentId)
            ->max('position');

        return ($maxPosition ?? -1) + 1;
    }

    /**
     * Validate that a folder can be set as parent (prevent circular references)
     */
    private function validateParent(int $folderId, int $parentId): void
    {
        if ($folderId === $parentId) {
            throw new \InvalidArgumentException('A folder cannot be its own parent.');
        }

        $parent = SchoolFolder::find($parentId);

        if (!$parent) {
            throw new \InvalidArgumentException('Parent folder not found.');
        }

        // Check if the parent is a descendant of the folder (would create circular reference)
        $ancestors = $parent->ancestors();

        if ($ancestors->contains('id', $folderId)) {
            throw new \InvalidArgumentException('Cannot move folder to its own descendant.');
        }
    }

    /**
     * Duplicate a folder and optionally its children
     */
    public function duplicate(int $folderId, bool $includeChildren = false): SchoolFolder
    {
        $original = SchoolFolder::findOrFail($folderId);

        return DB::transaction(function () use ($original, $includeChildren) {
            $duplicate = $original->replicate();
            $duplicate->name = $original->name . ' (Copy)';
            $duplicate->position = $this->getNextPosition($original->school_id, $original->parent_id);
            $duplicate->save();

            if ($includeChildren && $original->hasChildren()) {
                $this->duplicateChildren($original, $duplicate);
            }

            return $duplicate;
        });
    }

    /**
     * Recursively duplicate children
     */
    private function duplicateChildren(SchoolFolder $original, SchoolFolder $newParent): void
    {
        foreach ($original->children as $child) {
            $duplicateChild = $child->replicate();
            $duplicateChild->parent_id = $newParent->id;
            $duplicateChild->save();

            if ($child->hasChildren()) {
                $this->duplicateChildren($child, $duplicateChild);
            }
        }
    }

    /**
     * Get folder tree as nested array structure
     */
    public function getFolderTreeArray(int $schoolId, ?int $season = null): array
    {
        $query = SchoolFolder::roots()
            ->where('school_id', $schoolId)
            ->with(['descendants.user', 'user', 'school']);

        if ($season) {
            $query->bySeason($season);
        }

        $folders = $query->get();

        return $folders->map(function ($folder) {
            return $this->buildFolderNode($folder);
        })->toArray();
    }

    /**
     * Recursively build folder node with children
     */
    private function buildFolderNode(SchoolFolder $folder): array
    {
        $node = [
            'id' => $folder->id,
            'name' => $folder->name,
            'season' => $folder->season,
            'position' => $folder->position,
            'parent_id' => $folder->parent_id,
            'school_id' => $folder->school_id,
            'user_id' => $folder->user_id,
            'created_by' => $folder->user ? $folder->user->name : null,
            'created_at' => $folder->created_at?->toDateTimeString(),
            'updated_at' => $folder->updated_at?->toDateTimeString(),
            'path' => $folder->getPath(),
            'is_root' => $folder->isRoot(),
            'has_children' => $folder->hasChildren(),
            'children' => [],
        ];

        if ($folder->children->isNotEmpty()) {
            $node['children'] = $folder->children->map(function ($child) {
                return $this->buildFolderNode($child);
            })->toArray();
        }

        return $node;
    }
}