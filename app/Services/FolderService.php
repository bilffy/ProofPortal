<?php

namespace App\Services;
use App\Models\Folder;

class FolderService
{

    public function updateFolderData($folderIds, $field, $value)
    {
        return Folder::whereIn('ts_folder_id', $folderIds)
        ->update([$field => $value]);
    }

}
