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

    $('#notifications_enabled_checkbox').on('change', function() {
        // Get the checked state of the checkbox
        const checkedValue = $(this).is(':checked'); // true if checked, false if unchecked
        newValue = 0;
        if(checkedValue){
            newValue = 1;
        }
        const schoolKey = $('#schoolHash').val(); // Retrieve school key

        // Call the sendSchoolChanges function with the appropriate parameters
        sendSchoolChanges('is_email_notification', newValue, schoolKey);
    });

    function insertDigitalDownload(modelTag, fieldTag, roleTag, isChecked){
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
                //console.log(response);
            },
            error: function (e) {
                //alert("An error occurred: " + e.responseText.message);
                //
            }
        });
    }

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
            //console.log(e);
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
        const selectedJobKey = $('#job_select').val();
    
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
        const selectedJobKey = $('#job_select').val();
    
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
                    resolve(response);
                },
                error: function (error) {
                    console.error("Error updating job:", error);
                    reject(error);
                }
            });
        });
    }

    $("#select_season").select2();
    
    let jobsData = [];

    $('#select_season').on('change', function () {
        const selectedSeasonId = $(this).val();
        const selectedSchoolKey = $('#schoolHash').val();
        const selectedSeasonText = $(this).find('option:selected').text();
        $('#SeasoncodeDisplay').text(' - ' + selectedSeasonText).removeClass('d-none');
        $('#jobType, #digital_download, #folder_config').addClass('d-none');
        $('p.alert-message').remove();

        if (selectedSeasonId) {
            $.ajax({
                url: base_url + '/config-school/fetch-jobs',
                method: 'GET',
                data: { ts_season_id: selectedSeasonId, schoolkey: selectedSchoolKey },
                success: function (jobs) {
                    jobsData = jobs;

                    $('#jobSelect').removeClass('d-none');

                    const jobSelect = $('#job_select');
                    jobSelect.empty().append('<option value="">--Choose a Job--</option>');

                    $.each(jobs, function (index, job) {
                        jobSelect.append(`<option value="${job.ts_jobkey}">${job.ts_jobname}</option>`);
                    });
                    
                    $("#job_select").select2();

                    const jobTypeSelect = $('#jobType');
                    jobTypeSelect.empty().append(`
                        <div class="row mt-3">
                            <div class="col-12 m-auto">
                                <p class="h5 lead mb-1"><strong>Please choose a job type to access images in portal</strong></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <select id="job_access_image" name="job_access_image" class="form-control">
                                    <option value="0">--Choose a Job Type to Access Images--</option>
                                    <option value="all">None</option>
                                    <option value="portrait">Portrait / Group</option>
                                    <option value="special_group">Speciality</option>
                                </select>
                            </div>
                        </div>
                    `);
                }
            });
        }
    });

    // var is_portrait_date_clicked = false; 
    // var is_group_date_clicked = false; 

    // $('#portrait_download_start_picker').click(function(){
    //     is_portrait_date_clicked = true; 
    // });

    // $('#group_download_start_picker').click(function(){
    //     is_group_date_clicked = true; 
    // });
 

    // // Event listener for when a date is selected from the portrait download date picker
    // $('#portrait_download_start_picker').on('dp.change', function(e) {
    //     const selectedPortraitDate = e.date ? moment(e.date).format('YYYY-MM-DD HH:mm:ss') : null;
    //     const selectedJob = jobsData.find(job => job.ts_jobkey === $('#job_select').val());

    //     if(selectedJob.download_available_date && !selectedJob.portrait_download_date){
    //         is_portrait_date_clicked = true; 
    //     }
        
    //     if (selectedPortraitDate && is_portrait_date_clicked) {
    //         if (selectedJob) {
    //             selectedJob.portrait_download_date = selectedPortraitDate;
    //             sendJobChanges(selectedJob.ts_jobkey, 'portrait_download_date', selectedPortraitDate);
    //             is_portrait_date_clicked = false; 
    //         }
    //     }
    // });

    // // Event listener for when a date is selected from the group download date picker
    // $('#group_download_start_picker').on('dp.change', function(e) {
    //     const selectedGroupDate = e.date ? moment(e.date).format('YYYY-MM-DD HH:mm:ss') : null;
    //     const selectedJob = jobsData.find(job => job.ts_jobkey === $('#job_select').val());

    //     if(selectedJob.download_available_date && !selectedJob.group_download_date){
    //         is_group_date_clicked = true; 
    //     }
    
    //     if (selectedGroupDate && is_group_date_clicked) {
    //         if (selectedJob) {
    //             selectedJob.group_download_date = selectedGroupDate;
    //             sendJobChanges(selectedJob.ts_jobkey, 'group_download_date', selectedGroupDate);
    //             is_group_date_clicked = false;
    //         }
    //     }
    // });

    // Listen for changes in the input field value
    $('#portrait_download_start_picker').on('change', function() {
        const formattedPortraitDate = $('#portrait_download_start_picker').val();
        const selectedJob = jobsData.find(job => job.ts_jobkey === $('#job_select').val());
        sendJobChanges(selectedJob.ts_jobkey, 'portrait_download_date', formattedPortraitDate);
    });

    $('#group_download_start_picker').on('change', function() {
        const formattedGroupDate = $('#group_download_start_picker').val();
        const selectedJob = jobsData.find(job => job.ts_jobkey === $('#job_select').val());
        sendJobChanges(selectedJob.ts_jobkey, 'group_download_date', formattedGroupDate);
    });


    $('#job_select').on('change', function () {
        const selectedJobKey = $(this).val();
        $('#jobType, #digital_download, #folder_config').addClass('d-none');
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
                    $('#portrait_download_start_picker').flatpickr().destroy();
                }
                if ($('#group_download_start_picker').data("flatpickr")) {
                    $('#group_download_start_picker').flatpickr().destroy();
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
                    $('#portrait_download_start_picker').flatpickr({
                        enableTime: true,
                        dateFormat: "d/m/Y H:i K", // Same format as 'DD/MM/YYYY HH:mm A'
                        disableMobile: true,
                        defaultDate: portraitDateToDisplay, // Set default date
                        minDate: downloadAvailableDateInFormat
                    });

                    // Event listener for when a date is selected from the group download date picker
                    $('#group_download_start_picker').flatpickr({
                        enableTime: true,
                        dateFormat: "d/m/Y H:i K", // Same format as 'DD/MM/YYYY HH:mm A'
                        disableMobile: true,
                        defaultDate: groupDateToDisplay, // Set default date
                        minDate: downloadAvailableDateInFormat
                    });
                }
    
                $("#portrait_download_allowed").prop("checked", !!$("#portrait_download_start_picker").val());
                $("#group_download_allowed").prop("checked", !!$("#group_download_start_picker").val());
    
                const jobTypeSelect = $('#jobType');
                jobTypeSelect.empty().append(`
                    <div class="row mt-3">
                        <div class="col-12 m-auto">
                            <p class="h5 lead mb-1"><strong>Please choose a job type to access images in portal</strong></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <select id="job_access_image" name="job_access_image" class="form-control">
                                <option value="0">--Choose a Job Type to Access Images--</option>
                                <option value="all">None</option>
                                <option value="portrait">Portrait / Group</option>
                                <option value="special_group">Speciality</option>
                            </select>
                        </div>
                    </div>
                `);
    
            } else {
                $('#jobType, #digital_download, #folder_config').addClass('d-none');
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
                    }
                },
                error: function () {
                    console.error('Failed to load folder configuration.');
                }
            });
        }
    });    
        
    //     const selectedJobKey = $(this).val();
    //     $('#jobType, #digital_download, #folder_config').addClass('d-none');
    //     $('p.alert-message').remove();
    
    //     const selectedJob = jobsData.find(job => job.ts_jobkey === selectedJobKey);
    
    //     let portraitDateToDisplay;
    //     let groupDateToDisplay;
    
    //     if (selectedJob) {
    //         $('#jobType, #digital_download').removeClass('d-none');
    
    //         if (selectedJob.download_available_date !== null) {
    //             function parseDate(dateString) {
    //                 return dateString ? moment(dateString, 'YYYY-MM-DD HH:mm:ss') : null;
    //             }
    
    //             const downloadAvailableDate = parseDate(selectedJob.download_available_date);
    //             const portraitDownloadDate = parseDate(selectedJob.portrait_download_date);
    //             const groupDownloadDate = parseDate(selectedJob.group_download_date);
    
    //             portraitDateToDisplay = downloadAvailableDate.toDate();
    //             groupDateToDisplay = downloadAvailableDate.toDate();
    
    //             if (portraitDownloadDate && downloadAvailableDate.isBefore(portraitDownloadDate)) {
    //                 portraitDateToDisplay = portraitDownloadDate.toDate();
    //             }
    
    //             if (groupDownloadDate && downloadAvailableDate.isBefore(groupDownloadDate)) {
    //                 groupDateToDisplay = groupDownloadDate.toDate();
    //             }
    
    //             // Initialize Tempus Dominus datetime pickers
    //             const portraitPicker = new tempusDominus.TempusDominus(
    //                 document.getElementById('portrait_download_start_picker'),
    //                 {
    //                     defaultDate: portraitDateToDisplay,
    //                     restrictions: {
    //                         minDate: downloadAvailableDate.toDate(),
    //                     },
    //                 }
    //             );
    
    //             const groupPicker = new tempusDominus.TempusDominus(
    //                 document.getElementById('group_download_start_picker'),
    //                 {
    //                     defaultDate: groupDateToDisplay,
    //                     restrictions: {
    //                         minDate: downloadAvailableDate.toDate(),
    //                     },
    //                 }
    //             );
    
    //             // Update checkboxes based on values
    //             $("#portrait_download_allowed").prop("checked", !!portraitPicker.dates.getFirstDate());
    //             $("#group_download_allowed").prop("checked", !!groupPicker.dates.getFirstDate());
    
    //             const jobTypeSelect = $('#jobType');
    //             jobTypeSelect.empty().append(`
    //                 <div class="row mt-3">
    //                     <div class="col-12 m-auto">
    //                         <p class="h5 lead mb-1"><strong>Please choose a job type to access images in portal</strong></p>
    //                     </div>
    //                 </div>
    //                 <div class="row">
    //                     <div class="col-lg-3">
    //                         <select id="job_access_image" name="job_access_image" class="form-control">
    //                             <option value="0">--Choose a Job Type to Access Images--</option>
    //                             <option value="all">None</option>
    //                             <option value="portrait">Portrait / Group</option>
    //                             <option value="special_group">Speciality</option>
    //                         </select>
    //                     </div>
    //                 </div>
    //             `);
    //         } else {
    //             $('#jobType, #digital_download, #folder_config').addClass('d-none');
    //             $('#jobTypeMsg').after('<p class="alert-message" style="color:red;">**Currently photos are not processed in Lab. Please contact your local MSP Expert.</p>');
    //         }
    
    //         // Fetch folder configuration via AJAX
    //         $.ajax({
    //             url: base_url + '/config-school/folder-config',
    //             method: 'GET',
    //             data: { folders: selectedJob.Folders },
    //             success: function (response) {
    //                 if (response.html) {
    //                     $('#folder_config').html(response.html); // Correctly insert the HTML from the response
    //                 }
    //             },
    //             error: function () {
    //                 console.error('Failed to load folder configuration.');
    //             }
    //         });
    //     }
    // });        
    
    $('#jobType').on('change', function () {
        $('p.alert-message').remove();
        $('#folder_config').removeClass('d-none');

        const selectedValue = $('#job_access_image').val();

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
            const visibleRows = $('#folder_config tr.folder-row:visible').length;
            if (visibleRows === 0) {
                // If no rows are visible, add the "No folders available" message
                $('#folder_config tbody').append('<tr class="no-row"><td colspan="4">No folders available</td></tr>');
            }else{
                $('#folder_config tbody .no-row').remove();
            }
        }

                // Select all elements and checkboxes
        const selectAllPortrait = document.getElementById('set-is-visible-for-portrait-all');
        const selectNonePortrait = document.getElementById('set-is-visible-for-portrait-none');
        const portraitCheckboxes = document.querySelectorAll('.folder-details-is-visible-for-portrait');

        const selectAllGroup = document.getElementById('set-is-visible-for-group-all');
        const selectNoneGroup  = document.getElementById('set-is-visible-for-group-none');
        const GroupCheckboxes = document.querySelectorAll('.folder-details-is-visible-for-group');

        // Select "All" and "None" checkbox functionality
        // Event listener for Select All functionality
        if (selectAllPortrait) {
            selectAllPortrait.addEventListener('click', function() {
                const folderIdsToUpdate = []; // Initialize this once, outside the loop

                // Loop through all checkboxes and check them
                portraitCheckboxes.forEach(function(checkbox) {
                    if (!checkbox.checked) {
                        checkbox.checked = true;  // Check the checkbox
                        // Optional: Trigger the change event if you need to handle backend updates
                        checkbox.dispatchEvent(new Event('change'));

                        // Add the folderId to the list of IDs to update
                        const folderId = $(checkbox).data('folder-id');
                        folderIdsToUpdate.push(folderId);
                    }
                });

                // Send all folder IDs together in a single request
                if (folderIdsToUpdate.length > 0) {
                    sendFolderChanges(folderIdsToUpdate, "is_visible_for_portrait", 1); // 1 for checked
                }
            });
        }

        if (selectNonePortrait) {
            selectNonePortrait.addEventListener('click', function() {
                const folderIdsToUpdate = []; // Initialize this once, outside the loop

                portraitCheckboxes.forEach(function(checkbox) {
                    if (checkbox.checked) {
                        checkbox.checked = false;  // Uncheck the checkbox
                        // Optional: You can trigger a change event if you need to handle the change in the backend
                        checkbox.dispatchEvent(new Event('change'));

                        // Add the folderId to the list of IDs to update
                        const folderId = $(checkbox).data('folder-id');
                        folderIdsToUpdate.push(folderId);
                    }
                });

                // Send all folder IDs together in a single request
                if (folderIdsToUpdate.length > 0) {
                    sendFolderChanges(folderIdsToUpdate, "is_visible_for_portrait", 0); // 0 for unchecked
                }
            });
        }

        // Select "All" and "None" checkbox functionality
        if (selectAllGroup) {
            selectAllGroup.addEventListener('click', function() {
                const folderIdsToUpdate = []; // Initialize this once, outside the loop

                GroupCheckboxes.forEach(function(checkbox) {
                    if (!checkbox.checked) {
                        checkbox.checked = true;  // Check the checkbox
                        // Optional: You can trigger a change event if you need to handle the change in the backend
                        checkbox.dispatchEvent(new Event('change'));

                        // Add the folderId to the list of IDs to update
                        const folderId = $(checkbox).data('folder-id');
                        folderIdsToUpdate.push(folderId);
                    }
                });

                // Send all folder IDs together in a single request
                if (folderIdsToUpdate.length > 0) {
                    sendFolderChanges(folderIdsToUpdate, "is_visible_for_group", 1); // 1 for checked
                }
            });
        }

        if (selectNoneGroup) {
            selectNoneGroup.addEventListener('click', function() {
                const folderIdsToUpdate = []; // Initialize this once, outside the loop

                GroupCheckboxes.forEach(function(checkbox) {
                    if (checkbox.checked) {
                        checkbox.checked = false;  // Uncheck the checkbox
                        // Optional: You can trigger a change event if you need to handle the change in the backend
                        checkbox.dispatchEvent(new Event('change'));

                        // Add the folderId to the list of IDs to update
                        const folderId = $(checkbox).data('folder-id');
                        folderIdsToUpdate.push(folderId);
                    }
                });

                // Send all folder IDs together in a single request
                if (folderIdsToUpdate.length > 0) {
                    sendFolderChanges(folderIdsToUpdate, "is_visible_for_group", 0); // 1 for checked
                }
            });
        }
    });


    // document.addEventListener('DOMContentLoaded', function() {

    // });

    var modelTag;
    var fieldTag;
    var roleTag;
    var isChecked;

    var multiSelectOptionsDefault = {
        buttonWidth: '100%',
        buttonClass: 'btn btn-default border',
        onChange: function (option, checked, select) {
            modelTag = $(option).parent().data('model');
            fieldTag = $(option).parent().data('field');
            roleTag = $(option).val();
            isChecked = checked ? true : false;

            insertDigitalDownload(modelTag, fieldTag, roleTag, isChecked);
        },
    };

    //multi-selects for the notifications
    $('#download_portrait').multiselect(multiSelectOptionsDefault);
    $('#download_group').multiselect(multiSelectOptionsDefault);
    $('#download_schoolPhoto').multiselect(multiSelectOptionsDefault);
    // $('#notification_portrait').multiselect(multiSelectOptionsDefault);
    // $('#notification_group').multiselect(multiSelectOptionsDefault);
    // $('#notification_schoolPhoto').multiselect(multiSelectOptionsDefault); 

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

