
            $('#notifications_enabled_checkbox').on('change', function (e) {
                adjustReviewDateIsEnabled($(this));
                if ($(this).is(':checked')) {
                    $('#review-matrix').removeClass('d-none');
                } else {
                    $('#review-matrix').addClass('d-none');
                }
            });

            /*
            Main AJAX call to send the date
            */
            function sendAjaxRequest(targetUrl, formData) {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                  type: "POST",
                  url: targetUrl,
                  async: true,
                  data: formData,
                  cache: false,
                  contentType: false,
                  processData: false,
                  headers: {
                    'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                  },
                  timeout: 60000,
                  success: function (response) {
                    console.log('saved');
                  },
                  error: function (e) {
                    // console.log('An error occurred:', e);
                  }
                });
            }

            function adjustReviewDates(dateObject, reviewDataType) {
                var targetUrl = base_url+"/franchise/config-job/proofing-timeline/submit";
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                var jobHash = document.querySelector('input[name="jobHash"]').value;

                var formData = new FormData();
                formData.append("modifyReviewDate", true);
                formData.append("dataType", reviewDataType);
                formData.append("jobHash", jobHash);
                formData.append("date", dateObject);
    
                // if (dateObject) {
                //     formData.append("date", dateObject.format('YYYY-MM-DD HH:mm:ss'));
                //     formData.append("dateArray", dateObject.format('YYYY,MM,DD,HH,mm,ss'));
                // } else {
                //     formData.append("date", null);
                //     formData.append("dateArray", null);
                // }
    
                $.ajax({
                    type: "POST",
                    url: targetUrl,
                    async: true,
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                    },
                    timeout: 60000,
                    success: function (response) {
                        console.log('saved');
                    },
                    error: function (e) {
                        // console.log('An error occurred:', e);
                    }
                })
            }

            /*
            Main AJAX call to send the Notification parameters
            */
            function adjustReviewDateIsEnabled(data) {
                var targetUrl = base_url+"/franchise/config-job/email-notifications/enable";
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                var jobHash = document.querySelector('input[name="jobHash"]').value;
    
                var formData = new FormData();
                formData.append("isReviewDateEnabled", data.is(':checked'));
                formData.append("jobHash", jobHash);
    
                $.ajax({
                    type: "POST",
                    url: targetUrl,
                    async: true,
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                    },
                    timeout: 60000,
    
                    success: function (response) {
                       //console.log(response);
                    },
                    error: function (e) {
                        //alert("An error occurred: " + e.responseText.message);
                       //console.log(e);
                    }
                })
    
            }

            function debouncedInsertEmailNotification(modelTag, fieldTag, roleTag) {
                clearTimeout(debouncedInsertEmailNotification.timer);
                debouncedInsertEmailNotification.timer = setTimeout(() => {
                    insertEmailNotification(modelTag, fieldTag, roleTag);
                }, 300); // Adjust debounce interval as needed
            }

            function insertEmailNotification(modelTag,fieldTag,roleTag){
                var targetUrl = base_url+"/franchise/config-job/email-notifications/submit";
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                var jobHash = document.querySelector('input[name="jobHash"]').value;

                var formData = new FormData($('#notification_email_form')[0]);
                formData.append("jobHash", jobHash); // Append the jobHash to the form data

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

    
            $(document).ready(function () {
    
                var answerReal = $('#math-question-field').attr('data-c');
                var answerGiven = 0;
                var jobHash = document.querySelector('input[name="job"]').value;
                var deleteUrl = base_url+"/franchise/delete-job/"+jobHash;
                var deleteUrlDisabled = '##';
        
                $("#math-question-field").keyup(function () {
                    answerGiven = $(this).val();
                    if (answerReal == answerGiven) {
                        $("#math-question-button-delete").removeClass('disabled').attr("href", deleteUrl);
                    } else {
                        $("#math-question-button-delete").addClass('disabled').attr("href", deleteUrlDisabled);
                    }
        
                });
        
            });

            // $(document).ready(function () {
            //     // preload images
            //     $('.modal-thumb').each(function () {
            //         var img = new Image();
            //         img.src = $(this).data('modal-src');
            //     });
        
            //     $('.modal-thumb').on('click', function () {
            //         var title = $(this).data('modal-title'),
            //             src = $(this).data('modal-src');
        
            //         $('#modal .modal-title').text(title);
            //         $('#modal img').attr('src', src);
        
            //         $('#modal').modal('show');
            //     });
            // });
        
            //Group Image Upload
            $(document).ready(function () {
        
                var Upload = function (file, folder_key, folder_name) {
                    this.file = file;
                    this.folder_key = folder_key;
                    this.folder_name = folder_name;
                    this.progress_bar_id = "#progress-wrp-" + folder_key;
                };
        
                Upload.prototype.getType = function () {
                    return this.file.type;
                };
        
                Upload.prototype.getSize = function () {
                    return this.file.size;
                };
        
                Upload.prototype.getName = function () {
                    return this.file.name;
                };
        
                Upload.prototype.doUpload = function () {
                    var that = this;
                    var formData = new FormData();
                    var targetUrl = base_url+"/franchise/config-job/upload-file";
                    var csrfToken = $('meta[name="csrf-token"]').attr('content');
        
                    // add assoc key values, this will be posts values
                    formData.append("file", that.file, that.getName());
                    formData.append("upload_file", true);
                    formData.append("folder_key", that.folder_key);
                    formData.append("folder_name", that.folder_name);
        
                    $.ajax({
                        type: "POST",
                        url: targetUrl,
                        async: true,
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,headers: {
                            'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                        },
                        timeout: 60000,
        
                        xhr: function () {
                            var xhr = new window.XMLHttpRequest();
        
                            xhr.upload.progress_bar_id = that.progress_bar_id;
                            xhr.upload.addEventListener("progress", that.progressHandling, false);
        
                            xhr.progress_bar_id = that.progress_bar_id;
                            xhr.addEventListener("progress", that.progressHandling, false);
        
                            return xhr;
                        },
        
                        success: function (data) {
                            that.successHandling(data);
                        },
        
                        error: function (error) {
                            that.errorHandling(error);
                        }
                    });
                };
        
                Upload.prototype.progressHandling = function (evt) {
                   //console.log(this.progress_bar_id + " div.progress-bar");
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        var percentCompleteAsWhole = Math.ceil(percentComplete * 100);
                       //console.log(percentCompleteAsWhole);
                        $(this.progress_bar_id + " div.progress-bar").css({width: percentCompleteAsWhole + '%'}).attr('aria-valuenow', percentCompleteAsWhole);
                        $(this.progress_bar_id + " div.progress-bar").text(percentCompleteAsWhole + '%');
                        if (percentComplete === 1) {
                            $(this.progress_bar_id + " div.progress-bar").text('Upload Complete!');
                        }
                    }
                };
        
                Upload.prototype.successHandling = function (data) {
                    if (data.error) {
                        $("#" + this.folder_key + "-bar").addClass('d-none');
                        $("#" + this.folder_key + "-error").removeClass('d-none').text(data.error.data.message);
                    }
        
                    if (data.full_url) {
                        $("#" + this.folder_key + "-image").attr('src', data.full_url);
                        $("#" + this.folder_key + "-delete").removeClass("d-none").addClass('d-block');
                    }
                };
        
                Upload.prototype.errorHandling = function (error) {
                   //console.log(error);
                };
        
        
                $("input.traditional-photo-upload").on("change", function (e) {
                    var file = $(this)[0].files["0"];
                    var folder_key = $(this).attr('id');
                    var folder_name = $(this).attr('name');
                    var upload = new Upload(file, folder_key, folder_name);
        
                    $("#" + folder_key + "-bar").removeClass('d-none');
                    $(this.progress_bar_id + " div.progress-bar").css({width: 0 + '%'}).attr('aria-valuenow', 0);
                    $(this.progress_bar_id + " div.progress-bar").text("0%");
                    $("#" + folder_key + "-error").addClass('d-none');
        
                    // maybe check size or type here with upload.getSize() and upload.getType()
        
                    // execute upload
                    upload.doUpload();
                });
        
            });
        
            /**
             * Control for the Checkboxes
             */
            $(document).ready(function () {
               applyEditCapabilities();
        
        
                //Is Visible for Proofing
                $("#set-is-visible-for-proofing-all").click(function () {
                    $("input[id^='is-visible-for-proofing-']").prop('checked', true);
                    processCheckboxes('is-visible-for-proofing-','is_visible_for_proofing');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-visible-for-proofing-none").click(function () {
                    $("input[id^='is-visible-for-proofing-']").prop('checked', false);
                    processCheckboxes('is-visible-for-proofing-','is_visible_for_proofing');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-visible-for-proofing-']").change(function () {
                   processCheckboxes('is-visible-for-proofing-','is_visible_for_proofing');
                   applyEditCapabilities();
                });
                //==============================================================
        
        
                //Is Edit Portraits
                $("#set-is-edit-portraits-all").click(function () {
                    $("input[id^='is-edit-portraits-']").prop('checked', true);
                    processCheckboxes('is-edit-portraits-','is_edit_portraits');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-portraits-none").click(function () {
                    $("input[id^='is-edit-portraits-']").prop('checked', false);
                    processCheckboxes('is-edit-portraits-','is_edit_portraits');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-portraits-']").change(function () {
                    processCheckboxes('is-edit-portraits-','is_edit_portraits');
                    applyEditCapabilities();
                });
                //==============================================================
        
        
                //Is Edit Group
                $("#set-is-edit-group-all").click(function () {
                    $("input[id^='is-edit-group-']").prop('checked', true);
                    processCheckboxes('is-edit-group-','is_edit_groups');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-group-none").click(function () {
                    $("input[id^='is-edit-group-']").prop('checked', false);
                    processCheckboxes('is-edit-group-','is_edit_groups');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-group-']").change(function () {
                    processCheckboxes('is-edit-group-','is_edit_groups');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                //Is Subject List Allowed
                $("#is-subject-list-allowed-all").click(function () {
                    $("input[id^='is-subject-list-allowed-']").prop('checked', true);
                    processCheckboxes('is-subject-list-allowed-','is_subject_list_allowed');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#is-subject-list-allowed-none").click(function () {
                    $("input[id^='is-subject-list-allowed-']").prop('checked', false);
                    processCheckboxes('is-subject-list-allowed-','is_subject_list_allowed');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-subject-list-allowed-']").change(function () {
                    processCheckboxes('is-subject-list-allowed-','is_subject_list_allowed');
                    applyEditCapabilities();
                });
                //==============================================================
        
        
                //Is Edit Principal
                $("#set-is-edit-principal-all").click(function () {
                    $("input[id^='is-edit-principal-']").prop('checked', true);
                    processCheckboxes('is-edit-principal-','is_edit_principal');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-principal-none").click(function () {
                    $("input[id^='is-edit-principal-']").prop('checked', false);
                    processCheckboxes('is-edit-principal-','is_edit_principal');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-principal-']").change(function () {
                    processCheckboxes('is-edit-principal-','is_edit_principal');
                    applyEditCapabilities();
                });
                //==============================================================
        
        
                //Is Edit Deputy
                $("#set-is-edit-deputy-all").click(function () {
                    $("input[id^='is-edit-deputy-']").prop('checked', true);
                    processCheckboxes('is-edit-deputy-','is_edit_deputy');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-deputy-none").click(function () {
                    $("input[id^='is-edit-deputy-']").prop('checked', false);
                    processCheckboxes('is-edit-deputy-','is_edit_deputy');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-deputy-']").change(function () {
                    processCheckboxes('is-edit-deputy-','is_edit_deputy');
                    applyEditCapabilities();
                });
                //==============================================================
        
        
                //Is Edit Teacher
                $("#set-is-edit-teacher-all").click(function () {
                    $("input[id^='is-edit-teacher-']").prop('checked', true);
                    processCheckboxes('is-edit-teacher-','is_edit_teacher');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-teacher-none").click(function () {
                    $("input[id^='is-edit-teacher-']").prop('checked', false);
                    processCheckboxes('is-edit-teacher-','is_edit_teacher');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-teacher-']").change(function () {
                    processCheckboxes('is-edit-teacher-','is_edit_teacher');
                    applyEditCapabilities();
                });
                //==============================================================
        
        
                //Is Edit Salutation
                $("#set-is-edit-salutation-all").click(function () {
                    $("input[id^='is-edit-salutation-']").prop('checked', true);
                    processCheckboxes('is-edit-salutation-','is_edit_salutation');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-salutation-none").click(function () {
                    $("input[id^='is-edit-salutation-']").prop('checked', false);
                    processCheckboxes('is-edit-salutation-','is_edit_salutation');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-salutation-']").change(function () {
                    processCheckboxes('is-edit-salutation-','is_edit_salutation');
                    applyEditCapabilities();
                });
                //==============================================================
        
        
                //Is Edit Job Title
                $("#set-is-edit-job-title-all").click(function () {
                    $("input[id^='is-edit-job-title-']").prop('checked', true);
                    processCheckboxes('is-edit-job-title-','is_edit_job_title');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-job-title-none").click(function () {
                    $("input[id^='is-edit-job-title-']").prop('checked', false);
                    processCheckboxes('is-edit-job-title-','is_edit_job_title');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-job-title-']").change(function () {
                    processCheckboxes('is-edit-job-title-','is_edit_job_title');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                //Is Show Salutation in Portrait
                $("#set-is-edit-job-show-salutation-portrait-all").click(function () {
                    $("input[id^='is-edit-job-show-salutation-portrait-']").prop('checked', true);
                    processCheckboxes('is-edit-job-show-salutation-portrait-','show_salutation_portraits');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-job-show-salutation-portrait-none").click(function () {
                    $("input[id^='is-edit-job-show-salutation-portrait-']").prop('checked', false);
                    processCheckboxes('is-edit-job-show-salutation-portrait-','show_salutation_portraits');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-job-show-salutation-portrait-']").change(function () {
                    processCheckboxes('is-edit-job-show-salutation-portrait-','show_salutation_portraits');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                //Is Show Salutation in Group
                $("#set-is-edit-job-show-salutation-group-all").click(function () {
                    $("input[id^='is-edit-job-show-salutation-group-']").prop('checked', true);
                    processCheckboxes('is-edit-job-show-salutation-group-','show_salutation_groups');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-job-show-salutation-group-none").click(function () {
                    $("input[id^='is-edit-job-show-salutation-group-']").prop('checked', false);
                    processCheckboxes('is-edit-job-show-salutation-group-','show_salutation_groups');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-job-show-salutation-group-']").change(function () {
                    processCheckboxes('is-edit-job-show-salutation-group-','show_salutation_groups');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                //Is Show Prefix Suffix in Portrait
                $("#set-is-edit-job-prefix-suffix-portrait-all").click(function () {
                    $("input[id^='is-edit-job-prefix-suffix-portrait-']").prop('checked', true);
                    processCheckboxes('is-edit-job-prefix-suffix-portrait-','show_prefix_suffix_portraits');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-job-prefix-suffix-portrait-none").click(function () {
                    $("input[id^='is-edit-job-prefix-suffix-portrait-']").prop('checked', false);
                    processCheckboxes('is-edit-job-prefix-suffix-portrait-','show_prefix_suffix_portraits');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-job-prefix-suffix-portrait-']").change(function () {
                    processCheckboxes('is-edit-job-prefix-suffix-portrait-','show_prefix_suffix_portraits');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                //Is Show Prefix Suffix in Group
                $("#set-is-edit-job-prefix-suffix-group-all").click(function () {
                    $("input[id^='is-edit-job-prefix-suffix-group-']").prop('checked', true);
                    processCheckboxes('is-edit-job-prefix-suffix-group-','show_prefix_suffix_groups');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-edit-job-prefix-suffix-group-none").click(function () {
                    $("input[id^='is-edit-job-prefix-suffix-group-']").prop('checked', false);
                    processCheckboxes('is-edit-job-prefix-suffix-group-','show_prefix_suffix_groups');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-edit-job-prefix-suffix-group-']").change(function () {
                    processCheckboxes('is-edit-job-prefix-suffix-group-','show_prefix_suffix_groups');
                    applyEditCapabilities();
                });
        
                //==============================================================

                
                //Is Visible Portrait
                $("#set-is-visible-for-portrait-all").click(function () {
                    $("input[id^='is-visible-for-portrait-']").prop('checked', true);
                    processCheckboxes('is-visible-for-portrait-','is_visible_portrait');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-visible-for-portrait-none").click(function () {
                    $("input[id^='is-visible-for-portrait-']").prop('checked', false);
                    processCheckboxes('is-visible-for-portrait-','is_visible_portrait');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-visible-for-portrait-']").change(function () {
                    processCheckboxes('is-visible-for-portrait-','is_visible_portrait');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                //Is Visible Group
                $("#set-is-visible-for-group-all").click(function () {
                    $("input[id^='is-visible-for-group-']").prop('checked', true);
                    processCheckboxes('is-visible-for-group-','is_visible_for_group');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-visible-for-group-none").click(function () {
                    $("input[id^='is-visible-for-group-']").prop('checked', false);
                    processCheckboxes('is-visible-for-group-','is_visible_for_group');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-visible-for-group-']").change(function () {
                    processCheckboxes('is-visible-for-group-','is_visible_for_group');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                //Is Visible School Photo
                $("#set-is-visible-for-school-all").click(function () {
                    $("input[id^='is-visible-for-school-']").prop('checked', true);
                    processCheckboxes('is-visible-for-school-','is_visible_portrait');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("#set-is-visible-for-school-none").click(function () {
                    $("input[id^='is-visible-for-school-']").prop('checked', false);
                    processCheckboxes('is-visible-for-school-','is_visible_portrait');
                    applyEditCapabilities();
                }).css('cursor', 'pointer');
        
                $("input[id^='is-visible-for-school-']").change(function () {
                    processCheckboxes('is-visible-for-school-','is_visible_portrait');
                    applyEditCapabilities();
                });
        
                //==============================================================
        
        
                /**
                 * Function to loop every checkbox and control the GUI based on if selected or not.
                 * Need to loop in specific order as children need to override parent
                 */
                function applyEditCapabilities() {
        
                    let folderKeysValue = document.querySelector('input[name="allFolderKeys"]').value;

                    // Parse the JSON string back into an array
                    let folderKeys = JSON.parse(folderKeysValue);

                    var folderKey;
        
                    var isVisibleForProofingCheckbox;
                    var isEditPortraitsCheckbox;
                    var isEditGroupCheckbox;
                    var isSubjectListAllowedCheckbox;
                    var isEditPrincipalCheckbox;
                    var isEditDeputyCheckbox;
                    var isEditTeacherCheckbox;
        
                    var i;
                    for (i = 0; i < folderKeys.length; ++i) {
                        folderKey = folderKeys[i];
        
                        //checkbox selectors
                        isVisibleForProofingCheckbox = $("#is-visible-for-proofing-" + folderKey);
                        isEditPortraitsCheckbox = $("#is-edit-portraits-" + folderKey);
                        isEditGroupCheckbox = $("#is-edit-group-" + folderKey);
                        isSubjectListAllowedCheckbox = $("#is-subject-list-allowed-" + folderKey);
                        isEditPrincipalCheckbox = $("#is-edit-principal-" + folderKey);
                        isEditDeputyCheckbox = $("#is-edit-deputy-" + folderKey);
                        isEditTeacherCheckbox = $("#is-edit-teacher-" + folderKey);
                        isEditTeacherCheckbox = $("#is-edit-salutation-" + folderKey);
                        isEditTeacherCheckbox = $("#is-edit-job-title-" + folderKey);
        
        
                        //main isVisibleForProofing Checkbox
                        var isVisibleForProofingCheckboxTicked = isVisibleForProofingCheckbox.prop('checked');
                        if (isVisibleForProofingCheckboxTicked) {
                            $(".is-edit-portraits--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-group--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-subject-list-allowed--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".traditional-photo-upload--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-principal--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-deputy--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-teacher--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-salutation--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-job-title--" + folderKey).css('pointer-events', '').css('opacity', 1);
                        } else {
                            $(".is-edit-portraits--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-group--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-subject-list-allowed--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".traditional-photo-upload--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-principal--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-deputy--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-teacher--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-salutation--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-job-title--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                        }
        
        
                        //isEditGroup Checkbox
                        var isEditGroupCheckboxTicked = isEditGroupCheckbox.prop('checked');
                        if (isEditGroupCheckboxTicked && isVisibleForProofingCheckboxTicked) {
                            $(".is-subject-list-allowed--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".traditional-photo-upload--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-principal--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-deputy--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-teacher--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-salutation--" + folderKey).css('pointer-events', '').css('opacity', 1);
                            $(".is-edit-job-title--" + folderKey).css('pointer-events', '').css('opacity', 1);
                        } else {
                            $(".is-subject-list-allowed--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".traditional-photo-upload--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-principal--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-deputy--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-teacher--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-salutation--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                            $(".is-edit-job-title--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                        }
        
                    }
                }
        
        
                function processCheckboxes(selector,fieldName) {
                    //var folderDetailTmpField;
                    var folderDetailTmpId;
                    //var folderDetailTmpName;
                    var folderDetailTmpIsChecked;
        
                    //var activeFolderListsField = [];
                    var activeFolderListsIds = [];
                    //var activeFolderListsNames = [];
                    //var inactiveFolderListsField = [];
                    var inactiveFolderListsIds = [];
                    //var inactiveFolderListsNames = [];
        
        
                    $("input[id^='" + selector + "']").each(function () {
        
                        //folderDetailTmpField = $(this).attr('name');
                        folderDetailTmpId = $(this).data('folder-id');
                        //folderDetailTmpName = $(this).data('folder-name');
                        folderDetailTmpIsChecked = $(this).is(':checked');
        
                        if (folderDetailTmpIsChecked) {
                            //activeFolderListsField.push(folderDetailTmpField);
                            activeFolderListsIds.push(folderDetailTmpId);
                            //activeFolderListsNames.push(folderDetailTmpName);
                        } else {
                            //inactiveFolderListsField.push(folderDetailTmpField);
                            inactiveFolderListsIds.push(folderDetailTmpId);
                            //inactiveFolderListsNames.push(folderDetailTmpName);
                        }
        
                    });
        
                    if (activeFolderListsIds.length > 0 || inactiveFolderListsIds.length > 0) {
                        var targetUrl = base_url+"/franchise/config-job/folder-config/update/all";
                        var csrfToken = $('meta[name="csrf-token"]').attr('content');
                        var formData = new FormData();
        
                        formData.append('field', fieldName);
        
                        //formData.append("active_fields", JSON.stringify(activeFolderListsField));
                        formData.append("active_ids", JSON.stringify(activeFolderListsIds));
                        //formData.append("active_names", JSON.stringify(activeFolderListsNames));
        
                        //formData.append("inactive_fields", JSON.stringify(inactiveFolderListsField));
                        formData.append("inactive_ids", JSON.stringify(inactiveFolderListsIds));
                        //formData.append("inactive_names", JSON.stringify(inactiveFolderListsNames));
        
                        $.ajax({
                            type: "POST",
                            url: targetUrl,
                            async: true,
                            data: formData,
                            cache: false,
                            contentType: false,
                            processData: false,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                            },
                            timeout: 60000,
        
                            success: function (response) {
                               //console.log(response);
                                var newData = response;
                                var level;
                                var activeCount;
                                var inactiveCount;
        
                                if (newData) {
                                    level = 'success';
                                    activeCount = newData[0];
                                    inactiveCount = newData[1];
                                } else {
                                    level = 'error';
                                    activeCount = '';
                                    inactiveCount = '';
                                }
        
                                //flashAlert(level, msg);
        
                                var selectorActive = "[data-count=" + selector + "active]";
                                var selectorInactive = "[data-count=" + selector + "inactive]";
        
                                $(selectorActive).removeClass('d-none').html(activeCount);
                                $(selectorInactive).removeClass('d-none').html(inactiveCount);
        
                            },
                            error: function (e) {
                                //alert("An error occurred: " + e.responseText.message);
                               //console.log(e);
                            }
                        })
                    }
                   //console.log(activeFolderListsIds);
                   //console.log(inactiveFolderListsIds);
        
                    //console.log(activefolderListsField, activeFolderListsIds, activeFolderListsNames);
                    //console.log(inactivefolderListsField, inactiveFolderListsIds, inactiveFolderListsNames);
                }
        
            // });
        
            // function toHumanReadable(a) {
            //     return a.length === 1 ? a[0] : [a.slice(0, a.length - 1).join(", "), a[a.length - 1]].join(" and ");
            // }
        

            $('.delete-artifact').on('click', function () {
                var folderKey = $(this).data('folder-key');
                var folderName = $(this).data('folder-name');
                var traditionalGroupPlaceholderSmallUrl = base_url+"/proofing-assets/img/traditionalGroupPlaceholderImage.png";
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
        
                if (folderKey) {
                    var targetUrl = base_url+"/franchise/config-job/delete-file";
                    var formData = new FormData();
                    formData.append("delete", 'true');
                    formData.append("folder_key", folderKey);

                    $.ajax({
                        type: "POST",
                        url: targetUrl,
                        async: true,
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                        },
                        timeout: 60000,
        
                        success: function (response) {
                            // Expecting a success message, not data to manipulate
                            if (response.message === "Image deleted successfully") {
                                $("#" + folderKey + "-image").attr('src', traditionalGroupPlaceholderSmallUrl);
                                $('#' + folderKey + '-delete').removeClass("d-block").addClass('d-none');
                                flashAlert('success', 'Success! Group Image for "' + folderName + '" has been removed.');
                            } else {
                                flashAlert('error', 'Error! Please try again.');
                            }
                        },
                        error: function (e) {
                            flashAlert('error', 'An error occurred while deleting the image.');
                        }
                    });
                }
            });
        
            function flashAlert(level, msg) {
                $("#ajax-response-readable").addClass('alert-' + level).text(msg).toggle(500).delay(4000).toggle(500);
            }
        
            });