@extends('layouts.authenticated')

@section('content')
    <div class="container3 p-4">
        <h3 class="text-2xl mb-4">Dashboard</h3>
        @include('partials.dashboard.franchise')
    </div>
@endsection
