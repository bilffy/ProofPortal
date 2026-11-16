<?php

return [
    'class_namespace' => 'App\\Http\\Livewire',
    'temporary_file_upload' => [
        // The default disk ('local') is rooted at image_repository, which is a read-only/mounted path on staging.
        'disk' => 'public',
        'rules' => 'file|max:15360', // in KB, so 15MB - needed for the /order artwork upload dropzone
        'max_upload_time' => 5, // minutes - Livewire's own default, plenty for a 15MB file
    ],
];
