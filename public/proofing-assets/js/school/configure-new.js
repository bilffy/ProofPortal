jQuery(document).ready(function($) {
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
            url: base_url+"/school-change/submit",
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

    //Job Update
    $(document).on('change', '.folder-details-is-visible-for-portrait', function() {
        // Get the checked state of the checkbox
        const checkedValue = $(this).is(':checked'); // true if checked, false if unchecked
        const newValue = checkedValue ? 1 : 0;
    
        // Get folder and job-related data
        const folderId = $(this).data('folder-id');
        const field = "is_visible_for_portrait";
        const selectedJobKey = $('#select_job').val();
    
        // Find the selected job in jobsData
        const selectedJob = jobsData.find(job => job.ts_jobkey === selectedJobKey);
        if (selectedJob) {
            const selectedFolderName = $(this).data('folder-name');
    
            // Find the specific folder within the selected job's Folders array
            const selectedFolder = selectedJob.Folders.find(folder => folder.ts_foldername === selectedFolderName);
            
            // Update the folder property if the folder was found
            if (selectedFolder) {
                selectedFolder.is_visible_for_portrait = newValue;
                sendFolderChanges(folderId, field, newValue);
            }
        }
    });

    $(document).on('change', '.folder-details-is-visible-for-group', function() {
        // Get the checked state of the checkbox
        const checkedValue = $(this).is(':checked'); // true if checked, false if unchecked
        const newValue = checkedValue ? 1 : 0;
    
        // Get folder and job-related data
        const folderId = $(this).data('folder-id');
        const field = "is_visible_for_group";
        const selectedJobKey = $('#select_job').val();
    
        // Find the selected job in jobsData
        const selectedJob = jobsData.find(job => job.ts_jobkey === selectedJobKey);
        if (selectedJob) {
            const selectedFolderName = $(this).data('folder-name');
    
            // Find the specific folder within the selected job's Folders array
            const selectedFolder = selectedJob.Folders.find(folder => folder.ts_foldername === selectedFolderName);
            
            // Update the folder property if the folder was found
            if (selectedFolder) {
                selectedFolder.is_visible_for_group = newValue;
                sendFolderChanges(folderId, field, newValue);
            }
        }
    });    
    
    function sendFolderChanges(folderId, field, newData) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("field", field);
            formData.append("newValue", newData);
            formData.append("folderId", folderId);
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
    
            $.ajax({
                dataType: 'json',
                type: "POST",
                url: base_url + "/folder-change/submit",
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
    
    let jobsData = [];
    $('#select_season').on('change', function () {
        const selectedSeasonId = $(this).val();
        const selectedSchoolKey = $('#schoolHash').val();
        const selectedSeasonText = $(this).find('option:selected').text();
        $('#SeasoncodeDisplay').text(' - ' + selectedSeasonText).removeClass('d-none');
        hideOrShowJobDependentSections(false);

        $('#digital_download').addClass('d-none');
        $('p.alert-message').remove();

        if ('none' === selectedSeasonId) {
            $('#select_job').parent().hide();
        } else {
            $('#select_job').parent().show();
        }

        if (selectedSeasonId && 'none' !== selectedSeasonId) {
            $('#job-select-loading').removeClass('d-none'); // show loading spinner
            $('#select_job').next(".select2-container").addClass('d-none'); // hide dropdown container
            $('#no-jobs-msg').addClass('d-none'); // hide spinner
            $.ajax({
                url: base_url + '/config-school/fetch-jobs',
                method: 'GET',
                data: { ts_season_id: selectedSeasonId, schoolkey: selectedSchoolKey },
                success: function (jobs) {
                    jobsData = jobs;
                    const jobSelect = $('#select_job');
                    if (jobsData.length === 0) {
                        $('#no-jobs-msg').removeClass('d-none');
                        $('#select_job').next(".select2-container").addClass('d-none');
                    } else {
                        $('#no-jobs-msg').addClass('d-none');
                        $('#select_job').next(".select2-container").removeClass('d-none');
                        jobSelect.empty().append('<option value="">Choose a Job</option>');
    
                        $.each(jobs, function (index, job) {
                            jobSelect.append(`<option value="${job.ts_jobkey}">${job.ts_jobname}</option>`);
                        });
                    }
                    $('#job-select-loading').addClass('d-none'); // hide loading spinner
                },
                error: function (error) {
                    console.error('Failed to fetch jobs.');
                    console.error(error);
                    $('#no-jobs-msg').removeClass('d-none');
                    $('#job-select-loading').addClass('d-none'); // hide loading spinner
                }
            });
        }
    });

    // Listen for changes in the input field value
    $('#portrait_download_start_picker').on('change', function() {
        const formattedPortraitDate = $('#portrait_download_start_picker').val();
        const selectedJob = jobsData.find(job => job.ts_jobkey === $('#select_job').val());
        sendJobChanges(selectedJob.ts_jobkey, 'portrait_download_date', formattedPortraitDate);
    });

    $('#group_download_start_picker').on('change', function() {
        const formattedGroupDate = $('#group_download_start_picker').val();
        const selectedJob = jobsData.find(job => job.ts_jobkey === $('#select_job').val());
        sendJobChanges(selectedJob.ts_jobkey, 'group_download_date', formattedGroupDate);
    });


    $('#select_job').on('change', function () {
        const isGroupVisible = $("#is-group-visible").val();
        const selectedJobKey = $(this).val();
        $('#jobType, #digital_download').addClass('d-none');
        $('p.alert-message').remove();
    
        const selectedJob = jobsData.find(job => job.ts_jobkey === selectedJobKey);
        
        let portraitDateToDisplay;
        let groupDateToDisplay;
    
        if (selectedJob) {
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
                $('#jobTypeMsg').after('<p class="alert-message" style="color:red;">**Currently photos are not processed in Lab. Please contact your local MSP Expert.</p>');
            }
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
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

        if (selectedValue === '0'){
            $('#folder_config').addClass('d-none');
        } else {
            // Hide all <tr> elements with class 'folder-row', then show only those with matching data-tagid
            if (selectedValue === 'all') {
                // Show all <tr> elements with class 'folder-row' inside #folder_config
                $('#folder_config tr.folder-row').show();
            }else{
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
                }else{
                    $('#folder_config tbody .no-row').remove();
                }
            };
            // Add 500ms delay to ensure DOM is updated before checking
            setTimeout(checkVisibleRows, 500);
        }

    function handleSelectColumnAction(checkboxId, checkboxClass) {
        const inputCheckbox = document.getElementById(checkboxId);
        if (!inputCheckbox) return;
        inputCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            const selectedJobType = document.getElementById('select_job_access_image').value;
            const targetRows = selectedJobType !== 'all' ? document.querySelectorAll(`tr[data-tagid="${selectedJobType}"]`) : [document];
            const folderIdsToUpdate = [];

            targetRows.forEach(function(row) {
                const checkboxes = row.querySelectorAll(`.${checkboxClass}`);
                checkboxes.forEach(function(checkbox) {
                    if (checkbox.checked !== isChecked) {
                        checkbox.checked = isChecked;
                        checkbox.dispatchEvent(new Event('change'));
                        const folderId = checkbox.getAttribute('data-folder-id');
                        folderIdsToUpdate.push(folderId);
                    }
                });
            });

            if (folderIdsToUpdate.length > 0) {
                sendFolderChanges(folderIdsToUpdate, checkboxClass === 'folder-details-is-visible-for-portrait' ? "is_visible_for_portrait" : "is_visible_for_group", isChecked ? 1 : 0);
            }
        });
    }

    handleSelectColumnAction(
        'set-is-visible-for-portrait',
        'folder-details-is-visible-for-portrait'
    );

    handleSelectColumnAction(
        'set-is-visible-for-group',
        'folder-details-is-visible-for-group'
    );

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
                    reader.onload = function(e) {
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

$('#schoolLogoBtn').click(function (event) {
    event.preventDefault();
    $('#schoolLogo').click(); // Trigger the file input click
});

document.getElementById('schoolLogo').addEventListener('change', function(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('schoolLogoPreview');
    // Remove delete link since it does not match the current flow as of v1.1.30.2
    // const deleteLink = document.getElementById('deleteSchoolLogo');
    const schoolKey = $('#schoolHash').val();

    // Check if a file is selected and validate file type
    // TODO: Use the ImageHelper to get valid image extensions from backend
    const validExtensions = ['jpg', 'jpeg', 'png', 'bmp'];
    const validExtensionsUpper = validExtensions.map(ext => ext.toUpperCase());
    const prefixExtensions = validExtensions.map(ext => 'image/' + ext);
    // Check if the file type is in the list of valid extensions
    if (file && prefixExtensions.includes(file.type)) {
        // Use FormData to handle file uploads
        const formData = new FormData();
        formData.append('schoolLogo', file);
        formData.append('schoolKey', schoolKey);
        formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            url: base_url + '/config-school/upload-school-logo',
            method: 'POST',
            data: formData,
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            success: function (response) {
                // Display the uploaded image as a preview if the upload is successful
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    // deleteLink.classList.remove('d-none');
                };
                reader.readAsDataURL(file); // Convert the file to a data URL
            },
            error: function () {
                console.error('Failed to upload the school logo.');
            }
        });
    } else {
        // Display an error message or reset the preview
        const msg = `Please upload a valid image file (${validExtensionsUpper.join(', ')}).`;
        window.dispatchEvent(new CustomEvent('show-toast-message', { detail: { status: 'error', message: msg } }));
        // Remove resetting preview since it does not match the current flow as of v1.1.30.2
        // preview.src = ''; // Clear the preview image
        // preview.style.display = 'none'; // Hide the preview
        // deleteLink.style.display = 'none'; // Hide the delete link
        event.target.value = ''; // Reset the file input
    }
});

function insertDigitalDownload(modelTag, fieldTag, roleTag, isChecked) {
    var targetUrl = base_url+"/config-school/digital-download/submit";
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var schoolKey = $('#schoolHash').val();

    var formData = new FormData($('#digital_download_form')[0]);
    formData.append("schoolKey", schoolKey); // Append the jobHash to the form data
    formData.append(modelTag + "[" + fieldTag + "][" + roleTag + "]", isChecked);

    $.ajax({
        type: "POST",
        url:targetUrl,
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
    setTimeout(() => {
        elements.forEach(element => {
            if (show) {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
        });

        const section = document.getElementById('select_job_access_image');
        section.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest'
        });
    }, 200);
}

// Checkbox in Notification Email
document.addEventListener("DOMContentLoaded", function() {
    $("#select_season").select2();
    $("#select_job").select2();
    $("#select_job_access_image").select2();
    $('#select_job').parent().hide();
    hideOrShowJobDependentSections(false);
    // Select all checkbox inputs with the class 'img-permission'
    const permissionCheckboxes = document.querySelectorAll('input[type="checkbox"].img-permission');
    // Add an 'onchange' event listener to each checkbox
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const { model, field } = this.parentElement.dataset;
            const role = this.value;
            const checked = this.checked;

            insertDigitalDownload(model, field, role, checked);
        });
    });
});