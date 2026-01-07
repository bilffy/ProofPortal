<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('reports')->insert([
            [
                'name' => 'My Franchises',
                'description' => 'Provide a list of all the Franchises I am associated with.',
                'query' => 'myFranchises',
                'params' => NULL
            ],
            [
                'name' => 'My Schools',
                'description' => 'Provide a list of all the Schools I am associated with.',
                'query' => 'mySchools',
                'params' => NULL
            ],
            [
                'name' => 'My Folders',
                'description' => 'Provide a list of all the Folders I am associated with.',
                'query' => 'myFolders',
                'params' => NULL
            ],
            [
                'name' => 'My Folders By School',
                'description' => 'Provide a list of all the Folders I am associated with by School.',
                'query' => 'myFoldersBySchool',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  }]'
            ],
            [
                'name' => 'My Subjects',
                'description' => 'Provide a list of all the Subjects I am associated with.',
                'query' => 'mySubjects',
                'params' => NULL
            ],
            [
                'name' => 'My Subjects By School',
                'description' => 'Provide a list of all the Subjects I am associated with by School.',
                'query' => 'mySubjectsBySchool',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  }]'
            ],
            [
                'name' => 'My Subjects By Folder',
                'description' => 'Provide a list of all the Subjects I am associated with by Folder.',
                'query' => 'mySubjectsByFolder',
                'params' => '[  {    "name": "Folder",    "variable": "$ts_folder_id",    "queryName": "myFoldersIds",    "keyField": "ts_folder_id",    "valueField": "ts_foldername"  }]'
            ],
            [
                'name' => 'My Subjects By School And Folder',
                'description' => 'Provide a list of all the Subjects I am associated with by School and Folder.',
                'query' => 'mySubjectsBySchoolAndFolder',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  },  {    "name": "Folder",    "variable": "$ts_folder_id",    "queryName": "myFoldersIdsBySchool",    "keyField": "ts_folder_id",    "valueField": "ts_foldername"  }]'
            ],
            [
                'name' => 'My Photo Coordinators',
                'description' => 'Provide a list of all the Photo Coordinators I am associated with.',
                'query' => 'myPhotocoordinators',
                'params' => NULL
            ],
            [
                'name' => 'My Teachers',
                'description' => 'Provide a list of all the Teachers I am associated with.',
                'query' => 'myTeachers',
                'params' => NULL
            ],
            [
                'name' => 'My Folder Changes By School',
                'description' => 'Provide a list of changes to all Folders by School.',
                'query' => 'myFolderChangesBySchool',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  }]'
            ],
            [
                'name' => 'My Folder Changes By School And Folder',
                'description' => 'Provide a list of changes to all Folders by School and Folder.',
                'query' => 'myFolderChangesBySchoolAndFolder',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  },  {    "name": "Folder",    "variable": "$ts_folder_id",    "queryName": "myFoldersIdsBySchool",    "keyField": "ts_folder_id",    "valueField": "ts_foldername"  }]'
            ],
            [
                'name' => 'My Subject Changes By School',
                'description' => 'Provide a list of changes to all Subjects by School.',
                'query' => 'mySubjectChangesBySchool',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  }]'
            ],
            [
                'name' => 'My Subject Changes By School And Folder',
                'description' => 'Provide a list of changes to all Subjects by School and Folder.',
                'query' => 'mySubjectChangesBySchoolAndFolder',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  },  {    "name": "Folder",    "variable": "$ts_folder_id",    "queryName": "myFoldersIdsBySchool",    "keyField": "ts_folder_id",    "valueField": "ts_foldername"  }]'
            ],
            [
                'name' => 'My Subject Changes by School for Timestone Import',
                'description' => 'Provide a list of changes to Subject by School for importing into Datapost',
                'query' => 'mySubjectChangesBySchoolForTimestoneImport',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  }]'
            ],
            [
                'name' => 'TNJ Import Group Position By School',
                'description' => 'Use this to import a list into the TNJ. Filtered by School',
                'query' => 'myGroupPhotoPositionsBySchoolForTnjImporting',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  }]'
            ],
            [
                'name' => 'TNJ Import Group Position By Folder',
                'description' => 'Use this to import a list into the TNJ. Filtered by Folder',
                'query' => 'myGroupPhotoPositionsByFolderForTnjImporting',
                'params' => '[  {    "name": "Folder",    "variable": "$ts_folder_id",    "queryName": "myFoldersIds",    "keyField": "ts_folder_id",    "valueField": "ts_foldername"  }]'
            ],
            [
                'name' => 'TNJ Import Group Position By School And Folder',
                'description' => 'Use this to import a list into the TNJ. Filtered by School and Folder.',
                'query' => 'myGroupPhotoPositionsBySchoolAndFolderForTnjImporting',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  },  {    "name": "Folder",    "variable": "$ts_folder_id",    "queryName": "myFoldersIdsBySchool",    "keyField": "ts_folder_id",    "valueField": "ts_foldername"  }]'
            ],
            [
                'name' => 'Blueprint Full Change List',
                'description' => 'Runs 3 x Reports of all the Changes in a School. Developed by Ken.',
                'query' => 'blueprintFullChangeList',
                'params' => '[  {    "name": "School",    "variable": "$ts_job_id",    "queryName": "mySchoolsIds",    "keyField": "ts_job_id",    "valueField": "ts_jobname"  }]'
            ],
        ]);
    }
}
