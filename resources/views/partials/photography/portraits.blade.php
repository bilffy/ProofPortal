@php
    use App\Helpers\SchoolContextHelper;
    use App\Services\ImageService;

    $imageService = new ImageService();
    $job = SchoolContextHelper::getSchoolJob();
    $tsSeasonId = $job->ts_season_id;
    $tsSchoolKey = $job->ts_schoolkey;

    $portraitYearOptions = $imageService->getAllYears()->toArray();
    $portraitViewOptions = $imageService->getFolderForView(
        $tsSeasonId, 
        $tsSchoolKey,
        '!=',
        'SP'
    )->values()->toArray();
    $portraitClassOptions = $imageService->getFoldersByTag(
        $tsSeasonId,
        $tsSchoolKey,
        'student',
        'is_visible_for_portrait'
    )->toArray();

    $yearOptions[0] = 'Select Year';
    $viewOptions['ALL'] = 'Select View';
    $classOptions['ALL'] = 'Select Class';
    
    foreach ($portraitYearOptions as $option) {
        $yearOptions[$option->Year] = $option->Year;
    }
    foreach ($portraitViewOptions as $option) {
        $viewOptions[$option->external_name] = $option->external_name;
    }
    foreach ($portraitClassOptions as $option) {
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
            <x-form.select context="portaits_year" :options="$yearOptions" class="mb-4">Year</x-form.select>    
            <x-form.select context="portaits_view" :options="$viewOptions" class="mb-4">View</x-form.select>
            <x-form.select context="portaits_class" :options="$classOptions" class="mb-4">Classes</x-form.select>
        </div>

        @livewire('photography.photo-grid', [
            'category' => $PhotographyHelper::TAB_PORTRAITS,
            'season' => $tsSeasonId,
            'schoolKey' => $tsSchoolKey,
            'filters' => [
                'year' => array_key_first($yearOptions),
                'view' => array_key_first($viewOptions),
                'class' => array_key_first($classOptions),
            ]
        ])
    </div>
</div>

@push('scripts')
<script type="module">
    function performSearch(event) {
        console.log({event});
        // Livewire.dispatch('EV_UPDATE_FILTER', {year: selectedYear, view: selectedView, class: selectedClass});
    }
    function updateGridView(event) {
        const selectedYear = $('#select_portaits_year').val();
        const selectedView = $('#select_portaits_view').val();
        const selectedClass = $('#select_portaits_class').val();
        
        Livewire.dispatch('EV_UPDATE_FILTER', {year: selectedYear, view: selectedView, class: selectedClass});
    };

    window.addEventListener('load', () => {
        $('#select_portaits_year').select2({placeholder: "Select a Year"});
        $('#select_portaits_year').change(updateGridView);
        $('#select_portaits_view').select2({placeholder: "Select a View", minimumResultsForSearch: Infinity});
        $('#select_portaits_view').change(updateGridView);
        $('#select_portaits_class').select2({placeholder: "Select a Class"});
        $('#select_portaits_class').change(updateGridView);
    });
</script>
@endpush