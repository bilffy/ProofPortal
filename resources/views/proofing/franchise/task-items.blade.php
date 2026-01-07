@extends('proofing.layouts.master')
@section('title', 'Proofing')

@section('css')

@stop

@section('content')

@if(Session::has('selectedJob') && Session::has('selectedSeason'))

    @php
        $selectedJob = session('selectedJob');
        $hash = Crypt::encryptString($selectedJob->ts_jobkey);
        $prooflocation = URL::signedRoute('my-folders-list', ['hash' => $hash]);
        $configjoblocation = URL::signedRoute('config-job', ['hash' => $hash]);
        $manageStaffsLocation = URL::signedRoute('invitation.manageStaffs', ['hash' => $hash]);
        $bulkUploadLocation = URL::signedRoute('bulkUpload.image', ['hash' => $hash, 'step' => 'upload']);
        $approveChangelocation = URL::signedRoute('subject-change.approveChange', ['hash' => $hash]);
        $awaitApproveChangeFranchiselocation = URL::signedRoute('subject-change-franchise.awaitApproveChange', ['hash' => $hash]);
        $awaitApproveChangeCoordinatorlocation = URL::signedRoute('subject-change-coordinator.awaitApproveChange', ['hash' => $hash]);
        $reviewStatuslocation = URL::signedRoute('folders.reviewStatus', ['hash' => $hash]);
    @endphp

    
    <div class="mt-3">
    </div>

    <div class="row">
        <div class="col-lg-12"> 
            <h1 class="page-header">
                <span class="display-4">{{ __("Hello ") }} @if(isset($user)) {{ $user['firstname'] }}! @endif {{ __("Let's get you started.") }}</span>
            </h1>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12">
            <p class="lead">
                You can perform the following Tasks...
            </p>
        </div>
        <div class="col-12">
            <div class="row">
                
                @if ($user->hasRole('Franchise') || $user->hasRole('Photo Coordinator') || $user->hasRole('Teacher'))
                    <div class="col-6 col-lg-3">
                        <a href="{{$prooflocation}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-mortar-board bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-success mb-0 mt-3">{{ __('Proof People') }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                @if ($user->hasRole('Franchise'))
                    <div class="col-6 col-lg-3">
                        <a href="{{$configjoblocation}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-gears bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-success mb-0 mt-3">{{ __('Configure Job') }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                
                @if ($user->hasRole('Franchise'))
                    <div class="col-6 col-lg-3">
                        <a href="{{$reviewStatuslocation}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-tasks bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-success mb-0 mt-3">{{ __('Change Proofing Statuses') }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                
                @if ($user->hasRole('Franchise') || $user->hasRole('Photo Coordinator'))
                    <div class="col-6 col-lg-3">
                        <a href="{{$manageStaffsLocation}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-user-circle bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-success mb-0 mt-3">{{ __('Manage Photo Coordinators & Teachers') }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                @if ($user->hasRole('Franchise'))
                    <div class="col-6 col-lg-3">
                        <a href="{{$bulkUploadLocation}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-image bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-success mb-0 mt-3">{{ __('Bulk Upload Group Images') }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                @if (Session::get('approvedSubjectChangesCount') !== 0 && Session::get('approvedSubjectChangesCount') !== null && $user->hasRole('Franchise') || $user->hasRole('Photo Coordinator'))
                <div class="col-6 col-lg-3">
                    <a href="{{$approveChangelocation}}">
                        <div class="card">
                            <div class="card-body p-3 clearfix">
                                <i class="fa fa-history bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <span class="badge badge-pill badge-success float-right pt-1 pb-1 pl-2 pr-2 text-lg-center">
                                        {{ session('approvedSubjectChangesCount') }}
                                    </span>
                                <div class="h5 text-success mb-0 mt-3">{{ __('View Changes') }}</div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
                {{-- Franchise section --}}
                @if (Session::get('awaitApprovalSubjectChangesCount') !== 0 && Session::get('awaitApprovalSubjectChangesCount') !== null && $user->hasRole('Franchise'))
                <div class="col-6 col-lg-3">
                    <a href="{{$awaitApproveChangeFranchiselocation}}">
                        <div class="card">
                            <div class="card-body p-3 clearfix">
                                <i class="fa fa-history bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <span class="badge badge-pill badge-danger float-right pt-1 pb-1 pl-2 pr-2 text-lg-center">
                                        {{ session('awaitApprovalSubjectChangesCount') }}
                                    </span>
                                <div class="h5 text-success mb-0 mt-3">{{ __('View Unapproved Changes') }}</div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
                {{-- Photo Co-ordinator section --}}
                @if (Session::get('awaitApprovalSubjectChangesCount') !== 0 && Session::get('awaitApprovalSubjectChangesCount') !== null && $user->hasRole('Photo Coordinator'))
                <div class="col-6 col-lg-3">
                    <a href="{{$awaitApproveChangeCoordinatorlocation}}">
                        <div class="card">
                            <div class="card-body p-3 clearfix">
                                <i class="fa fa-history bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <span class="badge badge-pill badge-danger float-right pt-1 pb-1 pl-2 pr-2 text-lg-center">
                                        {{ session('awaitApprovalSubjectChangesCount') }}
                                    </span>
                                <div class="h5 text-success mb-0 mt-3">{{ __('Approve Changes') }}</div>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
                @if ($user->hasRole('Franchise'))
                    <div class="col-6 col-lg-3">
                        <a href="{{ route('reports') }}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-file bg-success p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-success mb-0 mt-3">{{ __('Reports') }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($user->hasRole('Franchise') || $user->hasRole('Photo Coordinator'))
        {{-- @php
            $role = Auth::user()->getRole();
        @endphp --}}
        <div class="row mt-4 invitation-section">
            <div class="col-12">
                <p class="lead">
                    You can invite the following People...
                </p>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-6 col-lg-3">
                        <a href="{{route('invitation.index', ['role' => 'photocoordinator'])}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-plus-circle bg-primary p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-primary mb-0 mt-3">Invite a Photo-Coordinator</div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-6 col-lg-3">
                        <a href="{{route('invitation.index', ['role' => 'teacher'])}}">
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-plus-circle bg-primary p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h5 text-primary mb-0 mt-3">Invite a Teacher</div>
                                </div>
                            </div>
                        </a>
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

@stop

