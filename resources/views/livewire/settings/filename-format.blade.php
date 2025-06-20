<div class="w-full">
    <div id="filename-format-section">
        <h5 class="text-xl font-bold">File Name Format Options</h5>
        <p class="pt-3">Add filename format options for the downloaded images. Users can select these options from a menu when downloading photos.</p>
        <div class="w-full flex flex-row gap-4 mt-4 items-start">
            <div class="min-w-[110px]">
                <x-form.select context="image-types" :options="$imageTypes" class="w-full">Image Type</x-form.select>
            </div>
            <div class="grid grid-rows-1 w-full min-w-[350px]">
                <div>
                    <p id="filename-pattern" class="block mb-2">File Name Pattern <span class="text-xs text-gray-500">(Use <span class="font-bold font-">#</span> to insert database fields)</span></p>
                </div>
                <div id="tag-input"
                    contenteditable="true"
                    class="w-full flex flex-wrap flex-row gap-y-1 border rounded-md p-2 border-neutral 
                        read-only:opacity-50 read-only:cursor-not-allowed 
                        read-only:bg-neutral-300 align-items-center"
                    >
                </div>
            </div>
            <div class="grid grid-rows-1 w-full max-w-1/3 min-w-[200px]">
                <div>
                    <span id="format-name-title" class="block mb-2">Display Name</span>
                </div>
                <x-form.input.text id="input-format-name" placeholder="" class="w-full" />
            </div>
            <div class="pt-[29px]">
                <x-button.primary id="btn-add-format" class="place-self-end">Add</x-button.primary>
            </div>
        </div>
        <div class="w-full flex flex-row gap-4 mt-4 max-h-96 overflow-y-auto rounded-[4px] border-[1px]">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="sticky top-0 bg-white">
                    <tr class="border-b border-[#E6E7E8]">
                        <x-table.headerCell id="header-file-pattern" class="p-0.5 border-none" clickable="{{false}}">File Name Pattern</x-table.headerCell>
                        <x-table.headerCell id="header-image-type" class="p-0.5 border-none" clickable="{{false}}">Image Type</x-table.headerCell>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($formats as $format)
                        <tr class="border-b border-[#E6E7E8] last:border-b-0">
                            <x-table.cell class="grid grid-rows-2 gap-1 border-none">
                                <span class="text-lg font-bold">{{ $format['name'] }}</span>
                                <span class="text-sm">{{ $format['format'] }}</span>
                            </x-table.cell>
                            <x-table.cell class="w-1/4 border-none">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($format["visibility"] as $visibility)
                                        <x-tag.base class="border-info-600 text-info-600" hollow>{{ ucfirst($visibility) }}</x-tag.base>
                                    @endforeach
                                </div>
                            </x-table.cell>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    const options = @json($fieldOptions);
    const tributeItems = [];
    Object.keys(options).forEach(key => {
        tributeItems.push({
            key: key,
            value: options[key]
        });
    });
    document.addEventListener('DOMContentLoaded', function () {
        const taggableInput = document.getElementById("tag-input");
        const addFormatBtn = document.getElementById("btn-add-format");
        const formatNameInput = document.getElementById("input-format-name");
        const imageTypeSelect = document.querySelector('select[name="image-types"]');
        $('#select_image-types').select2({minimumResultsForSearch: Infinity});
        $('.select2-selection').addClass('border-neutral');

        function toggleAddButton() {
            const hasPattern = taggableInput.textContent.trim().length > 0;
            const hasName = formatNameInput.value.trim().length > 0;
            addFormatBtn.disabled = !(hasPattern && hasName);
        }

        // Initial state
        toggleAddButton();

        // Listen for input changes
        taggableInput.addEventListener('input', toggleAddButton);
        formatNameInput.addEventListener('input', toggleAddButton);
        
        taggableInput.addEventListener('click', function (e) {
            e.stopPropagation();
            e.preventDefault();
            const selectedTag = e.target.closest('.format-tag');
            if (selectedTag) {
                selectedTag.remove();
            }
        });

        addFormatBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            e.preventDefault();
            let inputValueRaw = taggableInput.textContent.trim();

            tributeItems.forEach(item => {
                inputValueRaw = inputValueRaw.replaceAll(item.key, item.value);
            });
            
            const pattern = inputValueRaw;
            const name = formatNameInput.value.trim();
            const type = imageTypeSelect.value;

            // Basic validation (optional)
            if (!pattern || !name || !type) {
                alert('Please fill in all fields.');
                return;
            }

            Livewire.dispatch('EV_ADD_FILENAME_FORMAT', {
                type: type,
                pattern: pattern,
                name: name,
            });
        });
        
        var tribute = new window.Tribute({
            trigger: "#",
            requireLeadingSpace: false,
            // autocompleteMode: true,
            values: tributeItems,
            selectTemplate: function(item) {
                return ('<span contenteditable="false" class="format-tag rounded-md text-sm font-semibold h-fit py-[1px] px-1 bg-[#EEF2FA] cursor-pointer" ><a>' + item.original.key + '</a><x-icon class="pl-1" icon="close" /></span>');
            },
            menuItemTemplate: function(item) {
                return ('<span contenteditable="false" class="p-2 hover:cursor-pointer" >' + item.original.key + '</span>');
            },
            noMatchTemplate: function () {
                return null;
            }
        });

        tribute.attach(taggableInput);

        // Livewire.hook('commit', ({ commit, component }) => {
        Livewire.on('EV_FILENAME_FORMAT_ADDED', (data) => {
            document.getElementById('tag-input').innerHTML = '';
            $('#input-format-name').val('');
            debounce(() => {
                $('#select_image-types').select2({minimumResultsForSearch: Infinity});
                $('.select2-selection').addClass('border-neutral');
                toggleAddButton();
            }, 200)();
        });
    });
</script>
@endpush