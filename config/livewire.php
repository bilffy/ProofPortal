<?php

return [
    'class_namespace' => 'App\\Http\\Livewire',
    'temporary_file_upload' => [
        'rules'=> 'file|max:102400', // in KB, so 100M
    ],
];
