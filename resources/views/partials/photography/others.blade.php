@php
    $otherImages = [];
    $testYearOptions = ["2024", "2023", "2022", "2021", "2020", "2019"];
    $testViewOptions = ["G", "H", "I"];
    $testClassOptions = ["1-A", "1-B", "1-C", "2-A", "2-B", "2-C", "3-A", "3-B", "3-C"];
@endphp
<div>
    <div class="flex flex-row gap-4">
        <div class="w-[200px]">
            <div class="mb-4">
                <x-form.input.search/>
            </div>
            <x-form.select context="others_year" :options="$testYearOptions" class="mb-4">Year</x-form.select>    
            <x-form.select context="others_view" :options="$testViewOptions" class="mb-4">View</x-form.select>
            <x-form.select context="others_class" :options="$testClassOptions" class="mb-4">Classes</x-form.select>
        </div>

        @livewire('photography.photo-grid', ['category' => $PhotographyHelper::TAB_OTHERS])
    </div>
    <div class="text-center mt-4 mb-4">
        Insert Pagination here
        {{-- {{ $otherImages->onEachSide(1)->links('vendor.livewire.pagination') }} --}}
    </div>
</div>

@push('scripts')
<script type="module">
    function updateGridView(event) {
        console.log({val: event.target.value});
    };

    window.addEventListener('load', () => {
        $('#select_others_year').select2({placeholder: ""});
        $('#select_others_year').change(updateGridView);
        $('#select_others_view').select2({placeholder: "", minimumResultsForSearch: Infinity});
        $('#select_others_view').change(updateGridView);
        $('#select_others_class').select2({placeholder: ""});
        $('#select_others_class').change(updateGridView);
    });
</script>
@endpush
