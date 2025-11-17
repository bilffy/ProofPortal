@extends('proofing.layouts.master')
@section('title', 'Manage Photo Coordinators & Teachers')

@section('css')
@stop

@section('content')
    @php
        use Illuminate\Support\Facades\Crypt;
        use App\Models\Folder;
    @endphp
    @if(Session::has('selectedJob') && Session::has('selectedSeason'))
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    {{ __(':school_name Photo-Coordinators and Teachers', ['school_name' => $selectedJob->ts_jobname]) }}
                </h1>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12 m-auto">
                @php
                $btnAttrDanger = [
                    'class' => 'btn btn-primary float-right pl-4 pr-4',
                ];

                $urlDone = strpos(url()->previous(), 'staffs') ? route('proofing') : url()->previous();
                @endphp
        
                <a href="{{ $urlDone }}" class="{{ $btnAttrDanger['class'] }}">
                    {{ __('Done') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="users index">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i> {{ __('Photo-Coordinators') }}
                        </div>
                        <div class="card-body">
                            @if ($photocoordinators)
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th class="idx-first-name" scope="col" width="15%">{{ __('First Name') }}</th>
                                            <th class="idx-last-name" scope="col" width="15%">{{ __('Last Name') }}</th>
                                            <th class="idx-email" scope="col" width="20%">{{ __('Email') }}</th>
                                            <th class="idx-folders" scope="col">{{ __('Folders to Proof') }}
                                                <a href="#" class="photo-coordinator-folder-list-show d-none">(Show)</a>
                                                <a href="#" class="photo-coordinator-folder-list-hide">(Hide)</a>
                                            </th>
                                            <th scope="col" class="actions" width="15%">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($photocoordinators as $photocoordinator)
                                            <tr>
                                                <td class="idx-first-name">{{ $photocoordinator->firstname }}</td>
                                                <td class="idx-last-name">{{ $photocoordinator->lastname }}</td>
                                                <td class="idx-email">{{ $photocoordinator->email }}</td>
                                                <td class="idx-folders">
                                                    @php
                                                        $usersFolders = Folder::where('ts_job_id', $selectedJob->ts_job_id)
                                                                        ->whereHas('folderUsers', function($query) use ($photocoordinator) {
                                                                            $query->where('user_id', $photocoordinator->id); // Filter FolderUser by user_id
                                                                        })->select('ts_foldername')->get();
                                                    @endphp
                                                    @if($usersFolders->isNotEmpty())
                                                        <ul class="m-0 photo-coordinator-folder-list">
                                                            @foreach ($usersFolders as $folder)
                                                                <li>{{ $folder->ts_foldername }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                                <td class="actions">
                                                    {{-- <form action="{{ route('my-photocoordinators.impersonate', $user->id) }}" method="POST">
                                                        @csrf
                                                        @method('POST')
                                                        <a href="#" onclick="return confirm('{{ __('Are you sure you want to logout and pose as :name?', ['name' => $photocoordinator->name]) }}')">
                                                            {{ __('Pose') }}
                                                        </a>
                                                    </form>
                                                    | --}}
                                                    <form id="remove-job-{{ $photocoordinator->id }}" action="{{ route('user.remove-from-job', ['userId' => Crypt::encryptString($photocoordinator->id), 'tsJobId' => Crypt::encryptString($selectedJob->ts_job_id)]) }}" method="POST" style="display:none;">
                                                        @csrf
                                                    </form>
                                                    
                                                    <a href="#" onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to remove :name from this Job?', ['name' => $photocoordinator->name]) }}')) { document.getElementById('remove-job-{{ $photocoordinator->id }}').submit(); }">
                                                        {{ __('Revoke all User Access') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <span class="alert alert-warning p-1">
                                    {{ __('You must have at least 1 Photo-Coordinator in a Job to approve Subjects.') }} 
                                    <a href="{{route('invitation.index', ['role' => 'photocoordinator'])}}">
                                        {{ __('Invite a Photo-Coordinator here') }}
                                    </a>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="users index">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i> {{ __('Teachers') }}
                        </div>
                        <div class="card-body">
                            @if ($teachers)
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th class="idx-first-name" scope="col" width="15%">{{ __('First Name') }}</th>
                                            <th class="idx-last-name" scope="col" width="15%">{{ __('Last Name') }}</th>
                                            <th class="idx-email" scope="col" width="20%">{{ __('Email') }}</th>
                                            <th class="idx-folders" scope="col">{{ __('Folders to Proof') }}
                                                <a href="#" class="teacher-folder-list-show d-none">(Show)</a>
                                                <a href="#" class="teacher-folder-list-hide">(Hide)</a>
                                            </th>
                                            <th scope="col" class="actions" width="15%">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($teachers as $teacher)
                                            <tr>
                                                <td class="idx-first-name">{{ $teacher->firstname }}</td>
                                                <td class="idx-last-name">{{ $teacher->lastname }}</td>
                                                <td class="idx-email">{{ $teacher->email }}</td>
                                                <td class="idx-folders">
                                                    @php
                                                        $usersFolders = Folder::where('ts_job_id', $selectedJob->ts_job_id)
                                                                        ->whereHas('folderUsers', function($query) use ($teacher){
                                                                            $query->where('user_id', $teacher->id); // Filter FolderUser by user_id
                                                                        })->select('ts_foldername','ts_folder_id')->get();
                                                    @endphp
                                                    <ul class="m-0 teacher-folder-list">
                                                        @if($usersFolders->isNotEmpty())
                                                            @foreach ($usersFolders as $folder)
                                                                <li>
                                                                    {{ $folder->ts_foldername }} 
                                                                    <form id="remove-folder-{{ $teacher->id }}-{{$folder->ts_folder_id}}" action="{{ route('user.remove-from-folder', ['userId' => Crypt::encryptString($teacher->id), 'tsFolderId' => Crypt::encryptString($folder->ts_folder_id), 'tsJobId' => Crypt::encryptString($selectedJob->ts_job_id)]) }}" method="POST" style="display:none;">
                                                                        @csrf
                                                                    </form>
                                                                    
                                                                    <a href="#" onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to remove :name from this Folder?', ['name' => $teacher->name]) }}')) { document.getElementById('remove-folder-{{ $teacher->id }}-{{$folder->ts_folder_id}}').submit(); }">
                                                                        {{ __('Revoke User Access') }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        @endif
                                                    </ul>
                                                </td>
                                                <td class="actions">
                                                    {{-- <form action="{{ route('my-teachers.impersonate', $user->id) }}" method="POST">
                                                        @csrf
                                                        @method('POST')
                                                        <a href="#" onclick="return confirm('{{ __('Are you sure you want to logout and pose as :name?', ['name' => $teacher->name]) }}')">
                                                            {{ __('Pose') }}
                                                        </a>
                                                    </form>
                                                    | --}}
                                                    <form id="remove-job-{{ $teacher->id }}" action="{{ route('user.remove-from-job', ['userId' => Crypt::encryptString($teacher->id), 'tsJobId' => Crypt::encryptString($selectedJob->ts_job_id)]) }}" method="POST" style="display:none;">
                                                        @csrf
                                                    </form>
                                                    
                                                    <a href="#" onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to remove :name from this Job?', ['name' => $teacher->name]) }}')) { document.getElementById('remove-job-{{ $teacher->id }}').submit(); }">
                                                        {{ __('Revoke all User Access') }}
                                                    </a>                                                    
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="mb-0">
                                    {{ __('No Teachers that you can manage.') }} 
                                    <a href="{{route('invitation.index', ['role' => 'teacher'])}}">
                                        {{ __('Invite a Teacher here') }}
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="users index">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i> {{ __('Other Users with access to this Job and its Folders. Only Administrators can modify these Users.') }}
                        </div>
                        <div class="card-body">
                            @if ($otherList)
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <!-- Table Headings -->
                                        <tr>
                                            <th class="idx-first-name" scope="col" width="15%">{{ __('First Name') }}</th>
                                            <th class="idx-last-name" scope="col" width="15%">{{ __('Last Name') }}</th>
                                            <th class="idx-email" scope="col" width="20%">{{ __('Email') }}</th>
                                            <th class="idx-folders" scope="col">
                                                {{ __('Folders to Proof') }}
                                                <a href="#" class="other-people-folder-list-show">{{ __('(Show)') }}</a>
                                                <a href="#" class="other-people-folder-list-hide d-none">{{ __('(Hide)') }}</a>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($otherList as $otherListuser)
                                            <tr>
                                                <td class="idx-first-name">{{ $otherListuser->firstname }}</td>
                                                <td class="idx-last-name">{{ $otherListuser->lastname }}</td>
                                                <td class="idx-email">{{ $otherListuser->email }}</td>
                                                <td class="idx-folders">
                                                    @php
                                                        $usersFolders = Folder::where('ts_job_id', $selectedJob->ts_job_id)
                                                                        ->whereHas('folderUsers', function($query) use ($otherListuser) {
                                                                            $query->where('user_id', $otherListuser->id); // Filter FolderUser by user_id
                                                                        })->select('ts_foldername')->get();
                                                    @endphp
                                                    @if($usersFolders->isNotEmpty())
                                                        <ul class="m-0 other-people-folder-list d-none">
                                                            @foreach ($usersFolders as $usersFolder)
                                                                <li>{{ $usersFolder->ts_foldername }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="mb-0">{{ __('No additional Users are associated with this Job and its Folders.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    @else   
        @include('proofing.franchise.flash-error')
    @endif
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $("a.teacher-folder-list-show").on("click", function (e) {
                $(this).addClass('d-none');
                $("a.teacher-folder-list-hide").removeClass('d-none');
                $(".teacher-folder-list").removeClass('d-none');
            });

            $("a.teacher-folder-list-hide").on("click", function (e) {
                $(this).addClass('d-none');
                $("a.teacher-folder-list-show").removeClass('d-none');
                $(".teacher-folder-list").addClass('d-none');
            });

            $("a.photo-coordinator-folder-list-show").on("click", function (e) {
                $(this).addClass('d-none');
                $("a.photo-coordinator-folder-list-hide").removeClass('d-none');
                $(".photo-coordinator-folder-list").removeClass('d-none');
            });

            $("a.photo-coordinator-folder-list-hide").on("click", function (e) {
                $(this).addClass('d-none');
                $("a.photo-coordinator-folder-list-show").removeClass('d-none');
                $(".photo-coordinator-folder-list").addClass('d-none');
            });

            $("a.other-people-folder-list-show").on("click", function (e) {
                $(this).addClass('d-none');
                $("a.other-people-folder-list-hide").removeClass('d-none');
                $(".other-people-folder-list").removeClass('d-none');
            });

            $("a.other-people-folder-list-hide").on("click", function (e) {
                $(this).addClass('d-none');
                $("a.other-people-folder-list-show").removeClass('d-none');
                $(".other-people-folder-list").addClass('d-none');
            });
        });
    </script>
@stop
