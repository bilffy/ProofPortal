@extends('proofing.layouts.master')
@section('title', 'Proofing')

@section('css')

@stop

@section('content')

        @php
            use Illuminate\Support\Carbon;
            use Illuminate\Support\Facades\Crypt;
        @endphp

        <div class="row">
            <div class="col-lg-12"> 
                <h1 class="page-header">
                    <span class="display-4">{{ __("Hello ") }} @if(isset($user)) {{ $user['firstname'] }}! @endif {{ __("Let's get you started.") }}</span>
                </h1>
            </div>
        </div>
        
        @if(count($jobs) > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <p class="lead">
                        Please select a Job to work on. 
                    </p>
                </div>
                <div class="col-12">
                    <div class="row">
                        @foreach($jobs as $job)
                        @php
                            $jobIdHash = Crypt::encryptString($job->ts_jobkey);
                        @endphp
                            <div class="col-6 col-lg-3">
                                <div class="card">
                                    <div class="card-body p-3 clearfix">
                                        <i class="fa fa-mortar-board bg-secondary bg-inverse p-3 font-2xl mr-3 float-left"></i>
                                        <div class="h5 text-secondary mb-0 mt-2">
                                            {{ $job->ts_jobname }} ({{ $job->seasons->code }})<br>
                                            <a href="#" id="open-job-link" data-job="{{ $jobIdHash }}">Open Job</a>  
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
@endsection

@section('js')

<script src="{{ URL::asset('proofing-assets/js/franchise/ajaxcontrol.js') }}"></script>

@stop


