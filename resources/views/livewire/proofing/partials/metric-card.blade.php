<div class="w-full h-full min-h-[6.5rem] rounded-md border border-neutral-200 {{ $card['tint'] }} {{ $card['accent'] }} border-l-4 p-4 flex flex-col gap-2 box-border shadow-sm hover:shadow-md transition-shadow duration-150">
    <div class="flex items-center justify-between gap-2">
        <span class="text-sm font-semibold text-gray-700 leading-snug">{{ $card['label'] }}</span>
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/80 text-gray-500 shrink-0">
            <x-icon class="text-sm" icon="{{ $card['icon'] }}" />
        </span>
    </div>
    <div class="text-3xl font-bold tracking-tight text-gray-900" wire:loading.class="opacity-50">
        {{ number_format($card['value']) }}
    </div>
    @if (!empty($card['percent']))
        <div class="mt-auto">
            <div class="h-1.5 w-full rounded-full bg-white/70 overflow-hidden">
                <div class="h-full rounded-full {{ $card['bar'] ?? 'bg-primary' }}" style="width: {{ min(100, max(0, $card['percent'])) }}%"></div>
            </div>
            <p class="text-[10px] text-gray-500 mt-1">{{ $card['percent'] }}% of group</p>
        </div>
    @endif
</div>
