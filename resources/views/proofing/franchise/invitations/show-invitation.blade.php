@extends('proofing.layouts.master')
@section('title', 'Invitations')

@section('css')
@stop

@section('content')
    @if(Session::has('selectedJob') && Session::has('selectedSeason'))
        <div class="row mt-4">
            <div class="col-12">
                <p class="display-4">
                    Who would you like to invite?
                </p>
            </div>
            <div class="col-12 mb-3">
                @php
                    // Set the button attributes
                    $btnAttrCancel = 'class="btn btn-primary float-right pl-4 pr-4"';
        
                    // Get the previous URL
                    $referer = url()->previous();
        
                    // If the referer contains 'invitations', set the URL to the dashboard route, otherwise use the referer
                    if (str_contains($referer, 'invitations')) {
                        $urlDone = route('proofing'); // Assuming you have a named route 'dashboard'
                    } else {
                        $urlDone = $referer;
                    }
                @endphp
        
                {{-- Render the link --}}
                <a href="{{ $urlDone }}" {!! $btnAttrCancel !!}>
                    {{ __('Done') }}
                </a>
            </div>

            <div class="col-12">
                <div class="row">

                    <div class="col-6 col-lg-3">
                        <a href="{{route('invitation.index', ['role' => 'photocoordinator'])}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="icon icon-plus bg-primary p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-primary mb-0 mt-3">Invite a Photo-Coordinator</div>
                                </div>
                            </div>
                        </a>
                    </div>
        
                    <div class="col-6 col-lg-3">
                        <a href="{{route('invitation.index', ['role' => 'teacher'])}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="icon icon-plus bg-primary p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-primary mb-0 mt-3">Invite a Teacher</div>
                                </div>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    @else   
        @include('proofing.franchise.flash-error')
    @endif
@endsection

@section('js')

@stop
