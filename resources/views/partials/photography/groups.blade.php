@php
    use App\Helpers\SchoolContextHelper;
    use App\Helpers\PhotographyHelper;
    use App\Services\ImageService;

    $category = PhotographyHelper::TAB_GROUPS;
    $imageService = new ImageService();
    $school = SchoolContextHelper::getSchool();
    $schoolKey = $school->schoolkey ?? '';

    $groupYearOptions = $imageService->getAvailableYearsForSchool($schoolKey, $category)->toArray();
    $defaultSeasonId = empty($groupYearOptions) ? 0 : $groupYearOptions[0]->ts_season_id;
    $yearOptions = [];
    foreach ($groupYearOptions as $option) {
        $yearOptions[$option->ts_season_id] = $option->Year;
    }
    
    $season = $defaultSeasonId;
    $key = "photo-grid-groups-$schoolKey";
@endphp

<div class="relative">
    <div class="flex flex-row gap-4">
        <div class="w-[200px]">
            <div class="mb-4 relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <x-icon icon="search"/>
                </div>
                <input
                    id="image-search-groups"
                    type="search"
                    class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300 border-0"
                    placeholder="Search..."
                    onkeypress="if(event.key === 'Enter') { window.performGroupSearch(event); }"
                    oninput="window.performGroupSearch(event);"
                />
            </div>
            <x-form.select context="groups_year" :options="$yearOptions" class="mb-4">Year</x-form.select>    
            <x-form.select context="groups_view" :options="[]" class="mb-4">View</x-form.select>
            <x-form.select context="groups_class" :options="[]" class="mb-4" multiple>Class/Group</x-form.select>
        </div>
        @if ($season)
            <livewire:photography.photo-grid :$category :$season :$schoolKey :key="$key"/>
        @else
            @php
                $isFranchiseLevel = Auth::user()->isFranchiseLevel();
                $level = $isFranchiseLevel ? 'franchise_level' : 'school_level';
                $color = $isFranchiseLevel ? 'alert' : 'neutral-300';
            @endphp
            <div class="w-full text-center text-{{ $color  }}">{{ config('app.dialog_config.photography.no_jobs.' . $level) }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script type="module">
    function performGroupSearch(event) {
        if (event.key === 'Enter') {
            Livewire.dispatch('EV_UPDATE_SEARCH', { term: event.currentTarget.value, category: 'GROUPS' });
        } else if (!event.currentTarget.value) {
            Livewire.dispatch('EV_UPDATE_SEARCH', { term: '' , category: 'GROUPS' });
        }
    }
    function updateGridView(event) {
        const selectedYear = $('#select_groups_year').val();
        const selectedView = $('#select_groups_view').val();
        const selectedClass = $('#select_groups_class').val();
        
        resetImages();
        Livewire.dispatch('EV_UPDATE_FILTER', {year: selectedYear, view: selectedView, class: selectedClass, category: 'GROUPS'});
    };
    function updateSelect2Options(selector, options) {
        const select = $(selector);
        select.empty(); // Clear existing options

        $.each(options, function(value, text) {
            select.append(new Option(text, value));
        });
    }
    function disableForms() {
        $('#select_groups_year').prop('disabled', true);
        $('#select_groups_view').prop('disabled', true);
        $('#select_groups_class').prop('disabled', true);
        $('#image-search-groups').prop('disabled', true);
        updateDownloadsForGroups();
    }
    function updateDownloadsForGroups() {
        @if ($season == 0)
            $('#btn-download').prop('disabled', true);
        @else
            $('#btn-download').prop('disabled', false);
        @endif
    }

    window.updateDownloadsForGroups = updateDownloadsForGroups;

    window.performGroupSearch = performGroupSearch;
    window.addEventListener('load', () => {
        $('#select_groups_year').select2({placeholder: "Select a Year"});
        $('#select_groups_year').change(updateGridView);
        $('#select_groups_view').select2({placeholder: "Select a View", minimumResultsForSearch: Infinity});
        $('#select_groups_view').change(updateGridView);
        $('#select_groups_class').select2({placeholder: "All"});
        $('#select_groups_class').change(updateGridView);
        
        @if ($season == 0)
            disableForms();
        @endif
    });
    
    Livewire.on('EV_UPDATE_FILTER_DATA', (data) => {
        if (data[0] == 'GROUPS') {
            updateSelect2Options(`#select_groups_${data[1]}`, data[2]);
        }
    });
</script>
@endpush
