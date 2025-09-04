@php
    $groupsTab = $AppSettingsHelper::getByPropertyKey('groups_tab');
    $groupsTabValue = $groupsTab ? $groupsTab->property_value === 'true' ? true : false : true;
@endphp    

    <div class="row mt-2">
        <div class="col-12 m-auto">
            <p class="h5 lead mb-1"><strong>{{ __('Folder Configuration') }}</strong></p>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12 table-fixed-header">
            <table class="table table-responsive-sm table-sm table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th width="15%">Folder</th>
                        <th class="text-center" width="10%">
                            Visible for Portrait
                            <br>
                            <p class="mb-0 d-repeating-header-none">
                                <span class="font-weight-light" id="set-is-visible-for-portrait-all" style="cursor: pointer;">Select All</span>
                                <span class="font-weight-normal"> | </span>
                                <span class="font-weight-light" id="set-is-visible-for-portrait-none" style="cursor: pointer;">None</span>
                            </p>
                        </th>
                        @if($groupsTabValue)
                            <th class="text-center" width="10%">
                                Visible for Group Photo
                                <br>
                                <p class="mb-0 d-repeating-header-none">
                                    <span class="font-weight-light" id="set-is-visible-for-group-all" style="cursor: pointer;">Select All</span>
                                    <span class="font-weight-normal"> | </span>
                                    <span class="font-weight-light" id="set-is-visible-for-group-none" style="cursor: pointer;">None</span>
                                </p>
                            </th>
                        @endif
                        <th class="text-center" width="15%">
                            Photo Counts
                            <br>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @if($selectedFolders && count($selectedFolders) > 0)
                        @foreach ($selectedFolders as $folder)  
                            @php
                                $folderName = $folder['ts_foldername'];
                                $fIdHash = Crypt::encryptString($folder['ts_folder_id']);
                            @endphp
                            <tr class="folder-row" id="{{ $folder['tag'] }}" @if($folder['tag'] !== 'Speciality Group') data-tagid="portrait" @elseif($folder['tag'] === 'Speciality Group') data-tagid="special_group" @endif>
                                <td class="folder-name-cell">
                                    <div class="mt-1 ml-1">
                                        <?= $folderName ?>
                                    </div>
                                </td>
                                <td class="visible-for-portrait">
                                    <div class="row is-visible-for-portrait">
                                        <div class="col-12">
                                            @php
                                                $allowPortraitVisible = false;
                                                if (isset($folder['is_visible_for_portrait'])) {
                                                    $allowPortraitVisible = $folder['is_visible_for_portrait'] == 1;
                                                }
                                            @endphp
                                            <div class="form-group text-center">
                                                <input type="checkbox"
                                                    class="form-check-input folder-details-is-visible-for-portrait text-center mt-2 ml-0 mr-0"
                                                    id="is-visible-for-portrait"
                                                    name="is-visible-for-portrait-{{ $folderName }}"
                                                    data-folder-id="{{ $fIdHash }}"
                                                    data-folder-name="{{ $folderName }}"
                                                    {{ $allowPortraitVisible ? 'checked' : '' }} data-value="{{$folder['is_visible_for_portrait']}}">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @if($groupsTabValue)
                                    <td class="visible-for-group">
                                        <div class="row is-visible-for-group">
                                            <div class="col-12">
                                                @php
                                                    $allowGroupVisible = false;
                                                    if (isset($folder['is_visible_for_group'])) {
                                                        $allowGroupVisible = $folder['is_visible_for_group'] == 1;
                                                    }
                                                @endphp
                                                <div class="form-group text-center">
                                                    <input type="checkbox"
                                                        class="form-check-input folder-details-is-visible-for-group text-center mt-2 ml-0 mr-0"
                                                        id="is-visible-for-group"
                                                        name="is-visible-for-group-{{ $folderName }}"
                                                        data-folder-id="{{ $fIdHash }}"
                                                        data-folder-name="{{ $folderName }}"
                                                        {{ $allowGroupVisible ? 'checked' : '' }} data-value="{{$folder['is_visible_for_group']}}">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="folder-students">
                                    <div class="row is-visible-for-school">
                                        <div class="col-12">
                                            <div class="form-group text-center">
                                                @if($folder['students'] != 0) {{$folder['students']}} Portraits <br> @endif
                                                {{$folder['groupCount']}} Group
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>            
        </div>
    </div>