<div class="w-full">
    <h5 class="text-xl font-bold text-gray-900">Archive Jobs</h5>
    <p class="pt-2 text-sm text-gray-600">Bulk-archive all jobs for the selected season.</p>

    <div class="mt-5 flex flex-col sm:flex-row sm:items-end gap-4">
        <div class="flex flex-col gap-2 min-w-[16rem]" wire:ignore>
            <label for="archive-jobs-season" class="text-sm font-semibold text-gray-700">Season</label>
            <select
                id="archive-jobs-season"
                class="w-64 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block p-2.5 focus:ring-0"
            >
                <option value="">Choose a season</option>
                @foreach ($seasons as $season)
                    <option value="{{ $season->ts_season_id }}" @selected((string) $selectedTsSeasonId === (string) $season->ts_season_id)>
                        {{ $season->code }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <button
                type="button"
                onclick="window.runSeasonAction('archive-jobs-season', 'archiveJobsForSeason')"
                wire:loading.attr="disabled"
                wire:target="archiveJobsForSeason"
                class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-xs font-semibold uppercase tracking-wider rounded shadow hover:bg-opacity-95 active:bg-opacity-90 disabled:opacity-50 transition duration-150 ease-in-out"
            >
                <span wire:loading.remove wire:target="archiveJobsForSeason">Archive Jobs</span>
                <span wire:loading wire:target="archiveJobsForSeason">Archiving...</span>
            </button>
        </div>
    </div>

    @error('selectedTsSeasonId')
        <p class="mt-2 text-sm rounded-md px-3 py-2" style="background-color: #F8D0D0; color: #DA1414; border: 1px solid #F0A1A1;">{{ $message }}</p>
    @enderror

    @if ($statusMessage)
        <div
            class="mt-4 text-sm rounded-md px-3 py-2 border"
            @if ($statusType === 'success')
                style="background-color: #B6DBBF; color: #226C34; border-color: #93C49E;"
            @else
                style="background-color: #F8D0D0; color: #DA1414; border-color: #F0A1A1;"
            @endif
        >
            {{ $statusMessage }}
        </div>
    @endif
</div>
