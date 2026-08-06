<div>
    <h5 class="text-xl font-bold text-gray-900">Season Enable</h5>
    <p class="pt-2 text-sm text-gray-600">Enable or disable seasons to control job visibility in the portal and proofing</p>

    <div class="w-full mt-4 max-h-96 overflow-y-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="sticky top-0 bg-white z-10 border-b border-gray-200">
                <tr>
                    <th class="p-3 font-semibold">Season</th>
                    <th class="p-3 font-semibold">Portal Status</th>
                    <th class="p-3 font-semibold">Proofing Status</th>
                    <th class="p-3 font-semibold">Enable/Disable For Portal</th>
                    <th class="p-3 font-semibold">Enable/Disable For Proofing</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($seasons as $season)
                    @php
                        $key = (string) $season->id;
                        $isEnabledPortal = (int) $season->show_in_portal === 1;
                        $isEnabledProofing = (int) $season->show_in_proofing === 1;
                        $portalTagColor = $isEnabledPortal ? 'border-[#009236] text-[#009236]' : 'border-[#E87F54] text-[#E87F54]';
                        $proofingTagColor = $isEnabledProofing ? 'border-[#009236] text-[#009236]' : 'border-[#E87F54] text-[#E87F54]';
                    @endphp
                    <tr class="border-b border-gray-100" wire:key="season-row-{{ $key }}">
                        <td class="p-3">
                            <div class="font-bold text-base">{{ $season->code }}</div>
                            <div class="text-sm text-gray-600">Manage visibility for the {{ $season->code }} season.</div>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-1" wire:key="portal-status-{{ $key }}-{{ $isEnabledPortal ? '1' : '0' }}">
                                <x-tag.base class="{{ $portalTagColor }}" hollow>
                                    {{ $isEnabledPortal ? 'Enabled' : 'Disabled' }}
                                </x-tag.base>
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-1" wire:key="proofing-status-{{ $key }}-{{ $isEnabledProofing ? '1' : '0' }}">
                                <x-tag.base class="{{ $proofingTagColor }}" hollow>
                                    {{ $isEnabledProofing ? 'Enabled' : 'Disabled' }}
                                </x-tag.base>
                            </div>
                        </td>
                        <td class="p-3">
                            {{-- Inline transform: translate-x-* is not in the compiled CSS for this app --}}
                            <button
                                type="button"
                                wire:key="portal-switch-{{ $key }}-{{ $isEnabledPortal ? '1' : '0' }}"
                                wire:click="toggleSeasonPortal('{{ $key }}')"
                                wire:loading.attr="disabled"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 items-center cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-100 {{ $isEnabledPortal ? 'bg-primary' : 'bg-neutral-400' }}"
                                role="switch"
                                aria-checked="{{ $isEnabledPortal ? 'true' : 'false' }}"
                            >
                                <span
                                    class="pointer-events-none absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow"
                                    style="transform: translateX({{ $isEnabledPortal ? '1.25rem' : '0' }}); transition: transform 200ms ease-in-out;"
                                ></span>
                            </button>
                        </td>
                        <td class="p-3">
                            <button
                                type="button"
                                wire:key="proofing-switch-{{ $key }}-{{ $isEnabledProofing ? '1' : '0' }}"
                                wire:click="toggleSeasonProofing('{{ $key }}')"
                                wire:loading.attr="disabled"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 items-center cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-100 {{ $isEnabledProofing ? 'bg-primary' : 'bg-neutral-400' }}"
                                role="switch"
                                aria-checked="{{ $isEnabledProofing ? 'true' : 'false' }}"
                            >
                                <span
                                    class="pointer-events-none absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow"
                                    style="transform: translateX({{ $isEnabledProofing ? '1.25rem' : '0' }}); transition: transform 200ms ease-in-out;"
                                ></span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm font-semibold text-gray-500">
                            No Seasons Found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
