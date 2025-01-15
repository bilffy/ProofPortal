@php
    use App\Helpers\SchoolContextHelper;
    use App\Services\ImageService;

    $imageService = new ImageService();
    $school = SchoolContextHelper::getSchool();
    $schoolKey = $school->schoolkey ?? '';

    $otherYearOptions = $imageService->getAllYears()->toArray();
    $defaultSeasonId = $otherYearOptions[0]->ts_season_id;
    foreach ($otherYearOptions as $option) {
        $yearOptions[$option->ts_season_id] = $option->Year;
    }
@endphp

<div class="relative">
    {{--<div class="absolute top-[-77px] right-2">
        <x-button.primary>Download All</x-button.primary>
    </div>--}}

    <div class="flex flex-row gap-4">
        <div class="w-[200px]">
            <div class="mb-4 relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <x-icon icon="search"/>
                </div>
                <input
                    id="image-search"
                    type="search"
                    class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300 border-0"
                    placeholder="Search..."
                    onkeypress="if(event.key === 'Enter') { window.performOtherSearch(event); }"
                />
            </div>
            <x-form.select context="others_year" :options="$yearOptions" class="mb-4">Year</x-form.select>    
            <x-form.select context="others_view" :options="[]" class="mb-4">View</x-form.select>
            <x-form.select context="others_class" :options="[]" class="mb-4" multiple>Classes</x-form.select>
        </div>

        {{--@livewire('photography.photo-grid', [
            'category' => $PhotographyHelper::TAB_OTHERS,
            'season' => $defaultSeasonId,
            'schoolKey' => $schoolKey,
        ])--}}
    </div>
</div>

@push('scripts')
<script type="module">
    function performOtherSearch(event) {
        if (event.key === 'Enter') {
            Livewire.dispatch('EV_UPDATE_SEARCH', { term: event.currentTarget.value });
        } else if (!event.currentTarget.value) {
            Livewire.dispatch('EV_UPDATE_SEARCH', { term: '' });
        }
    }
    function updateGridView(event) {
        const selectedYear = $('#select_others_year').val();
        const selectedView = $('#select_others_view').val();
        const selectedClass = $('#select_others_class').val();
        
        Livewire.dispatch('EV_UPDATE_FILTER', {year: selectedYear, view: selectedView, class: selectedClass});
    };
    function updateSelect2Options(selector, options) {
        const select = $(selector);
        select.empty(); // Clear existing options

        $.each(options, function(value, text) {
            select.append(new Option(text, value));
        });
    }

    window.addEventListener('load', () => {
        $('#select_others_year').select2({placeholder: "Select a Year"});
        $('#select_others_year').change(updateGridView);
        $('#select_others_view').select2({placeholder: "Select a View", minimumResultsForSearch: Infinity});
        $('#select_others_view').change(updateGridView);
        $('#select_others_class').select2({placeholder: "Select a Class"});
        $('#select_others_class').change(updateGridView);
    });

    Livewire.on('EV_UPDATE_FILTER_DATA', (data) => {
        updateSelect2Options(`#select_others_${data[0]}`, data[1]);
    });
</script>
@endpush
