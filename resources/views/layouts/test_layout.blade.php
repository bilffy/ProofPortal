@extends('app2')

@section('blade_layout')
    <auth-layout>
        <h2>BLADE LAYOUT<h2>
        <div class="container">
            @yield('content')
        </div>
        <test-component><p>whaaat</p></test-component>
        <div class="container2">
            <div class="col-md-12">
                <h2>From the blade layout container2</h2>
                <p>Test test test test.</p>
            </div>
        </div>
    </auth-layout>
@endsection
    