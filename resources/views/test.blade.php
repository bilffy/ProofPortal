@extends('layouts.testLayout')

@section('content')
    <div class="container3 border-success border-2 p-4">
        <h3>BLADE TEST CONTENT HERE!</h3>
        <div class="col-md-12">
            <p>Testing... BLADE!</p>
        </div>
    </div>
    <x-button.baseButton type="submit" textColor="#000" >Test</x-button.baseButton>
    <x-button.baseButton bg="bg-black" >Test2</x-button.baseButton>
@endsection
