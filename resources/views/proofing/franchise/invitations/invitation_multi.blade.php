@extends('proofing.layouts.master')
@section('title', 'Invitations')

@section('css')
    <link rel="stylesheet" href="{{ asset('proofing-assets/vendors/jexcel-1.3.4/dist/css/jquery.jexcel.css') }}">
    <link href="{{ URL::asset('proofing-assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" />
@stop

@php
    use Illuminate\Support\Facades\Crypt;
@endphp

@section('content')
    @if(session('errors'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul>
                @foreach(session('errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(Session::has('selectedJob') && Session::has('selectedSeason'))
        <div class="row">
            <div class="col-12 mb-3">
                @php
                    $btnAttrCancel = "btn btn-primary float-right pl-4 pr-4";
                    $urlDone = (strpos(request()->headers->get('referer'), $role) !== false) ? route('proofing') : url()->previous();
                    if($role === 'photocoordinator'){
                        $title = 'Photo-Coordinator';
                    }else if($role === 'teacher'){
                        $title = 'Teacher';
                    }
                @endphp
        
                <a href="{{ $urlDone }}" class="{{ $btnAttrCancel }}">Back</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-xl-8 m-xl-auto">
                <form method="POST" action="{{ route('invitations.inviteSend') }}">
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <legend>{{ __('Invite :title to :school', ['title' => $title, 'school' => $selectedJob->ts_jobname]) }}</legend>
                        </div>
                        <div class="mt-4 mr-4 ml-4">
                            {{ __('Type directly into the Spreadsheet below or copy-and-paste from an Excel document.') }}
                        </div>
                        <div class="mt-4 mb-4 ml-4" id="invite-spreadsheet"></div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">{{ __('Send invitation to join :school', ['school' => $selectedJob->ts_jobname]) }}</button>
                            <input type="hidden" name="people" value="">
                            <input type="hidden" name="job_key" value="{{ $selectedJob->ts_jobkey }}">
                            <input type="hidden" name="role" value="{{ $role }}">
                            <input type="hidden" name="model_name" value="Folders">
                            <input type="hidden" name="model_field_name" value="ts_folderkey">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else   
        @include('proofing.franchise.flash-error')
    @endif

    @php
        if($role === 'photocoordinator')
        {
            //convert list of Folders into JSON
            $sourceList = [['id' => '*', 'name' => 'All Folders']];
            foreach ($selectedJob->folders as $folder) {
                $sourceList[] = ['id' => $folder->ts_folderkey, 'name' => $folder->ts_foldername];
            }
            $sourceList = json_encode($sourceList);

            $spreadsheetData = [];
            $spreadsheetRows = range(1, 4);
            foreach ($spreadsheetRows as $spreadsheetRow) {
                $spreadsheetData[] = ['', '', '', '*'];
            }
            $spreadsheetData = json_encode($spreadsheetData);


            if (isset($tryAgain)) {
                $spreadsheetData = json_encode($tryAgain);
            }
        }else if($role === 'teacher'){
            $sourceList = [['id' => '*', 'name' => 'All Folders']];
            foreach ($selectedJob->folders as $folder) {
                $spreadsheetData[] = ['', '', '', $folder->ts_folderkey];
                $sourceList[] = ['id' => $folder->ts_folderkey, 'name' => $folder->ts_foldername];
            }
            $spreadsheetData = json_encode($spreadsheetData);
            $sourceList = json_encode($sourceList);

            if (isset($tryAgain)) {
                $spreadsheetData = json_encode($tryAgain);
            }
        }
    @endphp
@endsection

@section('js')
    <script src="{{ asset('proofing-assets/vendors/jexcel-1.3.4/dist/js/jquery.jexcel.js') }}"></script>
    <script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script>
    <script>
        $(document).ready(function () {

            var change = function (instance, cell, value) {
                var data = $('#invite-spreadsheet').jexcel('getData');
                $("input[name='people']").val(JSON.stringify(data));
            };

            var role = $("input[name='role']").val();
            var spreadsheetRows = 13; // Default value
            if (role === 'photocoordinator') {
                spreadsheetRows = {{ count($spreadsheetRows ?? []) }};
            } 

            var spreadsheetData = {!! json_encode($spreadsheetData ?? []) !!};
            
            jQuery.noConflict();
            $('#invite-spreadsheet').jexcel({
                data: spreadsheetData,
                colHeaders: ['First Name', 'Last Name', 'Email', 'Class/Group'],
                colWidths: [120, 120, 280, 200],
                minDimensions: [4, spreadsheetRows],
                maxDimensions: [4, 40],
                allowInsertColumn : false,
                columns: [
                    {type: 'text'},
                    {type: 'text'},
                    {type: 'text'},
                    {type: 'dropdown', source: {!! $sourceList !!}}
                ],
                onafterchange: change
            });
        });
    </script>
@stop
