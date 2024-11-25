<div x-data="dropdownComponent()" class="relative">
    <x-button.link @click="open = !open">
        <x-icon icon="filter fa-sm" class="text-[#CFD1DE]"/>
    </x-button.link>

    <div x-show="open" @click.away="open = false" class="absolute z-10 top-8 bg-white divide-y divide-gray-100 rounded-lg shadow">
        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownDefaultButton-0">
            @foreach ($options as $key => $option)
                <li class="flex items-center px-4 py-2">
                    <input type="checkbox" 
                       :value="'{{ $key }}'"
                       @change="toggle('{{ $key }}')"
                       :checked="selected.includes('{{ $key }}')"
                       class="mr-2">
                    <div class="px-4 py-2 cursor-pointer hover:bg-gray-100 whitespace-nowrap"
                        :class="{ 'bg-blue-100': selected.includes('{{ $key }}') }"
                        @click="toggle('{{ $key }}')">
                        {{ $option }}
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    <script>
        function dropdownComponent() {
            return {
                open: false,
                selected: [],
                toggle(item) {
                    console.log('toggled!', {item});
                    // Check if the item is already selected
                    // if (this.selected.includes(item)) {
                    //     this.selected = this.selected.filter(i => i !== item);  // Remove item
                    //     @this.filterRemoved(this.selected).then(() => {
                    //     });
                    // } else {
                    //     this.selected.push(item);  // Add item
                    //     @this.filterAdded(this.selected).then(() => {
                    //     });
                    // }
                }
            }
        }
    </script>
</div>