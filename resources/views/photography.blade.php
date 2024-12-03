@extends('layouts.authenticated')

@section('content')
    <div class="container3 p-4">
        <x-tabs.tabContainer tabsWrapper="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                <x-tabs.tab id="configure" isActive="{{$currentTab == 'configure'}}" route="{{route('photography.configure')}}">Configure</x-tabs.tab>
            @endrole
            <x-tabs.tab id="portraits" isActive="{{$currentTab == 'portraits'}}" route="{{route('photography.portraits')}}">Portraits</x-tabs.tab>
            <x-tabs.tab id="groups" isActive="{{$currentTab == 'groups'}}" route="{{route('photography.groups')}}">Groups</x-tabs.tab>
            <x-tabs.tab id="others" isActive="{{$currentTab == 'others'}}" route="{{route('photography.others')}}">Others</x-tabs.tab>
            <div class="absolute right-2 h-full flex align-middle justify-center items-center gap-4">
                <x-button.primary hollow class="border-none">Clear Selection</x-button.primary>
                <x-button.primary>Download Selected</x-button.primary>
            </div>
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
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-button');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const url = tab.getAttribute('href');
                history.pushState({ path: url }, '', url);
            });
        });
    });
</script>
@endpush
