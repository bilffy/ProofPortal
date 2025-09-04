<div class="relative overflow-x-auto">
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">School</h3>
        <div class="flex justify-center mr-1">
            <div class="relative max-w-md mx-auto">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <x-icon icon="search"/>
                </div>
                <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        wire:keydown.enter="performSearch" 
                        placeholder="Search schools..."
                        class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300 border-0"
                />
                <button type="button" class="absolute inset-y-0 end-0 flex items-center pe-3" wire:click="clearSearch">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <table class="w-full text-sm text-left rtl:text-right">
        <thead>
            <tr>
                <x-table.headerCell id="schoolkey" isLivewire="{{true}}" wireEvent="sortBy('schoolkey')" filterable="{{false}}">School Key</x-table.headerCell>
                <x-table.headerCell id="name" isLivewire="{{true}}" wireEvent="sortBy('name')" filterable="{{false}}">School Name</x-table.headerCell>
                <x-table.headerCell class="{{ $hideFranchise ? 'hidden' : '' }}" id="name" isLivewire="{{true}}" wireEvent="sortBy('franchise_name')" filterable="{{false}}">Franchise</x-table.headerCell>
            </tr>
        </thead>
        <tbody>
            @foreach ($schools as $school)
                <tr>
                    <x-table.cell class="w-1/4">
                        <a href="{{ route('school.view', ['hashedId' => $school->getHashedIdAttribute()]) }}">
                            {{ $school->schoolkey }}
                        </a>
                    </x-table.cell>
                    <x-table.cell>
                        <a href="{{ route('school.view', ['hashedId' => $school->getHashedIdAttribute()]) }}">
                            {{ $school->name }}
                        </a>
                    </x-table.cell>
                    <x-table.cell  class="{{ $hideFranchise ? 'hidden' : '' }}">
                        {{ $school->franchise_name }}
                    </x-table.cell>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="w-full flex items-center justify-center py-4">
        {{ $schools->links('vendor.livewire.pagination') }}
    </div>
</div>
