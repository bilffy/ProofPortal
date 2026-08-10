// Capture jQuery NOW (proofing jquery.min.js + select2 plugin).
// Vite's deferred bundle later overwrites window.jQuery/window.$ with a
// different copy that does NOT have select2 — using that copy breaks events.
var configureJQ = window.jQuery;
var configure$ = configureJQ;

configureJQ(document).ready(function ($) {
    $('#schoolName_picker').on('keyup change', function () {
        var schoolNameNew = $(this).val();
        const schoolKey = $('#schoolHash').val();
        if (event.type === 'change') {
            sendSchoolChanges('name', schoolNameNew, schoolKey);
            schoolNameCurrent = schoolNameNew; // Update current class name after changes are sent
        }
    });

    $('#address_picker').on('keyup change', function () {
        var addressNew = $(this).val();
        const schoolKey = $('#schoolHash').val();
        if (event.type === 'change') {
            sendSchoolChanges('address', addressNew, schoolKey);
            addressCurrent = addressNew; // Update current class name after changes are sent
        }
    });

    $('#postcode_picker').on('keyup change', function () {
        var postcodeNew = $(this).val();
        const schoolKey = $('#schoolHash').val();
        if (event.type === 'change') {
            sendSchoolChanges('postcode', postcodeNew, schoolKey);
            postcodeCurrent = postcodeNew; // Update current class name after changes are sent
        }
    });

    $('#suburb_picker').on('keyup change', function () {
        var suburbNew = $(this).val();
        const schoolKey = $('#schoolHash').val();
        if (event.type === 'change') {
            sendSchoolChanges('suburb', suburbNew, schoolKey);
            suburbCurrent = suburbNew; // Update current class name after changes are sent
        }
    });

    function sendSchoolChanges(field, newData, schoolKey) {
        var returnResponse;

        var formData = new FormData();
        formData.append("field", field);
        formData.append("newData", newData);
        formData.append("schoolKey", schoolKey);

        formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            dataType: 'json',
            type: "POST",
            url: base_url + "/school-change/submit",
            async: true,
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            timeout: 60000,

            success: function (response) {
                returnResponse = response;
            },
            error: function (e) {
                //alert("An error occurred: " + e.responseText.message);
                returnResponse = false;
            },
            complete: function (xhr) {
                // if (xhr.status >= 400 && xhr.status <= 499) {
                //     window.location.replace("<?= $targetUrlStatus4xx ?>");
                // }
            }
        })
        return returnResponse;
    }

    // Folder visibility (Portraits / Groups) — plain ts_folder_id; never re-fire job change
    let jobsData = [];
    let isBulkFolderVisibilityUpdate = false;

    function normalizeJobFolders(folders) {
        if (Array.isArray(folders)) {
            return folders;
        }
        if (folders && typeof folders === 'object') {
            return Object.values(folders);
        }
        return [];
    }

    function syncFolderVisibilityInJobsData(folderTsId, field, newValue) {
        const selectedJobKey = $('#select_job').val();
        const selectedJob = jobsData.find(job => job.ts_jobkey === selectedJobKey);
        if (!selectedJob) {
            return;
        }
        selectedJob.Folders = normalizeJobFolders(selectedJob.Folders);
        const selectedFolder = selectedJob.Folders.find(
            folder => String(folder.ts_folder_id) === String(folderTsId)
        );
        if (selectedFolder) {
            selectedFolder[field] = newValue;
        }
        if (field === 'is_visible_for_portrait') {
            const anyVisible = selectedJob.Folders.some(f => Number(f.is_visible_for_portrait) === 1);
            const jobOption = $(`#select_job option[value="${selectedJobKey}"]`);
            jobOption
                .data('has-visible', !!anyVisible)
                .attr('data-has-visible', anyVisible ? 'true' : 'false');
        }
    }

    function syncHeaderFolderCheckbox(checkboxClass, headerSelector) {
        const $boxes = $('#folder_config tr.folder-row .' + checkboxClass);
        if ($boxes.length === 0) {
            return;
        }
        const checkedCount = $boxes.filter(':checked').length;
        $(headerSelector).prop('checked', checkedCount === $boxes.length);
    }

    function sendFolderChanges(folderId, field, newData) {
        const ids = (Array.isArray(folderId) ? folderId : [folderId])
            .map((id) => String(id == null ? '' : id).trim())
            .filter((id) => /^\d+$/.test(id));

        if (ids.length === 0) {
            console.error('Folder update skipped: no valid folder id', folderId);
            window.dispatchEvent(new CustomEvent('show-toast-message', {
                detail: { status: 'error', message: 'Folder update failed: missing folder id.' }
            }));
            return $.Deferred().reject(new Error('No folder id provided')).promise();
        }

        return $.ajax({
            dataType: 'json',
            type: 'POST',
            url: base_url + '/folder-change/submit',
            data: {
                field: field,
                newValue: newData,
                folderId: ids.join(','),
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            timeout: 60000,
        }).done(function (response) {
            if (response && response.success === false) {
                console.error('Folder update rejected:', response);
                window.dispatchEvent(new CustomEvent('show-toast-message', {
                    detail: {
                        status: 'error',
                        message: (response && response.message) || 'Folder visibility was not saved.'
                    }
                }));
                return;
            }
            ids.forEach(function (id) {
                if (field === 'is_visible_for_portrait') {
                    $('#is-visible-for-portrait-' + id).attr('data-value', newData);
                } else if (field === 'is_visible_for_group') {
                    $('#is-visible-for-group-' + id).attr('data-value', newData);
                }
            });
            if (typeof window.updateSchoolConfig === 'function') {
                window.updateSchoolConfig();
            }
        }).fail(function (error) {
            console.error('Error updating folder:', error);
            window.dispatchEvent(new CustomEvent('show-toast-message', {
                detail: { status: 'error', message: 'Folder visibility update failed. Please try again.' }
            }));
        });
    }

    function handleBulkCheckboxChange(isChecked, checkboxClass, field) {
        // Include all folder rows (do not use :visible — it can miss rows after filter/layout quirks)
        const $boxes = $('#folder_config tr.folder-row .' + checkboxClass);
        if ($boxes.length === 0) {
            console.error('Bulk folder toggle: no checkboxes found for', checkboxClass);
            return;
        }

        const newValue = isChecked ? 1 : 0;
        const folderIdsToUpdate = [];

        isBulkFolderVisibilityUpdate = true;
        $boxes.each(function () {
            this.checked = isChecked;
            $(this).attr('data-value', newValue);
            const folderTsId = $(this).attr('data-ts-folder-id');
            if (folderTsId) {
                folderIdsToUpdate.push(folderTsId);
                syncFolderVisibilityInJobsData(folderTsId, field, newValue);
            }
        });
        isBulkFolderVisibilityUpdate = false;

        if (folderIdsToUpdate.length > 0) {
            sendFolderChanges(folderIdsToUpdate, field, newValue);
        }
    }

    $(document).on('change.folderVisibility', '#folder_config .folder-details-is-visible-for-portrait', function () {
        if (isBulkFolderVisibilityUpdate) {
            return;
        }
        const newValue = this.checked ? 1 : 0;
        const folderTsId = this.getAttribute('data-ts-folder-id');
        $(this).attr('data-value', newValue);
        syncFolderVisibilityInJobsData(folderTsId, 'is_visible_for_portrait', newValue);
        syncHeaderFolderCheckbox('folder-details-is-visible-for-portrait', '#set-is-visible-for-portrait');
        sendFolderChanges(folderTsId, 'is_visible_for_portrait', newValue);
    });

    $(document).on('change.folderVisibility', '#folder_config .folder-details-is-visible-for-group', function () {
        if (isBulkFolderVisibilityUpdate) {
            return;
        }
        const newValue = this.checked ? 1 : 0;
        const folderTsId = this.getAttribute('data-ts-folder-id');
        $(this).attr('data-value', newValue);
        syncFolderVisibilityInJobsData(folderTsId, 'is_visible_for_group', newValue);
        syncHeaderFolderCheckbox('folder-details-is-visible-for-group', '#set-is-visible-for-group');
        sendFolderChanges(folderTsId, 'is_visible_for_group', newValue);
    });

    $(document).on('change.folderVisibility', '#folder_config #set-is-visible-for-portrait', function (e) {
        e.stopImmediatePropagation();
        handleBulkCheckboxChange(
            this.checked,
            'folder-details-is-visible-for-portrait',
            'is_visible_for_portrait'
        );
    });

    $(document).on('change.folderVisibility', '#folder_config #set-is-visible-for-group', function (e) {
        e.stopImmediatePropagation();
        handleBulkCheckboxChange(
            this.checked,
            'folder-details-is-visible-for-group',
            'is_visible_for_group'
        );
    });

    function sendJobChanges(jobKey, field, newData) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("field", field);
            formData.append("newData", newData);
            formData.append("jobKey", jobKey);
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                dataType: 'json',
                type: "POST",
                url: base_url + "/job-change/submit",
                async: true,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                timeout: 60000,
                success: function (response) {
                    window.updateSchoolConfig();
                    resolve(response);
                },
                error: function (error) {
                    console.error("Error updating job:", error);
                    reject(error);
                }
            });
        });
    }

    function refreshJobSelect(jobs) {
        const jobSelect = $('#select_job');
        const wasSelect2 = jobSelect.hasClass('select2-hidden-accessible');

        if (wasSelect2) {
            jobSelect.select2('destroy');
        }

        jobSelect.empty().append('<option value="">Choose a Job</option>');

        (jobs || []).forEach(function (job) {
            const option = new Option(job.ts_jobname, job.ts_jobkey, false, false);
            if (job.has_visible_portrait) {
                option.setAttribute('data-has-visible', 'true');
            }
            jobSelect.append(option);
        });

        const formatJobResult = (job) => {
            if (!job.id) return job.text;
            const hasVisible = $(job.element).data('has-visible');
            if (hasVisible) {
                return $(`<div class="flex justify-between items-center w-full">
                            <span>${job.text}</span>
                            <i class="fa fa-check" title="Folders with visible portraits" style="color: #b5d334;"></i>
                          </div>`);
            }
            return job.text;
        };

        const formatJobSelection = (job) => {
            if (!job.id) return job.text;
            const hasVisible = $(job.element).data('has-visible');
            if (hasVisible) {
                return $(`<span>${job.text} <i class="fa fa-check" title="Folders with visible portraits" style="color: #b5d334;"></i></span>`);
            }
            return job.text;
        };

        jobSelect.select2({
            templateResult: formatJobResult,
            templateSelection: formatJobSelection,
            escapeMarkup: function (m) { return m; }
        });

        jobSelect.next('.select2-container').removeClass('d-none');
        jobSelect.parent().show();
    }

    function onSeasonSelected() {
        // Guard against change + native listener both firing
        if (onSeasonSelected._running) {
            return;
        }
        onSeasonSelected._running = true;
        setTimeout(function () { onSeasonSelected._running = false; }, 50);

        const $season = $('#select_season');
        const selectedSeasonId = $season.val();
        const selectedSchoolKey = $('#schoolHash').val();
        const selectedSeasonText = $season.find('option:selected').text();
        $('#SeasoncodeDisplay').text(' - ' + selectedSeasonText).removeClass('d-none');
        hideOrShowJobDependentSections(false);

        $('#digital_download').addClass('d-none');
        $('p.alert-message').remove();
        $('#folder_config').empty();

        if (!selectedSeasonId || selectedSeasonId === 'none') {
            $('#select_job').parent().hide();
            $('#no-jobs-msg').addClass('d-none');
            $('#job-select-loading').addClass('d-none');
            return;
        }

        $('#select_job').parent().show();
        $('#job-select-loading').removeClass('d-none');
        $('#select_job').next('.select2-container').addClass('d-none');
        $('#no-jobs-msg').addClass('d-none');

        $.ajax({
            url: base_url + '/config-school/fetch-jobs',
            method: 'GET',
            dataType: 'json',
            data: { ts_season_id: selectedSeasonId, schoolkey: selectedSchoolKey },
            success: function (jobs) {
                jobsData = (Array.isArray(jobs) ? jobs : []).map(function (job) {
                    job.Folders = normalizeJobFolders(job.Folders);
                    return job;
                });
                $('#job-select-loading').addClass('d-none');

                if (jobsData.length === 0) {
                    $('#no-jobs-msg').removeClass('d-none');
                    const jobSelect = $('#select_job');
                    if (jobSelect.hasClass('select2-hidden-accessible')) {
                        jobSelect.select2('destroy');
                    }
                    jobSelect.empty().append('<option value="">Choose a Job</option>');
                    jobSelect.select2();
                    jobSelect.next('.select2-container').addClass('d-none');
                    return;
                }

                $('#no-jobs-msg').addClass('d-none');
                refreshJobSelect(jobsData);
            },
            error: function (error) {
                console.error('Failed to fetch jobs.', error);
                jobsData = [];
                $('#no-jobs-msg').removeClass('d-none');
                $('#job-select-loading').addClass('d-none');
                $('#select_job').next('.select2-container').addClass('d-none');
            }
        });
    }

    // Bind on the proofing jQuery instance (same one as select2), not window.$
    // which Vite may have replaced. Only "change" — select2:select would double-fire.
    $(document)
        .off('change.configureSeason', '#select_season')
        .on('change.configureSeason', '#select_season', onSeasonSelected);

    // Native fallback — survives dual-jQuery / select2 trigger quirks
    const seasonEl = document.getElementById('select_season');
    if (seasonEl && !seasonEl.dataset.configureSeasonBound) {
        seasonEl.dataset.configureSeasonBound = '1';
        seasonEl.addEventListener('change', function () {
            onSeasonSelected();
        });
    }

    window.onConfigureSeasonSelected = onSeasonSelected;

    // Init select2 with THIS jQuery ($) — the one that has the select2 plugin.
    // Do not use window.$ here; Vite replaces it with a second jQuery copy.
    (function initSelect2Widgets($) {
        if (!$.fn.select2) {
            console.error('Configure: select2 plugin missing on configure jQuery instance');
            return;
        }
        const formatJobResult = (job) => {
            if (!job.id) return job.text;
            const hasVisible = $(job.element).data('has-visible');
            if (hasVisible) {
                return $(`<div class="flex justify-between items-center w-full">
                            <span>${job.text}</span>
                            <i class="fa fa-check" title="Folders with visible portraits" style="color: #b5d334;"></i>
                          </div>`);
            }
            return job.text;
        };
        const formatJobSelection = (job) => {
            if (!job.id) return job.text;
            const hasVisible = $(job.element).data('has-visible');
            if (hasVisible) {
                return $(`<span>${job.text} <i class="fa fa-check" title="Folders with visible portraits" style="color: #b5d334;"></i></span>`);
            }
            return job.text;
        };

        if ($('#select_season').length) {
            if ($('#select_season').hasClass('select2-hidden-accessible')) {
                $('#select_season').select2('destroy');
            }
            $('#select_season').select2();
        }
        if ($('#select_job').length) {
            if ($('#select_job').hasClass('select2-hidden-accessible')) {
                $('#select_job').select2('destroy');
            }
            $('#select_job').select2({
                templateResult: formatJobResult,
                templateSelection: formatJobSelection,
                escapeMarkup: function (m) { return m; }
            });
        }
        if ($('#select_job_access_image').length) {
            if ($('#select_job_access_image').hasClass('select2-hidden-accessible')) {
                $('#select_job_access_image').select2('destroy');
            }
            $('#select_job_access_image').select2();
        }
        $('#select_job').parent().hide();
        hideOrShowJobDependentSections(false);
    })($);

    const permissionCheckboxes = document.querySelectorAll('input[type="checkbox"].img-permission');
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const { model, field } = this.parentElement.dataset;
            const role = this.value;
            const checked = this.checked;
            insertDigitalDownload(model, field, role, checked);
        });
    });

    // Listen for changes in the input field value
    $('#portrait_download_start_picker').on('change', function () {
        const formattedPortraitDate = $('#portrait_download_start_picker').val();
        const selectedJob = jobsData.find(job => job.ts_jobkey === $('#select_job').val());
        sendJobChanges(selectedJob.ts_jobkey, 'portrait_download_date', formattedPortraitDate);
    });

    $('#group_download_start_picker').on('change', function () {
        const formattedGroupDate = $('#group_download_start_picker').val();
        const selectedJob = jobsData.find(job => job.ts_jobkey === $('#select_job').val());
        sendJobChanges(selectedJob.ts_jobkey, 'group_download_date', formattedGroupDate);
    });


    // Job select change — same jQuery instance as select2
    $(document)
        .off('change.configureJob select2:select.configureJob', '#select_job')
        .on('change.configureJob select2:select.configureJob', '#select_job', function () {
        const isGroupVisible = $("#is-group-visible").val();
        const selectedJobKey = $(this).val();
        $('#jobType, #digital_download').addClass('d-none');
        $('p.alert-message').remove();

        const selectedJob = jobsData.find(job => job.ts_jobkey === selectedJobKey);

        let portraitDateToDisplay;
        let groupDateToDisplay;

        if (selectedJob) {
            // Stamp portal school ownership (school_id) for this job
            const schoolHash = $('#schoolHash').val();
            if (schoolHash && selectedJobKey) {
                $.ajax({
                    url: base_url + '/config-school/assign-job-school',
                    method: 'POST',
                    data: { jobKey: selectedJobKey, schoolKey: schoolHash },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    error: function () {
                        console.error('Failed to assign school to job.');
                    }
                });
            }

            $('#jobType, #digital_download').removeClass('d-none');

            if (selectedJob.download_available_date !== null) {
                function parseDate(dateString) {
                    return dateString ? moment(dateString, 'YYYY-MM-DD HH:mm:ss') : null;
                }

                const downloadAvailableDate = parseDate(selectedJob.download_available_date);
                const portraitDownloadDate = parseDate(selectedJob.portrait_download_date);
                const groupDownloadDate = parseDate(selectedJob.group_download_date);


                let downloadAvailableDateInFormat = new Date(downloadAvailableDate.format('YYYY-MM-DD HH:mm:ss'));
                let portraitDownloadDateInFormat = portraitDownloadDate ? new Date(portraitDownloadDate.format('YYYY-MM-DD HH:mm:ss')) : null;
                let groupDownloadDateInFormat = groupDownloadDate ? new Date(groupDownloadDate.format('YYYY-MM-DD HH:mm:ss')) : null;

                // Only destroy datetimepicker if it's already initialized
                if ($('#portrait_download_start_picker').data("flatpickr")) {
                    $('#portrait_download_start_picker')[0]._flatpickr.destroy();
                }
                if ($('#group_download_start_picker').data("flatpickr")) {
                    $('#group_download_start_picker')[0]._flatpickr.destroy();
                }

                portraitDateToDisplay = downloadAvailableDateInFormat;
                groupDateToDisplay = downloadAvailableDateInFormat;

                if (portraitDateToDisplay || groupDateToDisplay) {
                    // Update display date if conditions are met
                    if (downloadAvailableDateInFormat < portraitDownloadDateInFormat) {
                        portraitDateToDisplay = portraitDownloadDateInFormat;
                    }

                    if (downloadAvailableDateInFormat < groupDownloadDateInFormat) {
                        groupDateToDisplay = groupDownloadDateInFormat;
                    }

                    // Event listener for when a date is selected from the portrait download date picker
                    flatpickr('#portrait_download_start_picker', {
                        enableTime: true,
                        dateFormat: "d/m/Y H:i K", // Same format as 'DD/MM/YYYY HH:mm A'
                        disableMobile: true,
                        defaultDate: portraitDateToDisplay, // Set default date
                        minDate: downloadAvailableDateInFormat
                    });

                    // Event listener for when a date is selected from the group download date picker
                    flatpickr('#group_download_start_picker', {
                        enableTime: true,
                        dateFormat: "d/m/Y H:i K", // Same format as 'DD/MM/YYYY HH:mm A'
                        disableMobile: true,
                        defaultDate: groupDateToDisplay, // Set default date
                        minDate: downloadAvailableDateInFormat
                    });
                }

                $("#portrait_download_allowed").prop("checked", !!$("#portrait_download_start_picker").val());
                $("#group_download_allowed").prop("checked", !!$("#group_download_start_picker").val());

            } else {
                $('#digital_download').addClass('d-none');
                $('#jobTypeMsg').after('<p class="alert-message" style="color:red;">**Currently photos are not processed in Lab. Please set your Digital Download Date.</p>');
            }
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            selectedJob.Folders = normalizeJobFolders(selectedJob.Folders);
            $.ajax({
                url: base_url + '/config-school/folder-config',
                method: 'POST',
                data: { folders: selectedJob.Folders },
                headers: {
                    'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                },
                success: function (response) {
                    if (response.html) {
                        $('#folder_config').html(response.html); // Correctly insert the HTML from the response
                        $("#select_job_access_image").trigger('change');
                        syncHeaderFolderCheckbox('folder-details-is-visible-for-portrait', '#set-is-visible-for-portrait');
                        syncHeaderFolderCheckbox('folder-details-is-visible-for-group', '#set-is-visible-for-group');
                    }
                    hideOrShowJobDependentSections(true);
                },
                error: function () {
                    console.error('Failed to load folder configuration.');
                    hideOrShowJobDependentSections(false);
                }
            });
        } else {
            hideOrShowJobDependentSections(false);
        }
    });

    $('#jobType').on('change', function () {
        $('p.alert-message').remove();
        const selectedValue = $('#select_job_access_image').val();

        if (selectedValue === '0') {
            $('#folder_config').addClass('d-none');
        } else {
            // Hide all <tr> elements with class 'folder-row', then show only those with matching data-tagid
            if (selectedValue === 'all') {
                // Show all <tr> elements with class 'folder-row' inside #folder_config
                $('#folder_config tr.folder-row').show();
            } else {
                $('#folder_config tr.folder-row').hide();
            }

            $('#folder_config tr.folder-row[data-tagid="' + selectedValue + '"]').show();
            $('#folder_config tbody .no-row').remove();

            // Check if any rows are visible after filtering
            const checkVisibleRows = () => {
                const visibleRows = $('#folder_config tr.folder-row:visible').length;
                if (visibleRows === 0) {
                    // If no rows are visible, add the "No folders available" message
                    $('#folder_config tbody').append('<tr class="no-row"><td></td><td class="flex justify-center">No folders available</td><td></td></tr>');
                } else {
                    $('#folder_config tbody .no-row').remove();
                }
            };
            // Add 500ms delay to ensure DOM is updated before checking
            setTimeout(checkVisibleRows, 500);
        }
    });

    // Delete link functionality
    $('#deleteSchoolLogo').click(function (event) {
        event.preventDefault();
        const fileInput = document.getElementById('schoolLogo');
        const preview = document.getElementById('schoolLogoPreview');
        const deleteLink = document.getElementById('deleteSchoolLogo');
        const schoolKey = $('#schoolHash').val();
        // Use FormData to handle file uploads
        const formData = new FormData();
        formData.append('schoolKey', schoolKey);
        formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            url: base_url + '/config-school/delete-school-logo',
            method: 'POST',
            data: formData,
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            success: function (response) {
                // Display the uploaded image as a preview if the upload is successful
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    deleteLink.classList.remove('d-none');
                };
                const file = fileInput.files[0];
                if (file) {
                    reader.readAsDataURL(file); // Convert the file to a data URL
                }
            },
            error: function () {
                console.error('Failed to upload the school logo.');
            }
        });
        // Clear the input, hide preview and delete link
        fileInput.value = ''; // Clear the file input
        preview.src = ''; // Clear the preview image
        preview.style.display = 'none'; // Hide the preview
        deleteLink.classList.add('d-none');
    });
});

