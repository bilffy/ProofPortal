@extends('layouts.authenticated')

@section('content')
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Manage Users</h3>
        <div class="flex justify-center">
            @include('partials.users.forms.search')
            <div class="ml-4 mr-4 border-r-2 border-[#D9DDE2] my-3"></div>
            <x-button.primary onclick="window.location='{{ route('users.create') }}'">Add New User</x-button.primary>
        </div>
    </div>
    @include('partials.users.usersList', ['users' => $results])
    <div class="w-full flex items-center justify-center py-4">
        {{ $results->onEachSide(1)->links('components.pagination') }}
    </div>
@endsection

@push('scripts')
<script type="module">
    let users = {{ Js::from($results) }}
    // import { NAV_TABS } from "{{ Vite::asset('resources/js/helpers/constants.helper.ts') }}"
    // import { getCurrentNav, getNavTabId } from "{{ Vite::asset('resources/js/helpers/utils.helper.ts') }}"
    // window.addEventListener("load", function () {
    //     console.log("LOADED");
    // }, false);
    console.log({users});
</script>

@endpush
