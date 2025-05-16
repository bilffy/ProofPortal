@php
    $groupsTab = $AppSettingsHelper::getByPropertyKey('groups_tab');
    $groupsTabValue = $groupsTab ? $groupsTab->property_value === 'true' ? true : false : true;
@endphp
<div>
    <div class="w-full border rounded lg:w-full xl:w-1/2">
        <table class=" w-full">
            <thead>
                <x-table.headerCell sortable="{{ false }}">Folder</x-table.headerCell>
                <x-table.headerCell sortable="{{ false }}">
                    <input type="checkbox"
                        class="mr-1"
                        id="set-is-visible-for-portrait"
                        name="portrait-checkbox"
                    >
                    Portraits Tab
                </x-table.headerCell>
                @if($groupsTabValue)
                    <x-table.headerCell sortable="{{ false }}">
                        <input type="checkbox"
                            class="mr-1"
                            id="set-is-visible-for-group"
                            name="group-checkbox"
                        >
                        Groups Tab
                    </x-table.headerCell>
                @endif
            </thead>
            <tbody>
                @if($selectedFolders && count($selectedFolders) > 0)
                    @foreach($selectedFolders as $folder)
                        @php
                            $folderName = $folder['ts_foldername'];
                            $fIdHash = Crypt::encryptString($folder['ts_folder_id']);
                            $allowPortraitVisible = isset($folder['is_visible_for_portrait']) && $folder['is_visible_for_portrait'] == 1;
                            $allowGroupVisible = isset($folder['is_visible_for_group']) && $folder['is_visible_for_group'] == 1;
                        @endphp
                        <tr id="{{ $folder['tag'] }}" class="folder-row" @if($folder['tag'] !== 'Speciality Group') data-tagid="portrait" @elseif($folder['tag'] === 'Speciality Group') data-tagid="special_group" @endif>
                            <x-table.cell><?= $folderName ?></x-table.cell>
                            <x-table.cell class="flex items-center">
                                <input type="checkbox"
                                    class="folder-details-is-visible-for-portrait mr-1"
                                    id="is-visible-for-portrait"
                                    name="is-visible-for-portrait-{{ $folderName }}"
                                    data-folder-id="{{ $fIdHash }}"
                                    data-folder-name="{{ $folderName }}"
                                    data-value="{{$folder['is_visible_for_portrait']}}"
                                    {{ $allowPortraitVisible ? 'checked' : '' }}
                                >
                                <div class="column-1">
                                    <div>
                                        <label class="ml-1 mb-0" for="">
                                            {{$folder['students'] + $folder['attached']}} Portraits
                                        </label>
                                    </div>
                                    <div class="flex justify-between text-neutral-500 text-[13px]">
                                        <label class="ml-1 mb-0" for="Home folder" data-toggle="tooltip" title="Home folder">
                                            <x-icon icon="user" class="pe-1 fa-xs" /> {{$folder['students']}}
                                        </label>
                                        <label class="ml-1 mb-0" for="Attached to folder" data-toggle="tooltip" title="Attached to folder">
                                            <x-icon icon="paperclip" class="pe-1 fa-xs" /> {{$folder['attached']}}
                                        </label>
                                    </div>
                                </div>
                            </x-table.cell>
                            @if($groupsTabValue)
                                <x-table.cell class=" items-center">
                                    <input type="checkbox"
                                        class="folder-details-is-visible-for-group mr-1"
                                        id="is-visible-for-group"
                                        name="is-visible-for-group-{{ $folderName }}"
                                        data-folder-id="{{ $fIdHash }}"
                                        data-folder-name="{{ $folderName }}"
                                        data-value="{{$folder['is_visible_for_group']}}"
                                        {{ $allowGroupVisible ? 'checked' : '' }}
                                    >
                                    <label class="ml-1 mb-0" for="">
                                        {{$folder['groupCount']}} Group Photo
                                    </label>
                                </x-table.cell>
                            @endif
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>