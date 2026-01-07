@extends('proofing.layouts.master')
@section('title', 'Proofing | Open Season')

@section('content')
@php
    use Illuminate\Support\Facades\Crypt;
@endphp
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            {{ __('Seasons') }}
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="dashboard openSeason">
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-align-justify"></i> {{ __('Please pick a Season to open.') }}
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Season Key') }}</th>
                                <th scope="col">{{ __('Year') }}</th>
                                <th scope="col">{{ __('Default Season') }}</th>
                                <th scope="col">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allSeasons as $season)
                                <tr>
                                    <td class="idx-season-key">{{ $season->ts_season_key }}</td>
                                    <td class="idx-code">{{ $season->code }}</td>
                                    <td class="idx-is-default">{{ $season->is_default ? 'Yes' : 'No' }}</td>
                                    <td class="actions">
                                        @php
                                            // Use Laravel's encrypt helper or a custom method
                                            $hash = Crypt::encryptString($season->ts_season_id);
                                        @endphp
                                        
                                        <form action="{{ route('dashboard.passSeason') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="season_key_hash" value="{{ $hash }}">
                                            <button type="submit" class="btn btn-link p-0">
                                                {{ __('Open :code Season', ['code' => $season->code]) }}
                                            </button>
                                        </form>
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
@endsection
