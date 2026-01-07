@foreach ($subjects as $subject)
@php
    $skHash = sha1($subject->ts_subjectkey);
    $skEncrypted = Crypt::encryptString($subject->ts_subjectkey);
    $folderkeyEncrypted = Crypt::encryptString($currentFolder->ts_folderkey);
    if ($subject->ts_subjectkey != '' && $selectedJob->ts_jobkey != '') {
        $combined_key = $subject->ts_subjectkey . $selectedJob->ts_jobkey;
        $encryptImageKey = sprintf("%08x", crc32($combined_key));
        $hashed_key = hash('sha256', $combined_key);
        $sub_dirs = [];

        for ($i = 0; $i < strlen($hashed_key); $i += 5) {
            $sub_dirs[] = substr($hashed_key, $i, 3);
        }

        // Generate the directory structure and filename using DIRECTORY_SEPARATOR
        $full_path = implode(DIRECTORY_SEPARATOR, $sub_dirs);
        $imageName = DIRECTORY_SEPARATOR . $full_path . DIRECTORY_SEPARATOR . $encryptImageKey . '.jpg';
        $newimageName = Str::replace('\\', '-', $imageName);
        // Generate a signed URL for the image
        // $image_url = route('serve.image', ['filename' => $newimageName]);
        $image_url = route('serve.image', ['filename' => $skEncrypted]); 

        $useSalutation = $currentFolder->is_edit_salutation;
        $usePrefixSuffix = $currentFolder->show_prefix_suffix_groups;

        // Trim all parts safely
        $salutation = trim($subject->salutation ?? '');
        $prefix = trim($subject->prefix ?? '');
        $suffix = trim($subject->suffix ?? '');
        $firstname = trim($subject->firstname ?? '');
        $lastname = trim($subject->lastname ?? '');

        // Build display name dynamically using array join to avoid double spaces
        $nameParts = [];

        if ($useSalutation && $salutation !== '') {
            $nameParts[] = $salutation;
        }

        if ($usePrefixSuffix && $prefix !== '') {
            $nameParts[] = $prefix;
        }

        if ($firstname !== '') {
            $nameParts[] = $firstname;
        }

        if ($lastname !== '') {
            $nameParts[] = $lastname;
        }

        if ($usePrefixSuffix && $suffix !== '') {
            $nameParts[] = $suffix;
        }

        // Join with single space
        $displayName = implode(' ', $nameParts);
    }
@endphp

<tr class="person-row {{ $skHash }}"
    data-subject-name="{{ strtolower($displayName) }}">

    <td class="idx-artifact text-center pt-2 pb-1">
        <div class="person-pic-wrapper d-inline">
            <img class="lazyloadgrid" data-src="{{ route('serve.image', ['filename' => Crypt::encryptString($subject->ts_subjectkey)]) }}" width="100" height="100">
        </div>
    </td>
    @if ($currentFolder->is_edit_salutation)
        <td class="idx-salutation p-0">
            <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-salutation"
                id="{{ $skHash }}-grid-spelling-salutation"
                name="{{ $skHash }}_grid_spelling_salutation"
                value="{{ $subject->salutation }}"
                data-original-value="{{ $subject->salutation }}"
                data-old-value="{{ $subject->salutation }}"
                data-skhash="{{ $skHash }}"
                data-skencrypted="{{ $skEncrypted }}" data-folderkeyEncrypted="{{ $folderkeyEncrypted }}">
        </td>
    @endif
    <td class="idx-prefix p-0">
        <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-prefix"
            id="{{ $skHash }}-grid-spelling-prefix"
            name="{{ $skHash }}_grid_spelling_prefix"
            value="{{ $subject['prefix'] }}"
            data-original-value="{{ $subject['prefix'] }}"
            data-old-value="{{ $subject['prefix'] }}"
            data-skhash="{{ $skHash }}"
            data-skencrypted="{{ $skEncrypted }}" data-folderkeyEncrypted="{{ $folderkeyEncrypted }}">
    </td>
    <td class="idx-first-name p-0">
        <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-first-name"
            id="{{ $skHash }}-grid-spelling-first-name"
            name="{{ $skHash }}_grid_spelling_first_name"
            value="{{ $firstname }}"
            data-original-value="{{ $firstname }}"
            data-old-value="{{ $firstname }}"
            data-skhash="{{ $skHash }}"
            data-skencrypted="{{ $skEncrypted }}" data-folderkeyEncrypted="{{ $folderkeyEncrypted }}">
    </td>
    <td class="idx-last-name p-0">
        <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-last-name"
            id="{{ $skHash }}-grid-spelling-last-name"
            name="{{ $skHash }}_grid_spelling_last_name"
            value="{{ $lastname }}"
            data-original-value="{{ $lastname }}"
            data-old-value="{{ $lastname }}"
            data-skhash="{{ $skHash }}"
            data-skencrypted="{{ $skEncrypted }}" data-folderkeyEncrypted="{{ $folderkeyEncrypted }}">
    </td>
    <td class="idx-suffix p-0">
        <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-suffix"
            id="{{ $skHash }}-grid-spelling-suffix"
            name="{{ $skHash }}_grid_spelling_suffix"
            value="{{$subject['suffix'] }}"
            data-original-value="{{ $subject['suffix'] }}"
            data-old-value="{{ $subject['suffix'] }}"
            data-skhash="{{ $skHash }}"
            data-skencrypted="{{ $skEncrypted }}" data-folderkeyEncrypted="{{ $folderkeyEncrypted }}">
    </td>
    @if ($currentFolder->is_edit_job_title)
        <td class="idx-job-title p-0">
            <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-title"
                id="{{ $skHash }}-grid-spelling-title"
                name="{{ $skHash }}_grid_spelling_title"
                value="{{ $subject->title }}"
                data-original-value="{{ $subject->title }}"
                data-old-value="{{ $subject->title }}"
                data-skhash="{{ $skHash }}"
                data-skencrypted="{{ $skEncrypted }}" data-folderkeyEncrypted="{{ $folderkeyEncrypted }}">
        </td>
    @endif
    <td class="idx-last-name p-0 align-middle text-center">
        <span class="d-none pl-3 pr-4 pt-2 pb-2" id="{{ $skHash }}-grid-spelling-revert-button"
            data-skhash="{{ $skHash }}" data-skencrypted="{{ $skEncrypted }}" data-folderkeyEncrypted="{{ $folderkeyEncrypted }}">
            <a href="#">
                <i class="fa fa-undo fa-lg text-danger"></i>
            </a>
        </span>
    </td>
</tr>
@endforeach