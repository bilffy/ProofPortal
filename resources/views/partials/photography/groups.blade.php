@php
    use App\Helpers\SchoolContextHelper;
    use App\Helpers\PhotographyHelper;
    use App\Services\ImageService;

    $imageService = new ImageService();
    $school = SchoolContextHelper::getSchool();
    $schoolKey = $school->schoolkey ?? '';

    $groupYearOptions = $imageService->getAllYears()->toArray();
    $defaultSeasonId = $groupYearOptions[0]->ts_season_id;
    foreach ($groupYearOptions as $option) {
        $yearOptions[$option->ts_season_id] = $option->Year;
    }
    
    $season = $defaultSeasonId;
    $category = PhotographyHelper::TAB_GROUPS;
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
            <x-form.select context="groups_class" :options="[]" class="mb-4" multiple>Classes</x-form.select>
        </div>
        <livewire:photography.photo-grid :$category :$season :$schoolKey :key="$key"/>
        {{--@livewire('photography.photo-grid', [
            'category' => $PhotographyHelper::TAB_GROUPS,
            'season' => $defaultSeasonId,
            'schoolKey' => $schoolKey,
        ], key('photo-grid-groups-' . $schoolKey))--}}
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

    window.performGroupSearch = performGroupSearch;
    window.addEventListener('load', () => {
        $('#select_groups_year').select2({placeholder: "Select a Year"});
        $('#select_groups_year').change(updateGridView);
        $('#select_groups_view').select2({placeholder: "Select a View", minimumResultsForSearch: Infinity});
        $('#select_groups_view').change(updateGridView);
        $('#select_groups_class').select2({placeholder: "Select a Class"});
        $('#select_groups_class').change(updateGridView);
    });
    
    Livewire.on('EV_UPDATE_FILTER_DATA', (data) => {
        if (data[0] == 'GROUPS') {
            updateSelect2Options(`#select_groups_${data[1]}`, data[2]);
        }
    });
</script>
@endpush