const SCHOOL_LOGO_VALID_EXTENSIONS = ['jpg', 'jpeg', 'png', 'bmp'];
const SCHOOL_LOGO_MAX_DIMENSION = 2048;
const SCHOOL_LOGO_MIN_LONGEST_SIDE = 1200;
const SCHOOL_LOGO_JPEG_QUALITY = 0.88;

function isValidSchoolLogoFile(file) {
    if (!file) {
        return false;
    }

    const validMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'];
    if (validMimeTypes.includes(file.type)) {
        return true;
    }

    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
    return SCHOOL_LOGO_VALID_EXTENSIONS.includes(extension);
}

function showSchoolLogoToast(status, message) {
    window.dispatchEvent(new CustomEvent('show-toast-message', {
        detail: { status, message }
    }));
}

function normalizeSchoolLogoFile(file) {
    return new Promise((resolve, reject) => {
        const objectUrl = URL.createObjectURL(file);
        const image = new Image();

        image.onload = function () {
            URL.revokeObjectURL(objectUrl);

            let width = image.naturalWidth;
            let height = image.naturalHeight;

            if (!width || !height) {
                reject(new Error('Invalid image dimensions.'));
                return;
            }

            const longestSide = Math.max(width, height);
            let scale = 1;

            if (longestSide > SCHOOL_LOGO_MAX_DIMENSION) {
                scale = SCHOOL_LOGO_MAX_DIMENSION / longestSide;
            } else if (longestSide < SCHOOL_LOGO_MIN_LONGEST_SIDE) {
                scale = SCHOOL_LOGO_MIN_LONGEST_SIDE / longestSide;
            }

            width = Math.max(1, Math.round(width * scale));
            height = Math.max(1, Math.round(height * scale));

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, width, height);
            context.drawImage(image, 0, 0, width, height);

            canvas.toBlob(function (blob) {
                if (!blob) {
                    reject(new Error('Failed to prepare the school logo for upload.'));
                    return;
                }

                const filename = `school_logo_${Date.now()}.jpg`;
                resolve(new File([blob], filename, { type: 'image/jpeg' }));
            }, 'image/jpeg', SCHOOL_LOGO_JPEG_QUALITY);
        };

        image.onerror = function () {
            URL.revokeObjectURL(objectUrl);
            reject(new Error('Failed to read the selected image.'));
        };

        image.src = objectUrl;
    });
}

