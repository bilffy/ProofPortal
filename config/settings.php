<?php


return [

    'ssrs_server' => env('SSRS_SERVER'),
    'ssrs_folder' => env('SSRS_FOLDER'),
    'ssrs_username' => env('SSRS_USERNAME'),
    'ssrs_password' => env('SSRS_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | SSRS parameter name mapping (portal key => Report Server parameter)
    |--------------------------------------------------------------------------
    */
    'ssrs_param_map' => [
        'email' => 'email',
        'ts_job_id' => 'schoolid',
        'ts_folder_id' => 'folderid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra SSRS parameters required by specific reports on Report Server
    | Use {folderid} or {schoolid} placeholders from portal params.
    |--------------------------------------------------------------------------
    */
    'ssrs_report_extra_params' => [
    ],
];
