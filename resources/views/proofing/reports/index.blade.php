@extends('proofing.layouts.master')
@section('title', 'MyReports')

@section('css')
    <style>
        .bootstrap {
            /* Reset Tailwind overrides on Bootstrap classes */
            .pagination {
                font-family: inherit;
                display: flex;
                justify-content: center;
            }
            .pagination .page-item .page-link {
                color: #007bff; /* Bootstrap primary color */
                border-radius: 0.25rem;
            }
        }
    </style>
@stop

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">{{ __('Reports') }}</h1>
        </div>
    </div>

    @if(session('awaitApprovalSubjectChangesCount') > 0)
        <p class="mb-4">
            <span class="alert alert-danger p-2">
                There are {{ session('awaitApprovalSubjectChangesCount') }} unapproved changes in this Job that will not show in your reports.
                Please contact a Photo Coordinator and have them approve the changes before running reports.
            </span>
        </p>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="reports index">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ __('Report Records') }}
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <!-- Table Headings-->
                                <tr>
                                    <th class="idx-id" scope="col">
                                        @if ($sort == 'id')
                                            <i class="fa fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'direction' => ($sort == 'id' && $direction == 'asc') ? 'desc' : 'asc']) }}">
                                            ID
                                        </a>
                                    </th>
                                    <th class="idx-name" scope="col">
                                        @if ($sort == 'name')
                                            <i class="fa fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => ($sort == 'name' && $direction == 'asc') ? 'desc' : 'asc']) }}">
                                            Name
                                        </a>
                                    </th>
                                    <th class="idx-description" scope="col">
                                        @if ($sort == 'description')
                                            <i class="fa fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'description', 'direction' => ($sort == 'description' && $direction == 'asc') ? 'desc' : 'asc']) }}">
                                            Description
                                        </a>
                                    </th>
                                    <th scope="col" class="actions">{{ __('Actions') }}</th>
                                </tr>

                                <!-- Record Filters -->
                                <tr>
                                    <form method="GET" action="{{ route('reports') }}" id="record_filter">
                                        <th class="idx-id" scope="col">
                                            <input type="text" name="record_filter[id]" class="form-control form-filter input-sm" 
                                                value="{{ request()->input('record_filter.id', '') }}" placeholder="ID">
                                        </th>
                                        <th class="idx-name" scope="col">
                                            <input type="text" name="record_filter[name]" class="form-control form-filter input-sm" 
                                                value="{{ request()->input('record_filter.name', '') }}" placeholder="Name">
                                        </th>
                                        <th class="idx-description" scope="col">
                                            <input type="text" name="record_filter[description]" class="form-control form-filter input-sm" 
                                                value="{{ request()->input('record_filter.description', '') }}" placeholder="Description">
                                        </th>
                                        <th scope="col" class="actions">
                                            <button type="submit" name="action" value="filter" class="btn btn-sm btn-primary">Filter</button>
                                            <a href="{{ route('reports') }}" class="btn btn-sm btn-primary">Clear</a>
                                            {{-- <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#indexFilterHelp">?</button> --}}
                                        </th>
                                    </form>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reports as $report)
                                    <tr>
                                        <td class="idx-id">{{ $loop->iteration }}</td>
                                        <td class="idx-name">{{ $report->name }}</td>
                                        <td class="idx-description">{{ $report->description }}</td>
                                        <td class="actions">
                                            <a href="{{ route('reports.run', ['query' => $report->query]) }}">{{ __('Run') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    
                        <div class="paginator">
                            <ul class="pagination">
                                {{ $reports->links('proofing.layouts.pagination-custom') }} <!-- Use the custom pagination view -->
                            </ul>
                        
                            <p>
                                {{ __('Page :page of :pages, showing :current record(s) out of :count total', [
                                    'page' => $reports->currentPage(),
                                    'pages' => $reports->lastPage(),
                                    'current' => $reports->count(),
                                    'count' => $reports->total(),
                                ]) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('proofing.modals.index_filter_help')

@endsection
