<?php

namespace App\Http\Livewire;

use App\Models\SchoolFolder;
use App\Services\SchoolFolderService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SchoolFolderComponent extends Component
{
    protected SchoolFolderService $folderService;

    // Public properties
    public $schoolId;
    public $season;
    public $searchTerm = '';

    // Form properties
    public $folderId;
    public $name;
    public $parentId;
    public $position;

    // UI state
    public $showModal = false;
    public $isEditing = false;
    public $selectedFolder;
    public $folders = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'schoolId' => 'required|exists:schools,id',
        'season' => 'required|integer|min:1900|max:2100',
        'parentId' => 'nullable|exists:school_folders,id',
        'position' => 'nullable|integer|min:0',
    ];

    public function boot(SchoolFolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    public function mount($schoolId, $season = null)
    {
        $this->schoolId = $schoolId;
        $this->season = $season ?? now()->year;
        $this->loadFolders();
    }

    public function render()
    {
        return view('livewire.school-folder-component');
    }

    /**
     * Load folders tree
     */
    public function loadFolders()
    {
        $this->folders = $this->folderService->getFolderTreeArray($this->schoolId, $this->season);
    }

    /**
     * Open modal for creating new folder
     */
    public function create($parentId = null)
    {
        $this->resetForm();
        $this->parentId = $parentId;
        $this->isEditing = false;
        $this->showModal = true;
    }

    /**
     * Open modal for editing folder
     */
    public function edit($folderId)
    {
        $folder = SchoolFolder::findOrFail($folderId);

        $this->folderId = $folder->id;
        $this->name = $folder->name;
        $this->parentId = $folder->parent_id;
        $this->position = $folder->position;
        $this->season = $folder->season;

        $this->isEditing = true;
        $this->showModal = true;
    }

    /**
     * Save folder (create or update)
     */
    public function save()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'school_id' => $this->schoolId,
                'season' => $this->season,
                'parent_id' => $this->parentId,
                'position' => $this->position,
            ];

            if ($this->isEditing) {
                $this->folderService->update($this->folderId, $data);
                $message = 'Folder updated successfully';
            } else {
                $data['user_id'] = Auth::id();
                $this->folderService->create($data);
                $message = 'Folder created successfully';
            }

            $this->loadFolders();
            $this->closeModal();
            $this->dispatch('notify', ['message' => $message, 'type' => 'success']);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);
        }
    }

    /**
     * Delete folder
     */
    public function delete($folderId, $deleteChildren = true)
    {
        try {
            $this->folderService->delete($folderId, $deleteChildren);
            $this->loadFolders();
            $this->dispatch('notify', ['message' => 'Folder deleted successfully', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Failed to delete folder', 'type' => 'error']);
        }
    }

    /**
     * Move folder to new parent
     */
    public function moveFolder($folderId, $newParentId = null, $position = null)
    {
        try {
            $this->folderService->moveFolder($folderId, $newParentId, $position);
            $this->loadFolders();
            $this->dispatch('notify', ['message' => 'Folder moved successfully', 'type' => 'success']);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify', ['message' => $e->getMessage(), 'type' => 'error']);
        }
    }

    /**
     * Reorder folders
     */
    public function reorderFolders($folderPositions)
    {
        try {
            $this->folderService->reorder($folderPositions);
            $this->loadFolders();
            $this->dispatch('notify', ['message' => 'Folders reordered successfully', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Failed to reorder folders', 'type' => 'error']);
        }
    }

    /**
     * Search folders
     */
    public function search()
    {
        if (strlen($this->searchTerm) >= 2) {
            $results = $this->folderService->search($this->schoolId, $this->searchTerm, $this->season);
            $this->folders = $results->toArray();
        } else {
            $this->loadFolders();
        }
    }

    /**
     * Clear search
     */
    public function clearSearch()
    {
        $this->searchTerm = '';
        $this->loadFolders();
    }

    /**
     * Duplicate folder
     */
    public function duplicate($folderId, $includeChildren = false)
    {
        try {
            $this->folderService->duplicate($folderId, $includeChildren);
            $this->loadFolders();
            $this->dispatch('notify', ['message' => 'Folder duplicated successfully', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Failed to duplicate folder', 'type' => 'error']);
        }
    }

    /**
     * View folder details
     */
    public function viewFolder($folderId)
    {
        $this->selectedFolder = $this->folderService->getFolderWithDescendants($folderId);
    }

    /**
     * Close modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form fields
     */
    private function resetForm()
    {
        $this->folderId = null;
        $this->name = '';
        $this->parentId = null;
        $this->position = null;
        $this->resetErrorBag();
    }

    /**
     * Update season filter
     */
    public function updatedSeason()
    {
        $this->loadFolders();
    }

    /**
     * Update search term
     */
    public function updatedSearchTerm()
    {
        $this->search();
    }
}