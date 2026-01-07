@extends('proofing.layouts.master')
@section('title', 'View Changes')

@section('css')

@stop

@section('content')

    @php
        use Illuminate\Support\Str;
    @endphp
    @if(Session::has('selectedJob') && Session::has('selectedSeason'))

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                {{ __('All Changes Made To People In :name', ['name' => $selectedJob->ts_jobname]) }}
            </h1>
        </div>
    
        <div class="col-12 mb-3">
            @php
                $btnAttrCancel = [
                    "class" => "btn btn-primary float-right pl-4 pr-4",
                ];
    
                $urlDone = route('proofing');
                $image_url = asset('proofing-assets/img/subject-image.png');   
            @endphp
    
            <a href="{{ $urlDone }}" class="{{ $btnAttrCancel['class'] }}">{{ __('Done') }}</a>
        </div>
    </div>

    @if (count($folderChanges) !== 0)
        <div class="row">
            <div class="col-lg-12">
                <div class="schools index">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i> {{ __('Changes to Classes') }}
                        </div>
                        <div class="card-body">
                            <p class="mb-3">The following is a list of changes to all Classes. Changes are grouped by class and
                                listed in chronological order - most recent changes at the bottom.</p>
        
                            <table class="table table-bordered table-sm">
                                <thead>
                                <!-- Table Headings-->
                                <tr>
                                    <th class="idx-folder-name" scope="col">
                                        Class Name
                                    </th>
                                    <th class="idx-issue-type" scope="col">
                                        Issue Type
                                    </th>
                                    <th class="idx-note" scope="col">
                                        Note
                                    </th>
                                    <th class="idx-time-changed" scope="col">
                                        Change Made
                                    </th>
                                    <th class="idx-user" scope="col">
                                        By User
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $visibleFolderById = [];
                                @endphp
        
                                @foreach ($folderChanges as $folderId => $folderChange)
                                            <tr class="folder-{{ $folderChange->ts_folderkey }} folder-filter"
                                                data-folder-key="{{ $folderChange->ts_folderkey }}">
                                                <td class="idx-folder-name">
                                                    {{ $folderChange->ts_foldername }}
                                                </td>
                                                <td class="idx-issue-type">
                                                    {{ $folderChange->external_issue_name }}
                                                </td>
                                                <td class="idx-note">
                                                    @if ($folderChange->notes === 'The following general issues are present in this Folder: ""')
                                                        [User flagged an issue but did not enter a note]
                                                    @else
                                                        {{ $folderChange->notes }}
                                                    @endif
                                                </td>
                                                <td class="idx-time-changed">
                                                    {{ \Carbon\Carbon::parse($folderChange->change_datetime)->format('Y-m-d H:i:s') }}
                                                </td>
                                                <td class="idx-user">
                                                    {{ $folderChange->user->firstname }} {{ $folderChange->user->lastname }}
                                                </td>
                                            </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @if (count($subjectChanges) !== 0)
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ __('Changes to People') }}
                    </div>
                    <div class="card-body">
                        <p class="mb-3">The following is a list of all changes to all People. Changes are listed in chronological order - most recent changes at the bottom.</p>
        
                        <p>Filter People by Class Name:
                            <button type="button" class="btn btn-sm btn-primary ml-1 mt-1 mb-1 mr-1 click-folder-filter" data-click-folder-key="all">All Classes</button>
                            @foreach($allFolders as $allFolder)
                                <button type="button" class="btn btn-sm btn-outline-primary ml-1 mt-1 mb-1 mr-1 click-folder-filter" data-click-folder-key="{{ $allFolder->ts_folderkey }}">{{ $allFolder->ts_foldername }}</button>
                            @endforeach
                        </p>
        
                        <table class="table table-bordered table-striped table-sm mt-3">
                            <thead>
                                <tr>
                                    <th scope="col">Class</th>
                                    <th scope="col">
                                        Name
                                        <a href="#" class="small people-photos-hide d-inline">(Hide Photos)</a>
                                        <a href="#" class="small people-photos-show d-none">(Show Photos)</a>
                                    </th>
                                    <th scope="col">Key</th>
                                    <th scope="col">Issue Type</th>
                                    <th scope="col">Note</th>
                                    <th scope="col">Change Made</th>
                                    <th scope="col">By User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjectChanges as $subjectChange)
                                @php
                                    $folderKey = implode("-", $attachedFolderNames[$subjectChange->ts_subjectkey]['keys']);
                                    $folderName = implode("<br>", $attachedFolderNames[$subjectChange->ts_subjectkey]['names']);
                                    $skEncrypted = Crypt::encryptString($subjectChange->ts_subjectkey);
                                    if ($subjectChange->ts_subjectkey != '' && $selectedJob->ts_jobkey != '') {
                                        // $combined_key = $subjectChange->ts_subjectkey . $selectedJob->ts_jobkey;
                                        // $encryptImageKey = sprintf("%08x", crc32($combined_key));
                                        // $hashed_key = hash('sha256', $combined_key);
                                        // $sub_dirs = [];

                                        // for ($i = 0; $i < strlen($hashed_key); $i += 5) {
                                        //     $sub_dirs[] = substr($hashed_key, $i, 3);
                                        // }

                                        // // Generate the directory structure and filename using DIRECTORY_SEPARATOR
                                        // $full_path = implode(DIRECTORY_SEPARATOR, $sub_dirs);
                                        // $imageName = DIRECTORY_SEPARATOR . $full_path . DIRECTORY_SEPARATOR . $encryptImageKey . '.jpg';
                                        // $newimageName = Str::replace('\\', '-', $imageName);
                                        // // Generate a signed URL for the image
                                        // $image_url = route('serve.image', ['filename' => $newimageName]);
                                        $image_url = route('serve.image', ['filename' => $skEncrypted]);
                                    }
                                @endphp
                                
                                <tr class="folder-{{ $folderKey }} folder-filter" data-folder-key="{{ $folderKey }}">
                                    <td>{!! $folderName !!}</td>
                                    <td class="text-center pt-2 pb-1">
                                        <div class="person-pic-wrapper d-inline">
                                            {{-- <img src="{{ $image_url }}" class="mx-auto d-block" style="max-width: 100%; max-height: 90px;" alt="Subject Image"> --}}
                                            <img style="max-width: 100%; max-height: 90px;" class="lazyload mx-auto d-block" src="{{ $image_url }}" data-src="{{ $image_url }}" alt="Subject Image">
                                        </div>
                                        {{ $subjectChange->firstname }} {{ $subjectChange->lastname }}
                                    </td>
                                    <td>{{ $subjectChange->ts_subjectkey }}</td>
                                    <td>{{ $subjectChange->external_issue_name }}</td>
                                    <td>{{ $subjectChange->notes }}</td>
                                    <td>{{ \Carbon\Carbon::parse($subjectChange->change_datetime)->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $subjectChange->user->firstname }} {{ $subjectChange->user->lastname }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @else

        @include('proofing.franchise.flash-error')

    @endif

@endsection

@section('js')

<script>
    $(document).ready(function () {
        var linkHide = $(".people-photos-hide");
        var linkShow = $(".people-photos-show");
        var picWrapper = $(".person-pic-wrapper");

        linkHide.on('click', function () {
            linkHide.toggleClass("d-none d-inline");
            linkShow.toggleClass("d-inline d-none");
            picWrapper.toggleClass("d-none d-inline");
        });

        linkShow.on('click', function () {
            linkHide.toggleClass("d-none d-inline");
            linkShow.toggleClass("d-inline d-none");
            picWrapper.toggleClass("d-none d-inline");
        });

        $('.click-folder-filter').on('click', function () {
            filterSchoolsTable($(this));
        });

        function filterSchoolsTable(button) {
            $('.click-folder-filter').removeClass('btn-primary').addClass('btn-outline-primary');
            button.removeClass('btn-outline-primary').addClass('btn-primary');

            var filterByText = button.attr('data-click-folder-key');

            if (filterByText === 'all') {
                $(".folder-filter").removeClass("d-none");
            } else {
                $(".folder-filter").addClass("d-none");
                $("[data-folder-key*='" + filterByText + "']").removeClass("d-none");
            }
        }
    });
</script>

@stop
