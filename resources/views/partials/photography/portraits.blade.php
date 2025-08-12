@php
    use App\Helpers\SchoolContextHelper;
    use App\Helpers\PhotographyHelper;
    use App\Services\ImageService;

    $category = PhotographyHelper::TAB_PORTRAITS;
    $imageService = new ImageService();
    $school = SchoolContextHelper::getSchool();
    $schoolKey = $school->schoolkey ?? '';

    $portraitYearOptions = $imageService->getAvailableYearsForSchool($schoolKey, $category)->toArray();
    $defaultSeasonId = empty($portraitYearOptions) ? 0 : $portraitYearOptions[0]->ts_season_id;
    $yearOptions = [];
    foreach ($portraitYearOptions as $option) {
        $yearOptions[$option->ts_season_id] = $option->Year;
    }

    $season = $defaultSeasonId;
    $key = "photo-grid-portraits-$schoolKey";
@endphp
<div class="relative">
    <div class="flex flex-row gap-4">
        <div class="w-[200px]">
            <div class="mb-4 relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <x-icon icon="search"/>
                </div>
                <input
                    id="image-search-portraits"
                    type="search"
                    class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300 border-0"
                    placeholder="Search..."
                    onkeypress="if(event.key === 'Enter') { window.performPortaitSearch(event); }"
                    oninput="window.performPortaitSearch(event);"
                />
            </div>
            <x-form.select context="portraits_year" :options="$yearOptions" class="mb-4">Year</x-form.select>    
            <x-form.select context="portraits_view" :options="[]" class="mb-4">View</x-form.select>
            <x-form.select context="portraits_class" :options="[]" class="mb-4" multiple>Class/Group</x-form.select>
        </div>
        @if ($season)
            <livewire:photography.photo-grid :$category :$season :$schoolKey :key="$key"/>
        @else
            @php
                $isFranchiseLevel = Auth::user()->isFranchiseLevel();
                $level = $isFranchiseLevel ? 'franchise_level' : 'school_level';
                $color = $isFranchiseLevel ? 'alert' : 'neutral-300';
            @endphp
            <div class="w-full text-center text-{{ $color  }}">{{ $photographyMessages['no_jobs'][$level] }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script type="module">
    function performPortaitSearch(event) {
        if (event.key === 'Enter') {
            Livewire.dispatch('EV_UPDATE_SEARCH', { term: event.currentTarget.value, category: 'PORTRAITS' });
        } else if (!event.currentTarget.value) {
            Livewire.dispatch('EV_UPDATE_SEARCH', { term: '', category: 'PORTRAITS' });
        }
    }
    function updateGridView(event) {
        const selectedYear = $('#select_portraits_year').val();
        const selectedView = $('#select_portraits_view').val();
        const selectedClass = $('#select_portraits_class').val();
        
        resetImages();
        Livewire.dispatch('EV_UPDATE_FILTER', {year: selectedYear, view: selectedView, class: selectedClass, category: 'PORTRAITS'});
    };
    function updateSelect2Options(selector, options) {
        const select = $(selector);
        select.empty(); // Clear existing options

        $.each(options, function(value, text) {
            select.append(new Option(text, value));
        });
    }
    function disableForms() {
        $('#select_portraits_year').prop('disabled', true);
        $('#select_portraits_view').prop('disabled', true);
        $('#select_portraits_class').prop('disabled', true);
        $('#image-search-portraits').prop('disabled', true);
        updateDownloadsForPortraits();
    }
    function updateDownloadsForPortraits(activeTab = '') {
        let tab = activeTab;
        if (isEmptyString(tab)) {
            const activeTabEl = document.querySelector('.tab-button[aria-selected="true"]');
            tab = activeTabEl ? activeTabEl.id : null;
        }
        const season = "{{ $season }}";
        $('#btn-download').prop('disabled', (season == 0 && 'portraits-tab' == tab));
    }

    window.updateDownloadsForPortraits = updateDownloadsForPortraits;

    window.performPortaitSearch = performPortaitSearch;
    window.addEventListener('load', () => {
        $('#select_portraits_year').select2({placeholder: "Select a Year"});
        $('#select_portraits_year').change(updateGridView);
        $('#select_portraits_view').select2({placeholder: "Select a View", minimumResultsForSearch: Infinity});
        $('#select_portraits_view').change(updateGridView);
        $('#select_portraits_class').select2({placeholder: "All"});
        $('#select_portraits_class').change(updateGridView);

        @if ($season == 0)
            disableForms();
        @endif
    });

    Livewire.on('EV_UPDATE_FILTER_DATA', (data) => {
        if (data[0] == 'PORTRAITS') {
            updateSelect2Options(`#select_portraits_${data[1]}`, data[2]);
        }
    });
</script>
@endpush