document.getElementById('schoolLogo').addEventListener('change', function(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('schoolLogoPreview');
    const deleteLink = document.getElementById('deleteSchoolLogo');
    const schoolKey = $('#schoolHash').val();

    // Check if a file is selected and validate file type
    if (file && (file.type === 'image/jpeg' || file.type === 'image/png')) {
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
                    deleteLink.classList.remove('d-none');
                };
                reader.readAsDataURL(file); // Convert the file to a data URL
            },
            error: function () {
                console.error('Failed to upload the school logo.');
            }
        });
    } else {
        // Display an error message or reset the preview
        alert('Please upload a valid image file (JPG, JPEG, PNG).');
        preview.src = ''; // Clear the preview image
        preview.style.display = 'none'; // Hide the preview
        deleteLink.style.display = 'none'; // Hide the delete link
        event.target.value = ''; // Reset the file input
    }
});

// Checkbox in Notification Email
document.addEventListener("DOMContentLoaded", function() {
    const checkbox = document.getElementById("notifications_enabled_checkbox");
    const digitalDownloadStart = document.getElementById("digital_download_start");

    // Function to toggle visibility
    function toggleDigitalDownload() {
        if (checkbox.checked) {
            digitalDownloadStart.classList.remove("d-none");
        } else {
            digitalDownloadStart.classList.add("d-none");
        }
    }

    // Check visibility on page load
    toggleDigitalDownload();

    // Add event listener to toggle on checkbox change
    checkbox.addEventListener("change", toggleDigitalDownload);
});