<div class="w-full overflow-x-auto">
    <div class="overflow-hidden min-w-max">
        <h5 class="text-xl font-bold dark:text-white">Season Enable</h5>
        <p class="pt-3">Enable or Disable the season. Jobs from the enabled season will be visible in the portal and proofing.</p>
        <div class="w-full flex flex-row gap-4 mt-4 max-h-96 overflow-y-auto rounded-[4px] border-[1px]">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="sticky top-0 bg-white z-10">
                    <tr>
                        <x-table.headerCell id="header-season" class="p-0.5 border-b-[1px]" clickable="{{false}}">Season</x-table.headerCell>
                        <x-table.headerCell id="header-status" class="p-0.5 border-b-[1px]" clickable="{{false}}">Status</x-table.headerCell>
                        <x-table.headerCell id="header-able" class="p-0.5 border-b-[1px]" clickable="{{false}}">Enable/Disable</x-table.headerCell>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($seasons as $season)
                        <tr class="border-b border-[#E6E7E8] last:border-b-0">
                            <x-table.cell class="grid grid-rows-2 gap-1 border-none">
                                <span class="text-lg font-bold">{{ $season->code }}</span>
                                <span class="text-sm">Manage visibility for the {{ $season->code }} season in the portal.</span>
                            </x-table.cell>
                            <x-table.cell class="w-1/4 border-none">
                                <div class="flex flex-wrap gap-1">
                                    @php
                                        $isEnabled = $seasonStates[$season->id] ?? false;
                                        $tagText = $isEnabled ? 'Enabled' : 'Disabled';
                                        $tagColor = $isEnabled ? 'border-[#009236] text-[#009236]' : 'border-[#E87F54] text-[#E87F54]';
                                    @endphp
                                     <x-tag.base class="{{ $tagColor }}" hollow>{{ $tagText }}</x-tag.base>
                                </div>
                            </x-table.cell>
                            <x-table.cell class="w-1/4 border-none">
                                <div class="flex flex-wrap gap-1">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input
                                                type="checkbox"
                                                wire:model="seasonStates.{{ $season->id }}"
                                                wire:click="toggleSeason('{{ $season->id }}')"
                                                class="sr-only peer">
                                        <div class="relative w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                            </x-table.cell>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
