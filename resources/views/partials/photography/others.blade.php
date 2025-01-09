@php
    use App\Helpers\SchoolContextHelper;
    use App\Services\ImageService;

    $imageService = new ImageService();
    $job = SchoolContextHelper::getSchoolJob();
    $tsSeasonId = $job->ts_season_id;
    $tsSchoolKey = $job->ts_schoolkey;

    $othersYearOptions = $imageService->getAllYears()->toArray();
    $othersViewOptions = $imageService->getFolderForView(
        $tsSeasonId, 
        $tsSchoolKey,
        '=',
        'SP'
    )->values()->toArray();
    $othersClassOptions = $imageService->getFoldersByTag(
        $tsSeasonId,
        $tsSchoolKey,
        'student',
        'is_visible_for_group'
    )->toArray();

    $yearOptions[0] = 'Select Year';
    $viewOptions['ALL'] = 'Select View';
    $classOptions['ALL'] = 'Select Class';
    
    foreach ($othersYearOptions as $option) {
        $yearOptions[$option->Year] = $option->Year;
    }
    foreach ($othersViewOptions as $option) {
        $viewOptions[$option->external_name] = $option->external_name;
    }
    foreach ($othersClassOptions as $option) {
        $classOptions[$option->ts_folderkey] = $option->ts_foldername;
    }
@endphp

<div>
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
                    onkeypress="if(event.key === 'Enter') { performSearch(event); }"
                />
            </div>
            <x-form.select context="others_year" :options="$yearOptions" class="mb-4">Year</x-form.select>    
            <x-form.select context="others_view" :options="$viewOptions" class="mb-4">View</x-form.select>
            <x-form.select context="others_class" :options="$classOptions" class="mb-4">Classes</x-form.select>
        </div>

        {{--@livewire('photography.photo-grid', [
            'category' => $PhotographyHelper::TAB_OTHERS,
            'season' => $tsSeasonId,
            'schoolKey' => $tsSchoolKey,
            'filters' => [
                'year' => array_key_first($yearOptions),
                'view' => array_key_first($viewOptions),
                'class' => array_key_first($classOptions),
            ]
        ])--}}
    </div>
</div>

@push('scripts')
<script type="module">
    function performSearch(event) {
        console.log({event});
        // Livewire.dispatch('EV_UPDATE_FILTER', {year: selectedYear, view: selectedView, class: selectedClass});
    }
    function updateGridView(event) {
        const selectedYear = $('#select_others_year').val();
        const selectedView = $('#select_others_view').val();
        const selectedClass = $('#select_others_class').val();
        
        Livewire.dispatch('EV_UPDATE_FILTER', {year: selectedYear, view: selectedView, class: selectedClass});
    };

    window.addEventListener('load', () => {
        $('#select_others_year').select2({placeholder: "Select a Year"});
        $('#select_others_year').change(updateGridView);
        $('#select_others_view').select2({placeholder: "Select a View", minimumResultsForSearch: Infinity});
        $('#select_others_view').change(updateGridView);
        $('#select_others_class').select2({placeholder: "Select a Class"});
        $('#select_others_class').change(updateGridView);
    });
</script>
@endpush