function uploadSchoolLogoFile(file, schoolKey, preview) {
    const formData = new FormData();
    formData.append('schoolLogo', file);
    formData.append('schoolKey', schoolKey);
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

    return $.ajax({
        url: base_url + '/config-school/upload-school-logo',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    }).done(function () {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        showSchoolLogoToast('success', 'School logo uploaded successfully.');
    });
}

$('#schoolLogoBtn').click(function (event) {
    event.preventDefault();
    $('#schoolLogo').click(); // Trigger the file input click
});

document.getElementById('schoolLogo').addEventListener('change', async function(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('schoolLogoPreview');
    const schoolKey = $('#schoolHash').val();
    const validExtensionsUpper = SCHOOL_LOGO_VALID_EXTENSIONS.map(ext => ext.toUpperCase());

    if (!file || !isValidSchoolLogoFile(file)) {
        showSchoolLogoToast('error', `Please upload a valid image file (${validExtensionsUpper.join(', ')}).`);
        event.target.value = '';
        return;
    }

    let uploadFile = file;

    try {
        uploadFile = await normalizeSchoolLogoFile(file);
    } catch (error) {
        console.error('School logo normalization failed:', error);
        showSchoolLogoToast('error', error.message || 'Failed to prepare the school logo for upload.');
        event.target.value = '';
        return;
    }

    uploadSchoolLogoFile(uploadFile, schoolKey, preview)
        .fail(function (xhr) {
            console.error('Failed to upload the school logo.', xhr);

            let message = 'Failed to upload the school logo.';
            const response = xhr.responseJSON;

            if (response?.message) {
                message = response.message;
            } else if (xhr.status === 403) {
                message = 'Upload was blocked by the server. Please try a different image or contact support.';
            } else if (xhr.status === 422 && response?.errors?.schoolLogo?.[0]) {
                message = response.errors.schoolLogo[0];
            }

            showSchoolLogoToast('error', message);
        })
        .always(function () {
            event.target.value = '';
        });
});

function insertDigitalDownload(modelTag, fieldTag, roleTag, isChecked) {
    var targetUrl = base_url + "/config-school/digital-download/submit";
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var schoolKey = $('#schoolHash').val();
    var formElement = document.getElementById('digital_download_form');
    var formData = formElement ? new FormData(formElement) : new FormData();

    formData.set("schoolKey", schoolKey);
    formData.set(modelTag + "[" + fieldTag + "][" + roleTag + "]", isChecked ? 'true' : 'false');

    $.ajax({
        type: "POST",
        url: targetUrl,
        dataType: "json",
        async: false,
        data: formData, // Use the formData object here
        processData: false,
        headers: {
            'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
        },
        contentType: false,
        success: function (response) {
        },
        error: function (e) {
            //alert("An error occurred: " + e.responseText.message);
        }
    });
}

function hideOrShowJobDependentSections(show) {
    const elements = document.querySelectorAll('.job-dependent-section');
    const action = () => {
        elements.forEach(element => {
            element.style.display = show ? 'block' : 'none';
        });

        if (show) {
            const section = document.getElementById('select_job_access_image');
            if (section) {
                section.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });
            }
        }
    };

    if (show) {
        setTimeout(action, 200);
    } else {
        action();
    }
}
