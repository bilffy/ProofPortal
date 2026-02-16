@extends('proofing.layouts.master')
@section('title', 'Bulk Upload Group Images')

@section('css')
    <link href="{{ asset('proofing-assets/vendors/dropzone-5.7.0/dist/min/basic.min.css') }}" rel="stylesheet">
    <link href="{{ asset('proofing-assets/vendors/dropzone-5.7.0/dist/min/dropzone.min.css') }}" rel="stylesheet">
@stop

@php
    $groupImageMatchLocation = URL::signedRoute('bulkUpload.image', ['hash' => $jobHash, 'step' => 'match']);
@endphp

@section('content')
    @if(Session::has('selectedJob') && Session::has('selectedSeason'))

    @if (Session::has('error'))
        @include('proofing.franchise.flash-error', ['message' => Session::get('error')])
    @endif

        {{-- Check user role --}}
        {{-- @if (auth()->user()->hasAnyRole(['superadmin', 'admin', 'franchise'])) --}}
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">
                        {{ __(':school Bulk Upload Group Images', ['school' => $selectedJob->ts_jobname]) }}
                    </h1>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12 m-auto">
                                            <a href="#" id="cancel-upload" class="btn btn-secondary float-right pl-4 pr-4"
                                            data-cancel="true" 
                                            data-upload_session="{{ $uploadSession }}"
                                            data-redirect-url="{{ URL::signedRoute('proofing.dashboard', ['hash' => $jobHash]) }}">
                                                {{ __('Cancel') }}
                                            </a>
                                        </div>
                                    </div>

            @if($step === 'upload')
                <div class="row">
                    <div class="col-lg-12 m-auto">
                        <div class="folders index">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fa fa-upload"></i> {{ __('Bulk Upload Group Images') }}
                                </div>
                                <div class="card-body">
                                    @php
                                        $uploadMaxFilesize = ini_get('upload_max_filesize');
                                    @endphp
                                    <p class="mb-0">Upload Instructions</p>
                                    <ul>
                                        <li>Drag multiple files from Windows Explorer or OSX Finder.</li>
                                        <li>JPG and PNG images only - other files will be rejected including zip files.</li>
                                        <li>There is a {{ $uploadMaxFilesize }} size limit per image.</li>
                                        <li>Match Images to Folders in the next step.</li>
                                    </ul>
        
                                    {{-- Laravel form --}}
                                    <form method="POST" action="{{ route('groupImage.upload') }}" class="dropzone" id="dropzone-bulk-upload">
                                        @csrf
                                        <input type="hidden" name="upload_session" value="{{ $uploadSession }}">
                                        <input type="hidden" name="jobHash" value="{{ $jobHash }}">
                                    </form>
                                </div>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-12 m-auto">
                                            <a href="{{$groupImageMatchLocation}}" class="btn btn-primary float-right pl-4 pr-4"
                                            data-match="true" data-upload_session="{{ $uploadSession }}">
                                                {{ __('Next') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($step === 'match')
                <div class="row">
                    <div class="col-lg-12 m-auto">
                        <div class="folders index">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fa fa-exchange"></i> {{ __('Match Images to Folders') }}
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">Matching Instructions</p>
                                    <ul>
                                        <li>Pattern matching finds the closest match between Group Image and Folder names.</li>
                                        <li>Select a Folder for each Group Image.</li>
                                        <li>New Group Images will replace previously uploaded ones.</li>
                                        <li>Select 'Discard Image' or 'No Match' for mistakes.</li>
                                    </ul>
        
                                    <div class="row">
                                        <div class="col-lg-8 col-xl-6 m-auto">
                                            <table class="table table-bordered table-striped table-sm">
                                                <thead>
                                                <tr>
                                                    <th class="idx-image text-center" scope="col" width="40%">Group Image</th>
                                                    <th class="idx-folder text-center" scope="col">Folder</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @php
                                                    $folderList = [
                                                        'discard_image' => '----Discard Image----',
                                                        'no_match' => '----No Match----',
                                                    ];
                                                    foreach ($selectedJob->folders as $folder) {
                                                        $folderList[$folder->ts_folderkey] = $folder->ts_foldername;
                                                    }
                                                @endphp
        
                                                @foreach ($uploadedImages as $image)
                                                    <tr>
                                                        <td class="idx-image">
                                                            <img loading="lazy" src="{{ asset('storage/' . $image) }}"
                                                                class="mx-auto d-block group-image mt-3" style="max-width: 100%;" width='200' height='133'>
                                                            <p class="text-center mb-2">{{ basename($image) }}</p>
                                                        </td>
                                                        <td class="idx-folder align-middle">
                                                            <select class="form-control folder-select"
                                                                    data-artifact-token="{{ $image }}">
                                                                @foreach ($folderList as $key => $name)
                                                                    <option value="{{ $key }}">
                                                                        {{ $name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-12 m-auto">
                                            <form id="folder-matching-form" method="POST" action="{{ route('groupImage.submit') }}">
                                                @csrf
                                                <input type="hidden" name="upload_session" value="{{ $uploadSession }}">
                                                <input type="hidden" name="jobHash" value="{{ $jobHash }}">
                                                <input type="hidden" name="artifact-to-folder-map" value="">
                                                <button type="submit" class="btn btn-primary float-right pl-4 pr-4">{{ __('Submit') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        {{-- @endif --}}
    @else   
        @include('proofing.franchise.flash-error')
    @endif
@endsection

@section('js')
    <script src="{{ asset('proofing-assets/vendors/dropzone-5.7.0/dist/min/dropzone.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#folder-matching-form').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission

                // Create an object to store the mapping of images to folder selections
                let mapping = {};
                $('.folder-select').each(function () {
                    let artifactToken = $(this).data('artifact-token'); // Get the artifact token
                    let folderKey = $(this).val(); // Get the selected folder key
                    mapping[artifactToken] = folderKey; // Map artifact token to selected folder key
                });

                // Store the mapping as JSON in the hidden input field
                $('input[name="artifact-to-folder-map"]').val(JSON.stringify(mapping));

                // Now submit the form
                this.submit(); // Submit the form after setting the mapping
            });

            $('#cancel-upload').on('click', function (e) {
                e.preventDefault(); // Prevent default action

                // Get the upload session value
                var uploadSession = $(this).data('upload_session');
                var redirectUrl = $(this).data('redirect-url'); // Get the dynamic redirect URL

                // Confirm the action (optional)
                if (!confirm('Are you sure you want to cancel and delete the uploaded images?')) {
                    return;
                }

                // Make the AJAX request
                $.ajax({
                    url: "{{ route('groupImage.delete') }}",  // Route for deletion
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",  // Include CSRF token
                        upload_session: uploadSession  // Send the upload session as data
                    },
                    success: function (response) {
                        // Redirect to proofing upon successful deletion
                        window.location.href = redirectUrl ? redirectUrl : "{{ route('proofing') }}";
                    },
                    error: function (xhr, status, error) {
                        // Handle the error response
                        alert('An error occurred while deleting the images.');
                    }
                });
            });
        });
    </script>
@stop
