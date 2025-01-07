@extends('layouts.authenticated')

@section('content')
    <div x-data class="container3 p-4">
        <x-tabs.tabContainer tabsWrapper="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                <x-tabs.tab id="configure" isActive="{{$currentTab == 'configure'}}" route="{{route('photography.configure')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Configure</x-tabs.tab>
            @endrole
            <x-tabs.tab id="portraits" isActive="{{$currentTab == 'portraits'}}" route="{{route('photography.portraits')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Portraits</x-tabs.tab>
            <x-tabs.tab id="groups" isActive="{{$currentTab == 'groups'}}" route="{{route('photography.groups')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Groups</x-tabs.tab>
            <x-tabs.tab id="others" isActive="{{$currentTab == 'others'}}" route="{{route('photography.others')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Others</x-tabs.tab>
            @livewire('photography.download-selection', ['id' => 'downloads'])
        </x-tabs.tabContainer>
        <x-tabs.tabContentContainer id="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                <x-tabs.tabContent id="configure">
                    @include('partials.photography.configure')
                </x-tabs.tabContent>
            @endrole
            <x-tabs.tabContent id="portraits">
                @include('partials.photography.portraits')
            </x-tabs.tabContent>
            <x-tabs.tabContent id="groups">
                @include('partials.photography.groups')
            </x-tabs.tabContent>
            <x-tabs.tabContent id="others" isActive="{{true}}">
                @include('partials.photography.others')
            </x-tabs.tabContent>
        </x-tabs.tabContentContainer>
    </div>
@endsection

@push('scripts')
<script type="module">
    
    function updateDownloadSection() {
        const images =  JSON.parse(localStorage.getItem('selectedImages'));

        
    }

    function resetImages() {
        window.localStorage.setItem('selectedImages', JSON.stringify([]));
        updateDownloadSection();
    }

    resetImages();
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-button');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                // reset images selected
                resetImages();
                const url = tab.getAttribute('href');
                history.pushState({ path: url }, '', url);
            });
        });
    });
</script>
@endpush
