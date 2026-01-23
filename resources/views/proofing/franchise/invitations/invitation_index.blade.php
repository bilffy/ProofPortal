@extends('proofing.layouts.master')
@section('title', 'Invitations')

@section('css')
@stop

@php
    use Illuminate\Support\Facades\Crypt;
@endphp

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
        </div>
    
        @if($role === 'photocoordinator' || $role === 'teacher')
            @php
                $continue = 0;
            @endphp
            @if (Auth::user()->hasRole(['teacher', 'photocoordinator']))
                @if ($myActiveReviewSchoolsCount === 0 && count($mySchools) === 0)
                    <div class="alert alert-info">
                        <p class="mb-0">Proofing has been completed for this year. Please contact MSP if you believe this to be an issue.</p>
                    </div>
                    @php
                        $continue = 1;
                    @endphp
                @elseif ($myActiveReviewSchoolsCount === 0 && count($mySchools) > 0)
                    <div class="alert alert-info">
                        <p class="mb-0">
                            You are linked to the following Schools, however, they are all marked as inactive for Proofing.
                            Please contact MSP if you believe this to be an issue.
                        </p>
                        <ul>
                            @foreach ($mySchools as $mySchool)
                                <li>{{ $mySchool->name }} <strong>{{ in_array($mySchool->id, $allActiveSchools) ? '' : '(Inactive)' }}</strong></li>
                            @endforeach
                        </ul>
                    </div>
                @elseif ($myActiveReviewSchoolsCount > 0 && $myActiveReviewSchoolsCount < count($mySchools))
                    <div class="alert alert-info">
                        <p class="mb-0">
                            You are linked to the following Schools, however, some are marked as inactive for Proofing.
                            Please contact MSP if you believe this to be an issue.
                        </p>
                        <ul>
                            @foreach ($mySchools as $mySchool)
                                <li>{{ $mySchool->name }} <strong>{{ in_array($mySchool->id, $allActiveSchools) ? '' : '(Inactive)' }}</strong></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
            @php
                if($role === 'photocoordinator')
                {
                    $hasRolesData = ['superadmin', 'admin', 'franchise', 'photocoordinator'];
                }elseif($role === 'teacher')
                {
                    $hasRolesData = ['superadmin', 'admin', 'franchise', 'photocoordinator', 'teacher'];
                }
            @endphp
            {{-- @if(Auth::user()->hasRole($hasRolesData)) --}}
            @if($continue === 0)
                <div class="row">
                    <div class="col-md-6 m-auto">
                        <div class="card">
                            <div class="card-header">
                                @if($role === 'photocoordinator')
                                    <legend>Invite a Photo-Coordinator</legend>
                                @elseif($role === 'teacher')
                                    <legend>Invite a Teacher</legend>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($role === 'photocoordinator')
                                    <p>
                                        Create a new User as a Photo-Coordinator for a School.
                                        As a Member of that School, a Photo-Coordinator can
                                        perform Administration tasks such as inviting Teachers and viewing Subjects.
                                        Use the 'Single Invitation' button to invite one Photo-Coordinator.
                                        Use the 'Multi Invitation' button to copy-and-paste from an Excel document.
                                    </p>
                                @elseif($role === 'teacher')
                                    <p>
                                        Create a new User as a Teacher for a School.
                                        As a Member of that School, a Teacher can review Student photos and spelling.
                                        Use the 'Single Invitation' button to invite one Teacher.
                                        Use the 'Multi Invitation' button to copy-and-paste from an Excel document.
                
                                    </p>
                                @endif
                                <div class="row">
                                    <div class="col-12">
                                        <table class="table table-responsive-sm table-sm">
                                            <thead>
                                                <tr>
                                                    <th>School</th>
                                                    <th>&nbsp;</th>
                                                    <th>&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                    @php
                                                        $jobKeyHash = Crypt::encryptString($selectedJob->ts_jobkey);
                                                        $activeInactiveCssClass = $selectedJob->jobsync_status_id === $syncStatus ? 'active-school' : 'inactive-school';
                                                    @endphp
                                                    <tr class="{{ $activeInactiveCssClass }}">
                                                        <td>
                                                            <div class="mt-1 ml-1">
                                                                {{ $selectedJob->ts_jobname }} ({{ $selectedSeason->code }})
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="{{ URL::signedRoute('invitations.inviteSingle', ['hash' => $jobKeyHash, 'role' => $role]) }}" 
                                                               class="btn btn-sm btn-primary btn-block">Single</a>
                                                        </td>
                                                        <td>
                                                            <a href="{{ URL::signedRoute('invitations.inviteMulti', ['hash' => $jobKeyHash, 'role' => $role]) }}" 
                                                               class="btn btn-sm btn-primary btn-block">Multi</a>
                                                        </td>
                                                    </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            {{-- @endif --}}
        @endif
    @else   
        @include('proofing.franchise.flash-error')
    @endif
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $(".show-all").click(function () {
                $(".active-school").show();
                $(".inactive-school").show();
            });

            $(".show-active").click(function () {
                $(".active-school").show();
                $(".inactive-school").hide();
            });

            $(".show-inactive").click(function () {
                $(".active-school").hide();
                $(".inactive-school").show();
            });
        });
    </script>
@stop
