<div class="w-full overflow-x-auto">
    <div class="overflow-hidden min-w-max">
        <h5 class="text-xl font-bold dark:text-white">Feature Controls</h5>
        <p class="pt-3">Enable or Disable features of the Application. Changes made here are applied system-wide.</p>
        <div class="grid grid-cols-3 p-4 text-sm font-medium text-gray-900 bg-gray-100 border-t border-b border-gray-200 gap-x-16 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            <div class="flex items-center">Feature</div>
            <div>Status</div>
            <div>Enabled/Disable</div>
        </div>
        <div class="grid grid-cols-3 px-4 py-5 text-sm text-gray-700 border-b border-gray-200 gap-x-16 dark:border-gray-700">
            @foreach ($settings as $setting)
                <div class="text-gray-500 dark:text-gray-400">
                    {{ str_replace('_', ' ', \Illuminate\Support\Str::title($setting->name)) }}
                    <p>{{ $setting->description }}</p>
                    
                </div>
                <div>
                    @if($settingsStates[$setting->id] ?? $setting->property_value == 'true')
                        <span class="text-green-600 font-semibold border-1 border-green-600 px-1 py-1">Enabled</span>
                    @else
                        <span class="text-red-600 font-semibold border-1 border-red-600 px-1 py-1">Disabled</span>
                    @endif
                </div>
                <div>
                    <label class="inline-flex items-center cursor-pointer">
                        <input
                                type="checkbox"
                                wire:model="settingsStates.{{ $setting->id }}"
                                wire:click="updateSettingValue('{{ $setting->id }}', '{{ $setting->property_value }}')"
                                class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            @endforeach    
        </div>
        
    </div>
</div>
