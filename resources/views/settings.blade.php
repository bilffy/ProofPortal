@php
    
@endphp

@extends('layouts.authenticated')

@section('content')
    <div id="settings-root" class="px-4">
        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">App Settings</h3>
            <div></div>
        </div>
        <div id="file-format-section" class="relative mb-8 gap-4">
            @livewire('settings.filename-format')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.feature-control')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.role-permission')
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
</script>
@endpush