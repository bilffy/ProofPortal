<div class="row">
    <div class="col-12 m-auto">
        <p class="h5 lead mb-1"><strong>TNJ Data Refresh</strong></p>
        <p>The below options are available to refresh data from a TNJ file.</p>
    </div>
</div>

<div class="row">
    <div class="col-9 m-auto">
        <p class="lead mb-0"><strong>{{ __('Update Images of People') }}</strong></p>
        <ul>
            @if($imageCount['totalTSSubjectImages'] != $imageCount['totalBPSubjectImages'])
                <li>
                    <span class="text-danger">
                        {{ __('There are :tnj thumbnails in the TNJ and :bp thumbnails in Blueprint. It might be a good idea to Update Images of People.', [
                            'tnj' => $imageCount['totalTSSubjectImages'], 
                            'bp' => $imageCount['totalBPSubjectImages']
                        ]) }}
                    </span> 
                </li>
            @else
                @php
                    $missingMessage = ($imageCount['bpSubjectCount'] > $imageCount['totalTSSubjectImages']) 
                        ? __(' (missing :count thumbnails in the TNJ)', ['count' => $imageCount['bpSubjectCount'] - $imageCount['totalTSSubjectImages']]) 
                        : "";
                @endphp
                
                <li>
                    {{ __('There are :tnj Thumbnails in the TNJ and :sub Subjects in the School:missing.', [
                        'tnj' => $imageCount['totalTSSubjectImages'],
                        'sub' => $imageCount['bpSubjectCount'],
                        'missing' => $missingMessage
                    ]) }}
                </li>
            @endif
            <li>{{ __('This will refresh all People Images for this School.') }}</li>
            <li>{{ __('Use this when you have updated or added People images in the TNJ.') }}</li>
            <li>{!! __('This will <strong>NOT</strong> refresh Personal details such as First and Last names.') !!}</li>
        </ul>
    </div>
    <div class="col-3 m-auto">
        {{-- @if (auth()->user()->hasRole('franchise')) --}}
            @php
                $confirmMsg = __('Are you sure you want to update People Images of :school? This should only be done if you have uploaded new images in the TNJ.', ['school' => $selectedJob->ts_jobname]);
            @endphp
            <form action="{{ URL::signedRoute('config-job-action', ['action' => 'update-people-images', 'hash' => $jobKeyHash]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary float-right pl-4 pr-4" onclick="return confirm('{{ $confirmMsg }}')">
                    {{ __('Update Images of People') }}
                </button>
            </form>
        {{-- @endif --}}
    </div>
</div>

<div class="row">
    <div class="col-9 m-auto">
        <p class="lead mb-0"><strong>{{ __('Update Person Folder Attachments') }}</strong></p>
        <ul>
            <li>{{ __('This will reattach People into their Folders.') }}</li>
            <li>{{ __('Use this when you have moved a Person between Folders in the TNJ.') }}</li>
            <li>{!! __('This will <strong>NOT</strong> refresh Personal details such as First and Last names.') !!}</li>
        </ul>
    </div>
    <div class="col-3 m-auto">
        {{-- @if (auth()->user()->hasRole('franchise')) --}}
            @php
                $confirmMsg = __('Are you sure you want to update how People are linked to Folders for :school? This should only be done if you have moved People between Folders in the TNJ.', ['school' => $selectedJob->ts_jobname]);
            @endphp
            <form action="{{ URL::signedRoute('config-job-action', ['action' => 'update-subject-associations', 'hash' => $jobKeyHash]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary float-right pl-4 pr-4" onclick="return confirm('{{ $confirmMsg }}')">
                    {{ __('Update Person Folder Attachments') }}
                </button>
            </form>
        {{-- @endif --}}
    </div>
</div>

@if ($compiledFolderDuplicates->count() > 0)
    <div class="row">
        <div class="col-9 m-auto">
            <p class="lead mb-0"><strong>{{ __('Merge Duplicate Folders') }}</strong></p>
            <ul>
                <li><span class="text-danger">
                    {{ __('We have found :count Duplicate Folders in this School.', ['count' => $compiledFolderDuplicates->count()]) }}
                </span></li>
                <li>{{ __('On rare occasions, a double sync of Folders may happen.') }}</li>
                <li>{{ __('This action will merge Duplicate Folders into a single Folder.') }}</li>
                <li>{{ __('Modifications (e.g. Folder Name) to duplicate Folders will also be merged.') }}</li>
            </ul>
        </div>
        <div class="col-3 m-auto">
            {{-- @if (auth()->user()->hasRole('franchise')) --}}
                @php
                    $confirmMsg = __('Please check that duplicate Folders have been removed after merging.');
                @endphp
                <form action="{{ URL::signedRoute('config-job-action', ['action' => 'merge-duplicate-folders', 'hash' => $jobKeyHash]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary float-right pl-4 pr-4" onclick="return confirm('{{ $confirmMsg }}')">
                        {{ __('Merge Duplicate Folders') }}
                    </button>
                </form>
            {{-- @endif --}}
        </div>
    </div>
@endif

@if ($compiledSubjectDuplicates->count() > 0)
    <div class="row">
        <div class="col-9 m-auto">
            <p class="lead mb-0"><strong>{{ __('Merge Duplicate People') }}</strong></p>
            <ul>
                <li><span class="text-danger">
                    {{ __('We have found :count Duplicate People in this School.', ['count' => $compiledSubjectDuplicates->count()]) }}
                </span></li>
                <li>{{ __('On rare occasions, a double sync of People may happen.') }}</li>
                <li>{{ __('This action will merge Duplicate People into a single Person.') }}</li>
                <li>{{ __('Modifications (e.g. spelling) to duplicate People will also be merged.') }}</li>
            </ul>
        </div>
        <div class="col-3 m-auto">
            {{-- @if (auth()->user()->hasRole('franchise')) --}}
                @php
                    $confirmMsg = __('Please check that duplicate People have been removed after merging.');
                @endphp
                <form action="{{ URL::signedRoute('config-job-action', ['action' => 'merge-duplicate-subjects', 'hash' => $jobKeyHash]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary float-right pl-4 pr-4" onclick="return confirm('{{ $confirmMsg }}')">
                        {{ __('Merge Duplicate People') }}
                    </button>
                </form>
            {{-- @endif --}}
        </div>
    </div>
@endif