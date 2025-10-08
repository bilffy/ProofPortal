<?php

return [
    'class_namespace' => 'App\\Http\\Livewire',
    'temporary_file_upload' => [
        'rules'=> 'image|max:102400', // in KB, so 100M
    ],
];
