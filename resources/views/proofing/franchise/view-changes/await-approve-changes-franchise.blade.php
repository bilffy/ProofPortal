@extends('proofing.layouts.master')
@section('title', 'Approve Changes')

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
                {{ __('Unapproved Changes To People In :name', ['name' => $selectedJob->ts_jobname]) }}
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
    
    @if (count($subjectChanges) !== 0)
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ __('Changes to People') }}
                    </div>
                    <div class="card-body">
                        <p class="mb-0">Most changes are automatically approved, however, the following types of changes
                            need the approval of a Photo Coordinator:</p>
                        <ul>
                            <li>Moving a Person between Classes or Groups</li>
                            <li>Removal of a photo due to a Person leaving the School</li>
                            <li>Removal of a photo for reasons such as infringement or child protection</li>
                            <li>Change of photo</li>
                            <li>People absent on photo day</li>
                        </ul>
                        <p class="mb-0"><span class="alert alert-warning p-1">These changes will not show in your reports till they are approved. Please contact a Photo Coordinator and have them approve the changes.</span></p>
                        <div class="row">
                            <div class="col-lg-12 mb-2">
                                <span id="approval-acknowledge" class="p-2"></span>&nbsp;
                            </div>
                        </div>

                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        Name
                                        <a href="#" class="small people-photos-hide d-inline">(Hide Photos)</a>
                                        <a href="#" class="small people-photos-show d-none">(Show Photos)</a>
                                    </th>
                                    <th scope="col">Issue Type</th>
                                    <th scope="col">Note</th>
                                    <th scope="col">Change Requested</th>
                                    <th scope="col">By User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjectChanges as $subjectChange)
                                @php
                                    $folderKey = implode("-", $attachedFolderNames[$subjectChange->ts_subjectkey]['keys']);
                                    $skEncrypted = Crypt::encryptString($subjectChange->ts_subjectkey);
                                    if ($subjectChange->ts_subjectkey != '' && $selectedJob->ts_jobkey != '') {
                                        $image_url = route('serve.image', ['filename' => $skEncrypted, 'jobKey' => Crypt::encryptString($selectedJob->ts_jobkey)]); 
                                    }
                                @endphp
                                <tr id="{{ $folderKey }}">
                                    <td class="text-center pt-2 pb-1">
                                        <div class="person-pic-wrapper d-inline">
                                            {{-- <img src="{{ $image_url }}" class="mx-auto d-block" style="max-width: 100%; max-height: 90px;" alt="Subject Image"> --}}
                                            <img style="max-width: 100%; max-height: 90px;" class="lazyload mx-auto d-block" src="{{ $image_url }}" data-src="{{ $image_url }}" alt="Subject Image">
                                        </div>
                                        {{ $subjectChange->firstname }} {{ $subjectChange->lastname }}
                                    </td>
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
    });
</script>

@stop
