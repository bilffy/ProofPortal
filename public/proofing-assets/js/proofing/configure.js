
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
            if (response && response.success === false && response.message) {
                showProofingTimelineError(response.message, formData.get('dataType'));
            } else if (formData.get('dataType') && formData.get('date')) {
                markProofingTimelineDateSaved(formData.get('dataType'), formData.get('date'));
            }
        },
        error: function (xhr) {
            var message = xhr?.responseJSON?.message;
            if (message) {
                showProofingTimelineError(message, formData.get('dataType'));
            }
        }
    });
}

function markProofingTimelineDateSaved(dataType, dateValue) {
    var fieldMap = {
        'proof_start': '#review_due_start_picker',
        'proof_warning': '#review_due_warning_picker',
        'proof_due': '#review_due_picker',
        'proof_catchup': '#review_due_catchup_picker',
    };
    var selector = fieldMap[dataType];
    if (!selector) {
        return;
    }
    var el = document.querySelector(selector);
    if (el && dateValue) {
        el.setAttribute('data-last-saved', el.value || dateValue);
        el.setAttribute('data-is-saved', '1');
    }
    if (dataType === 'proof_start') {
        clearProofingStartDateError();
    }
}

function parseProofingTimelineDate(value) {
    if (!value) {
        return null;
    }
    // Supports "d/m/Y h:i K" (picker) and "Y-m-d H:i:s" (API)
    if (value.indexOf('/') !== -1) {
        var parts = value.trim().split(/\s+/);
        if (parts.length < 1) return null;
        var dmy = parts[0].split('/');
        if (dmy.length !== 3) return null;
        var hours = 0;
        var minutes = 0;
        if (parts[1]) {
            var hm = parts[1].split(':');
            hours = parseInt(hm[0], 10) || 0;
            minutes = parseInt(hm[1], 10) || 0;
            var ampm = (parts[2] || '').toUpperCase();
            if (ampm === 'PM' && hours !== 12) hours += 12;
            if (ampm === 'AM' && hours === 12) hours = 0;
        }
        return new Date(parseInt(dmy[2], 10), parseInt(dmy[1], 10) - 1, parseInt(dmy[0], 10), hours, minutes, 0);
    }
    var parsed = new Date(value.replace(' ', 'T'));
    return isNaN(parsed.getTime()) ? null : parsed;
}

function getSavedProofingTimelineDate(selector) {
    var el = document.querySelector(selector);
    if (!el || el.getAttribute('data-is-saved') !== '1') {
        return null;
    }
    return parseProofingTimelineDate(el.getAttribute('data-last-saved') || el.value);
}

function validateProofStartAgainstWarningAndDue(startDateStr) {
    var startDate = parseProofingTimelineDate(startDateStr);
    if (!startDate) {
        return null;
    }
    var warningDate = getSavedProofingTimelineDate('#review_due_warning_picker');
    var dueDate = getSavedProofingTimelineDate('#review_due_picker');
    var warningBefore = warningDate && warningDate <= startDate;
    var dueBefore = dueDate && dueDate <= startDate;
    if (warningBefore || dueBefore) {
        return 'Please reset the Warning Date and the Due Date as it should be after the Start Date';
    }
    return null;
}

function refreshProofStartValidationMessage() {
    var startVal = $('#review_due_start_picker').val();
    if (!startVal) {
        clearProofingStartDateError();
        return;
    }
    var startError = validateProofStartAgainstWarningAndDue(startVal);
    if (startError) {
        showProofingTimelineError(startError, 'proof_start');
    } else {
        clearProofingStartDateError();
    }
}

function adjustReviewDates(dateObject, reviewDataType) {
    if (reviewDataType === 'proof_start') {
        var startError = validateProofStartAgainstWarningAndDue(dateObject);
        if (startError) {
            showProofingTimelineError(startError, reviewDataType);
            return;
        }
        clearProofingStartDateError();
    }

    var targetUrl = base_url + "/franchise/config-job/proofing-timeline/submit";
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var jobHash = document.querySelector('input[name="jobHash"]').value;

    var formData = new FormData();
    formData.append("modifyReviewDate", true);
    formData.append("dataType", reviewDataType);
    formData.append("jobHash", jobHash);
    formData.append("date", dateObject);

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
            if (response && response.success === false && response.message) {
                showProofingTimelineError(response.message, reviewDataType);
                return;
            }
            markProofingTimelineDateSaved(reviewDataType, dateObject);
            if (reviewDataType === 'proof_start') {
                clearProofingStartDateError();
            }
            // After Warning/Due are reset, clear Start Date error if timeline is valid again.
            if (reviewDataType === 'proof_warning' || reviewDataType === 'proof_due') {
                refreshProofStartValidationMessage();
            }
        },
        error: function (xhr) {
            var message = xhr?.responseJSON?.message
                || 'Unable to save the proofing timeline date. Please try again.';
            showProofingTimelineError(message, reviewDataType);
        }
    })
}

// When Start Date validation fails, onClose can run after a revert and wrongly clear the error.
var proofStartValidationBlocked = false;

function clearProofingStartDateError() {
    proofStartValidationBlocked = false;
    var $error = $('#review_due_start_error');
    if ($error.length) {
        $error.hide().text('');
    }
    $('#review_due_start_picker').removeClass('is-invalid border-danger');
}

function revertProofingStartDatePicker() {
    var startInput = document.querySelector('#review_due_start_picker');
    if (!startInput) {
        return;
    }
    var startPicker = startInput._flatpickr;
    var previous = startInput.getAttribute('data-last-saved');
    var wasSaved = startInput.getAttribute('data-is-saved') === '1';

    if (startPicker) {
        // Close calendar so it does not cover the inline error message.
        startPicker.close();
    }

    var applyRevert = function () {
        if (!startInput) {
            return;
        }
        startPicker = startInput._flatpickr;
        if (wasSaved && previous) {
            var prevDate = parseProofingTimelineDate(previous);
            if (startPicker && prevDate && !isNaN(prevDate.getTime())) {
                // Use a Date object — string setDate often fails (Carbon g:i A vs flatpickr h:i K).
                startPicker.setDate(prevDate, false);
            } else if (startPicker) {
                startPicker.setDate(previous, false);
            }
            // Force visible value to last saved if flatpickr left the rejected selection.
            if (startPicker && startPicker.selectedDates[0]) {
                startInput.value = startPicker.formatDate(
                    startPicker.selectedDates[0],
                    startPicker.config.dateFormat
                );
            } else {
                startInput.value = previous;
            }
        } else if (startPicker) {
            startPicker.clear(false);
            startInput.value = '';
        } else {
            startInput.value = '';
        }
        if (startPicker) {
            startPicker.close();
        }
    };

    // Flatpickr re-applies the newly selected date after onChange/onClose; revert on next tick.
    applyRevert();
    setTimeout(applyRevert, 0);
}

function showProofingTimelineError(message, reviewDataType) {
    // Start Date validation: inline red message under the field (no alert/toast).
    // Revert the picker to the last saved value so users don't think the new date was saved.
    if (reviewDataType === 'proof_start') {
        proofStartValidationBlocked = true;
        var $error = $('#review_due_start_error');
        if ($error.length) {
            $error.text(message).css({
                display: 'block',
                color: '#dc3545',
                marginTop: '8px',
                marginBottom: '4px'
            });
        }
        $('#review_due_start_picker').addClass('is-invalid border-danger');
        revertProofingStartDatePicker();
        return;
    }

    window.dispatchEvent(new CustomEvent('show-toast-message', {
        detail: { status: 'error', message: message }
    }));
}

/*
Main AJAX call to send the Notification parameters
*/
function adjustReviewDateIsEnabled(data) {
    var targetUrl = base_url + "/franchise/config-job/email-notifications/enable";
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var jobHash = document.querySelector('input[name="jobHash"]').value;

    var formData = new FormData();
    formData.append("isReviewDateEnabled", data.is(':checked') ? 'true' : 'false');
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

function insertEmailNotification(modelTag, fieldTag, roleTag) {
    var targetUrl = base_url + "/franchise/config-job/email-notifications/submit";
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var jobHash = document.querySelector('input[name="jobHash"]').value;

    var formData = new FormData($('#notification_email_form')[0]);
    formData.append("jobHash", jobHash); // Append the jobHash to the form data
    formData.append("fieldTag", fieldTag);

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
    var deleteUrl = base_url + "/franchise/delete-job/" + jobHash;
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

    (function initLazyGroupImages() {
        var maxConcurrent = 5;
        var inFlight = 0;
        var queue = [];
        var placeholder = window.groupImagePlaceholder || '';

        function loadNext() {
            while (inFlight < maxConcurrent && queue.length > 0) {
                var img = queue.shift();
                var url = img.getAttribute('data-src');
                if (!url) {
                    continue;
                }

                inFlight++;
                var loader = new Image();
                loader.onload = loader.onerror = function () {
                    if (loader.src) {
                        img.src = loader.src;
                    }
                    img.dataset.lazyLoaded = '1';
                    inFlight--;
                    loadNext();
                };
                loader.src = url;
            }
        }

        function enqueue(img) {
            if (!img || img.dataset.lazyLoaded === '1' || !img.getAttribute('data-src')) {
                return;
            }
            if (img.dataset.lazyLoaded === 'queued') {
                return;
            }
            img.dataset.lazyLoaded = 'queued';
            queue.push(img);
            loadNext();
        }

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        enqueue(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { root: null, rootMargin: '250px 0px' });

            document.querySelectorAll('img.lazy-group-image[data-src]').forEach(function (img) {
                observer.observe(img);
            });
        } else {
            document.querySelectorAll('img.lazy-group-image[data-src]').forEach(enqueue);
        }

        window.queueLazyGroupImage = function (img) {
            if (!img) {
                return;
            }
            if (placeholder) {
                img.src = placeholder;
            }
            delete img.dataset.lazyLoaded;
            enqueue(img);
        };
    })();

    (function warmGroupThumbsInBackground() {
        var pending = (window.groupImageFolderKeys || []).slice();
        var warmUrl = window.groupImageWarmUrl;
        if (!pending.length || !warmUrl) {
            return;
        }

        var batchSize = 4;
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        function refreshVisibleThumbs(folderKeys) {
            (folderKeys || []).forEach(function (folderKey) {
                var img = document.getElementById(folderKey + '-image');
                if (img && img.getAttribute('data-src') && typeof window.queueLazyGroupImage === 'function') {
                    window.queueLazyGroupImage(img);
                }
            });
        }

        function processBatch() {
            if (!pending.length) {
                return;
            }

            var batch = pending.splice(0, batchSize);
            $.ajax({
                url: warmUrl,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ folder_keys: batch }),
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (data) {
                    var warmed = (data && data.warmed) ? data.warmed : [];
                    var skipped = (data && data.skipped) ? data.skipped : [];
                    refreshVisibleThumbs(warmed.concat(skipped));
                },
                complete: function () {
                    if (pending.length) {
                        setTimeout(processBatch, 150);
                    }
                }
            });
        }

        var start = function () {
            setTimeout(processBatch, 1500);
        };

        if ('requestIdleCallback' in window) {
            requestIdleCallback(start, { timeout: 5000 });
        } else {
            start();
        }
    })();

    var Upload = function (file, folder_key, folder_name) {
        this.file = file;
        this.folder_key = folder_key;
        this.folder_name = folder_name;
        this.progress_bar_id = "#progress-wrp-" + folder_key;
        this.maxBytes = 15 * 1024 * 1024; // 15MB
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

    // Re-encode camera JPEGs so Cloudflare WAF is less likely to 403 the multipart body
    Upload.prototype.normalizeFile = function (file) {
        return new Promise(function (resolve, reject) {
            var objectUrl = URL.createObjectURL(file);
            var image = new Image();

            image.onload = function () {
                URL.revokeObjectURL(objectUrl);

                var width = image.naturalWidth;
                var height = image.naturalHeight;
                if (!width || !height) {
                    reject(new Error('Invalid image dimensions.'));
                    return;
                }

                var maxSide = 4500;
                var scale = Math.min(1, maxSide / Math.max(width, height));
                width = Math.max(1, Math.round(width * scale));
                height = Math.max(1, Math.round(height * scale));

                var canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                var context = canvas.getContext('2d');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, width, height);
                context.drawImage(image, 0, 0, width, height);

                canvas.toBlob(function (blob) {
                    if (!blob) {
                        reject(new Error('Failed to prepare the image for upload.'));
                        return;
                    }
                    var baseName = (file.name || 'image').replace(/\.[^.]+$/, '');
                    resolve(new File([blob], baseName + '.jpg', {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    }));
                }, 'image/jpeg', 0.92);
            };

            image.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                reject(new Error('Failed to read the selected image.'));
            };

            image.src = objectUrl;
        });
    };

    Upload.prototype.doUpload = function () {
        var that = this;

        if (that.file && that.file.size > that.maxBytes) {
            that.showError('Each image must be 15MB or smaller.');
            return;
        }

        that.normalizeFile(that.file).then(function (normalizedFile) {
            that.file = normalizedFile;
            that.sendUpload();
        }).catch(function (error) {
            that.showError((error && error.message) ? error.message : 'Failed to prepare the image for upload.');
        });
    };

    Upload.prototype.sendUpload = function () {
        var that = this;
        var targetUrl = (typeof window.groupImageUploadUrl === 'string' && window.groupImageUploadUrl)
            ? window.groupImageUploadUrl
            : (base_url + "/franchise/config-job/upload-file");
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        var reader = new FileReader();
        reader.onload = function () {
            var dataUrl = reader.result || '';
            var base64 = typeof dataUrl === 'string' && dataUrl.indexOf(',') !== -1
                ? dataUrl.split(',')[1]
                : dataUrl;

            $.ajax({
                type: "POST",
                url: targetUrl,
                async: true,
                data: JSON.stringify({
                    file_base64: base64,
                    filename: that.getName(),
                    upload_file: true,
                    folder_key: that.folder_key,
                    folder_name: that.folder_name,
                    _token: csrfToken
                }),
                contentType: 'application/json',
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                timeout: 120000,
                xhrFields: {
                    withCredentials: true
                },

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
        reader.onerror = function () {
            that.showError('Failed to prepare the image for upload.');
        };
        reader.readAsDataURL(that.file);
    };

    Upload.prototype.progressHandling = function (evt) {
        //console.log(this.progress_bar_id + " div.progress-bar");
        if (evt.lengthComputable) {
            var percentComplete = evt.loaded / evt.total;
            var percentCompleteAsWhole = Math.ceil(percentComplete * 100);
            //console.log(percentCompleteAsWhole);
            $(this.progress_bar_id + " div.progress-bar").css({ width: percentCompleteAsWhole + '%' }).attr('aria-valuenow', percentCompleteAsWhole);
            $(this.progress_bar_id + " div.progress-bar").text(percentCompleteAsWhole + '%');
            if (percentComplete === 1) {
                $(this.progress_bar_id + " div.progress-bar").text('Upload Complete!');
            }
        }
    };

    Upload.prototype.successHandling = function (data) {
        if (data.error) {
            $("#" + this.folder_key + "-bar").addClass('d-none');
            var errMsg = (data.error.data && data.error.data.message)
                ? data.error.data.message
                : (data.message || 'Upload failed.');
            $("#" + this.folder_key + "-error").removeClass('d-none').text(errMsg);
        }

        if (data.full_url || data.thumb_url) {
            $("#" + this.folder_key + "-bar").addClass('d-none');
            var $image = $("#" + this.folder_key + "-image");
            if (data.thumb_url) {
                $image.attr('data-src', data.thumb_url);
            }
            if (data.full_url) {
                $image.attr('data-modal-src', data.full_url);
            }
            if (typeof window.queueLazyGroupImage === 'function') {
                window.queueLazyGroupImage($image.get(0));
            } else if (data.thumb_url || data.full_url) {
                $image.attr('src', data.thumb_url || data.full_url);
            }
            $("#" + this.folder_key + "-delete").removeClass("d-none").addClass('d-block');
            $("#" + this.folder_key + "-error").addClass('d-none').text('');
        }
    };

    Upload.prototype.showError = function (message) {
        $("#" + this.folder_key + "-bar").addClass('d-none');
        $("#" + this.folder_key + "-error").removeClass('d-none').text(message);
    };

    Upload.prototype.errorHandling = function (xhr) {
        var message = 'Upload failed.';
        var response = xhr.responseJSON;
        var status = xhr.status;
        var fileTooLarge = this.file && this.file.size > this.maxBytes;

        // Web server / proxy rejections often have no JSON body (e.g. nginx 413),
        // and browsers may report that as status 0.
        if (status === 413 || fileTooLarge) {
            message = 'Each image must be 15MB or smaller.';
        } else if (status === 0) {
            message = 'Each image must be 15MB or smaller. If the file is under 15MB, refresh and try again.';
        } else if (status === 401 || status === 419) {
            message = 'Your session has expired. Please refresh the page and try again.';
        } else if (status === 403) {
            var cfMitigated = xhr.getResponseHeader && xhr.getResponseHeader('cf-mitigated');
            var body = xhr.responseText || '';
            var isCloudflare = !!cfMitigated
                || body.indexOf('challenges.cloudflare.com') !== -1
                || body.indexOf('cf-browser-verification') !== -1;

            if (response && response.message && !isCloudflare) {
                message = response.message;
            } else if (isCloudflare) {
                message = 'Upload blocked by Cloudflare security for this file. Try again, or export/re-save the image as a standard JPG.';
            } else {
                message = 'Upload blocked (HTTP 403). Refresh the page and try again.';
            }
        } else if (status === 404) {
            message = 'Upload endpoint not found. Please refresh and try again.';
        } else if (status === 422 && response) {
            if (response.message) {
                message = response.message;
            } else if (response.errors) {
                var firstKey = Object.keys(response.errors)[0];
                if (firstKey && response.errors[firstKey] && response.errors[firstKey][0]) {
                    message = response.errors[firstKey][0];
                }
            }
        } else if (response && response.message) {
            message = response.message;
        } else if (response && response.errors) {
            var errKey = Object.keys(response.errors)[0];
            if (errKey && response.errors[errKey] && response.errors[errKey][0]) {
                message = response.errors[errKey][0];
            }
        } else if (xhr.responseText) {
            try {
                var parsed = JSON.parse(xhr.responseText);
                if (parsed.message) {
                    message = parsed.message;
                }
            } catch (e) {
                if (status >= 500) {
                    message = 'Server error while uploading. Please try again.';
                } else if (status > 0) {
                    message = 'Upload failed (HTTP ' + status + ').';
                }
            }
        } else if (status > 0) {
            message = 'Upload failed (HTTP ' + status + ').';
        }

        this.showError(message);
    };


    $("input.traditional-photo-upload").on("change", function (e) {
        var file = $(this)[0].files["0"];
        var folder_key = $(this).attr('id');
        var folder_name = $(this).attr('name');
        if (!file) {
            return;
        }
        startGroupImageUpload(file, folder_key, folder_name);
    });

    function startGroupImageUpload(file, folder_key, folder_name) {
        if (!file) {
            return;
        }

        var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (file.type && allowedTypes.indexOf(file.type) === -1) {
            $("#" + folder_key + "-error").removeClass('d-none').text('Please drop a JPG or PNG image.');
            return;
        }

        var upload = new Upload(file, folder_key, folder_name);

        $("#" + folder_key + "-bar").removeClass('d-none');
        $(upload.progress_bar_id + " div.progress-bar").css({ width: 0 + '%' }).attr('aria-valuenow', 0);
        $(upload.progress_bar_id + " div.progress-bar").text("0%");
        $("#" + folder_key + "-error").addClass('d-none');

        upload.doUpload();
    }

    $(document).on('dragenter dragover', '.group-image-dropzone', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if ($(this).closest('.traditional-photo-upload').css('pointer-events') === 'none') {
            return;
        }
        $(this).addClass('is-dragover');
    });

    $(document).on('dragleave drop', '.group-image-dropzone', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('is-dragover');
    });

    $(document).on('drop', '.group-image-dropzone', function (e) {
        if ($(this).closest('.traditional-photo-upload').css('pointer-events') === 'none') {
            return;
        }

        var dt = e.originalEvent && e.originalEvent.dataTransfer;
        var files = dt && dt.files ? dt.files : null;
        if (!files || !files.length) {
            return;
        }

        var folder_key = $(this).data('folder-key');
        var folder_name = $(this).data('folder-name');
        startGroupImageUpload(files[0], folder_key, folder_name);
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
        processCheckboxes('is-visible-for-proofing-', 'is_visible_for_proofing');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-visible-for-proofing-none").click(function () {
        $("input[id^='is-visible-for-proofing-']").prop('checked', false);
        processCheckboxes('is-visible-for-proofing-', 'is_visible_for_proofing');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-visible-for-proofing-']").change(function () {
        processCheckboxes('is-visible-for-proofing-', 'is_visible_for_proofing');
        applyEditCapabilities();
    });
    //==============================================================


    //Is Edit Portraits
    $("#set-is-edit-portraits-all").click(function () {
        $("input[id^='is-edit-portraits-']").prop('checked', true);
        processCheckboxes('is-edit-portraits-', 'is_edit_portraits');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-portraits-none").click(function () {
        $("input[id^='is-edit-portraits-']").prop('checked', false);
        processCheckboxes('is-edit-portraits-', 'is_edit_portraits');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-portraits-']").change(function () {
        processCheckboxes('is-edit-portraits-', 'is_edit_portraits');
        applyEditCapabilities();
    });
    //==============================================================

    //Is Edit Group
    $("#set-is-edit-group-all").click(function () {
        $("input[id^='is-edit-group-']").prop('checked', true);
        processCheckboxes('is-edit-group-', 'is_edit_groups');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-group-none").click(function () {
        $("input[id^='is-edit-group-']").prop('checked', false);
        processCheckboxes('is-edit-group-', 'is_edit_groups');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-group-']").change(function () {
        processCheckboxes('is-edit-group-', 'is_edit_groups');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Subject List Allowed
    $("#is-subject-list-allowed-all").click(function () {
        $("input[id^='is-subject-list-allowed-']").prop('checked', true);
        processCheckboxes('is-subject-list-allowed-', 'is_subject_list_allowed');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#is-subject-list-allowed-none").click(function () {
        $("input[id^='is-subject-list-allowed-']").prop('checked', false);
        processCheckboxes('is-subject-list-allowed-', 'is_subject_list_allowed');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-subject-list-allowed-']").change(function () {
        processCheckboxes('is-subject-list-allowed-', 'is_subject_list_allowed');
        applyEditCapabilities();
    });
    //==============================================================


    //Is Edit Principal
    $("#set-is-edit-principal-all").click(function () {
        $("input[id^='is-edit-principal-']").prop('checked', true);
        processCheckboxes('is-edit-principal-', 'is_edit_principal');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-principal-none").click(function () {
        $("input[id^='is-edit-principal-']").prop('checked', false);
        processCheckboxes('is-edit-principal-', 'is_edit_principal');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-principal-']").change(function () {
        processCheckboxes('is-edit-principal-', 'is_edit_principal');
        applyEditCapabilities();
    });
    //==============================================================


    //Is Edit Deputy
    $("#set-is-edit-deputy-all").click(function () {
        $("input[id^='is-edit-deputy-']").prop('checked', true);
        processCheckboxes('is-edit-deputy-', 'is_edit_deputy');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-deputy-none").click(function () {
        $("input[id^='is-edit-deputy-']").prop('checked', false);
        processCheckboxes('is-edit-deputy-', 'is_edit_deputy');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-deputy-']").change(function () {
        processCheckboxes('is-edit-deputy-', 'is_edit_deputy');
        applyEditCapabilities();
    });
    //==============================================================


    //Is Edit Teacher
    $("#set-is-edit-teacher-all").click(function () {
        $("input[id^='is-edit-teacher-']").prop('checked', true);
        processCheckboxes('is-edit-teacher-', 'is_edit_teacher');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-teacher-none").click(function () {
        $("input[id^='is-edit-teacher-']").prop('checked', false);
        processCheckboxes('is-edit-teacher-', 'is_edit_teacher');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-teacher-']").change(function () {
        processCheckboxes('is-edit-teacher-', 'is_edit_teacher');
        applyEditCapabilities();
    });
    //==============================================================


    // //Is Edit Salutation
    // $("#set-is-edit-salutation-all").click(function () {
    //     $("input[id^='is-edit-salutation-']").prop('checked', true);
    //     processCheckboxes('is-edit-salutation-','is_edit_salutation');
    //     applyEditCapabilities();
    // }).css('cursor', 'pointer');

    // $("#set-is-edit-salutation-none").click(function () {
    //     $("input[id^='is-edit-salutation-']").prop('checked', false);
    //     processCheckboxes('is-edit-salutation-','is_edit_salutation');
    //     applyEditCapabilities();
    // }).css('cursor', 'pointer');

    // $("input[id^='is-edit-salutation-']").change(function () {
    //     processCheckboxes('is-edit-salutation-','is_edit_salutation');
    //     applyEditCapabilities();
    // });
    //==============================================================


    //Is Edit Job Title
    $("#set-is-edit-job-title-all").click(function () {
        $("input[id^='is-edit-job-title-']").prop('checked', true);
        processCheckboxes('is-edit-job-title-', 'is_edit_job_title');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-job-title-none").click(function () {
        $("input[id^='is-edit-job-title-']").prop('checked', false);
        processCheckboxes('is-edit-job-title-', 'is_edit_job_title');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-job-title-']").change(function () {
        processCheckboxes('is-edit-job-title-', 'is_edit_job_title');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Show Salutation in Portrait
    $("#set-is-edit-job-show-salutation-portrait-all").click(function () {
        $("input[id^='is-edit-job-show-salutation-portrait-']").prop('checked', true);
        processCheckboxes('is-edit-job-show-salutation-portrait-', 'show_salutation_portraits');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-job-show-salutation-portrait-none").click(function () {
        $("input[id^='is-edit-job-show-salutation-portrait-']").prop('checked', false);
        processCheckboxes('is-edit-job-show-salutation-portrait-', 'show_salutation_portraits');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-job-show-salutation-portrait-']").change(function () {
        processCheckboxes('is-edit-job-show-salutation-portrait-', 'show_salutation_portraits');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Show Salutation in Group
    $("#set-is-edit-job-show-salutation-group-all").click(function () {
        $("input[id^='is-edit-job-show-salutation-group-']").prop('checked', true);
        processCheckboxes('is-edit-job-show-salutation-group-', 'show_salutation_groups');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-job-show-salutation-group-none").click(function () {
        $("input[id^='is-edit-job-show-salutation-group-']").prop('checked', false);
        processCheckboxes('is-edit-job-show-salutation-group-', 'show_salutation_groups');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-job-show-salutation-group-']").change(function () {
        processCheckboxes('is-edit-job-show-salutation-group-', 'show_salutation_groups');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Show Prefix Suffix in Portrait
    $("#set-is-edit-job-prefix-suffix-portrait-all").click(function () {
        $("input[id^='is-edit-job-prefix-suffix-portrait-']").prop('checked', true);
        processCheckboxes('is-edit-job-prefix-suffix-portrait-', 'show_prefix_suffix_portraits');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-job-prefix-suffix-portrait-none").click(function () {
        $("input[id^='is-edit-job-prefix-suffix-portrait-']").prop('checked', false);
        processCheckboxes('is-edit-job-prefix-suffix-portrait-', 'show_prefix_suffix_portraits');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-job-prefix-suffix-portrait-']").change(function () {
        processCheckboxes('is-edit-job-prefix-suffix-portrait-', 'show_prefix_suffix_portraits');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Show Prefix Suffix in Group
    $("#set-is-edit-job-prefix-suffix-group-all").click(function () {
        $("input[id^='is-edit-job-prefix-suffix-group-']").prop('checked', true);
        processCheckboxes('is-edit-job-prefix-suffix-group-', 'show_prefix_suffix_groups');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-edit-job-prefix-suffix-group-none").click(function () {
        $("input[id^='is-edit-job-prefix-suffix-group-']").prop('checked', false);
        processCheckboxes('is-edit-job-prefix-suffix-group-', 'show_prefix_suffix_groups');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-edit-job-prefix-suffix-group-']").change(function () {
        processCheckboxes('is-edit-job-prefix-suffix-group-', 'show_prefix_suffix_groups');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Visible Portrait
    $("#set-is-visible-for-portrait-all").click(function () {
        console.log('hi');
        $("input[id^='is-visible-for-portrait-']").prop('checked', true);
        processCheckboxes('is-visible-for-portrait-', 'is_visible_for_portrait');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-visible-for-portrait-none").click(function () {
        $("input[id^='is-visible-for-portrait-']").prop('checked', false);
        processCheckboxes('is-visible-for-portrait-', 'is_visible_for_portrait');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-visible-for-portrait-']").change(function () {
        processCheckboxes('is-visible-for-portrait-', 'is_visible_for_portrait');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Visible Group
    $("#set-is-visible-for-group-all").click(function () {
        $("input[id^='is-visible-for-group-']").prop('checked', true);
        processCheckboxes('is-visible-for-group-', 'is_visible_for_group');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-visible-for-group-none").click(function () {
        $("input[id^='is-visible-for-group-']").prop('checked', false);
        processCheckboxes('is-visible-for-group-', 'is_visible_for_group');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-visible-for-group-']").change(function () {
        processCheckboxes('is-visible-for-group-', 'is_visible_for_group');
        applyEditCapabilities();
    });

    //==============================================================


    //Is Visible School Photo
    $("#set-is-visible-for-school-all").click(function () {
        $("input[id^='is-visible-for-school-']").prop('checked', true);
        processCheckboxes('is-visible-for-school-', 'pre_catchup_visible_portrait');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("#set-is-visible-for-school-none").click(function () {
        $("input[id^='is-visible-for-school-']").prop('checked', false);
        processCheckboxes('is-visible-for-school-', 'pre_catchup_visible_portrait');
        applyEditCapabilities();
    }).css('cursor', 'pointer');

    $("input[id^='is-visible-for-school-']").change(function () {
        processCheckboxes('is-visible-for-school-', 'pre_catchup_visible_portrait');
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
        var isEditSalutationCheckbox;
        var isEditJobTitleCheckbox;
        var showSalutationPortraitCheckbox;
        var showSalutationGroupCheckbox;
        var showPrefixSuffixPortraitCheckbox;
        var showPrefixSuffixGroupCheckbox;

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
            // isEditSalutationCheckbox = $("#is-edit-salutation-" + folderKey);
            isEditJobTitleCheckbox = $("#is-edit-job-title-" + folderKey);
            showSalutationPortraitCheckbox = $("#is-edit-job-show-salutation-portrait-" + folderKey);
            showSalutationGroupCheckbox = $("#is-edit-job-show-salutation-group-" + folderKey);
            showPrefixSuffixPortraitCheckbox = $("#is-edit-job-prefix-suffix-portrait-" + folderKey);
            showPrefixSuffixGroupCheckbox = $("#is-edit-job-prefix-suffix-group-" + folderKey);

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
                // $(".is-edit-salutation--" + folderKey).css('pointer-events', '').css('opacity', 1);
                $(".is-edit-job-title--" + folderKey).css('pointer-events', '').css('opacity', 1);
                $(".is-edit-job-show-salutation-portrait--" + folderKey).css('pointer-events', '').css('opacity', 1);
                $(".is-edit-job-prefix-suffix-portrait--" + folderKey).css('pointer-events', '').css('opacity', 1);
            } else {
                $(".is-edit-portraits--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-group--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-subject-list-allowed--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".traditional-photo-upload--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-principal--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-deputy--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-teacher--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                // $(".is-edit-salutation--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-job-title--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-job-show-salutation-portrait--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-job-prefix-suffix-portrait--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
            }


            //isEditPortraits Checkbox
            var isEditPortraitsCheckboxTicked = isEditPortraitsCheckbox.prop('checked');
            if (isEditPortraitsCheckboxTicked && isVisibleForProofingCheckboxTicked) {
                $(".is-edit-job-show-salutation-portrait--" + folderKey).css('pointer-events', '').css('opacity', 1);
                $(".is-edit-job-prefix-suffix-portrait--" + folderKey).css('pointer-events', '').css('opacity', 1);
                // $(".is-edit-salutation--" + folderKey).css('pointer-events', '').css('opacity', 1);
                $(".is-edit-job-title--" + folderKey).css('pointer-events', '').css('opacity', 1);
            } else {
                $(".is-edit-job-show-salutation-portrait--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-job-prefix-suffix-portrait--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                // $(".is-edit-salutation--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
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
                $(".is-edit-job-show-salutation-group--" + folderKey).css('pointer-events', '').css('opacity', 1);
                $(".is-edit-job-prefix-suffix-group--" + folderKey).css('pointer-events', '').css('opacity', 1);
            } else {
                $(".is-subject-list-allowed--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".traditional-photo-upload--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-principal--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-deputy--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-teacher--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-job-show-salutation-group--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
                $(".is-edit-job-prefix-suffix-group--" + folderKey).css('pointer-events', 'none').css('opacity', 0);
            }

        }
    }


    function processCheckboxes(selector, fieldName) {
        if (typeof window.checkboxTimers === 'undefined') window.checkboxTimers = {};
        if (typeof window.checkboxAjax === 'undefined') window.checkboxAjax = {};

        if (window.checkboxTimers[selector]) {
            clearTimeout(window.checkboxTimers[selector]);
        }

        window.checkboxTimers[selector] = setTimeout(function () {
            var folderDetailTmpId;
            var folderDetailTmpIsChecked;
            var activeFolderListsIds = [];
            var inactiveFolderListsIds = [];

            $("input[id^='" + selector + "']").each(function () {
                folderDetailTmpId = $(this).data('folder-id');
                folderDetailTmpIsChecked = $(this).is(':checked');

                if (folderDetailTmpIsChecked) {
                    activeFolderListsIds.push(folderDetailTmpId);
                } else {
                    inactiveFolderListsIds.push(folderDetailTmpId);
                }
            });

            if (activeFolderListsIds.length > 0 || inactiveFolderListsIds.length > 0) {
                var targetUrl = base_url + "/franchise/config-job/folder-config/update/all";
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                var formData = new FormData();

                formData.append('field', fieldName);
                formData.append("active_ids", JSON.stringify(activeFolderListsIds));
                formData.append("inactive_ids", JSON.stringify(inactiveFolderListsIds));

                // Abort any ongoing request for this specific selector to prevent race conditions
                if (window.checkboxAjax[selector]) {
                    window.checkboxAjax[selector].abort();
                }

                window.checkboxAjax[selector] = $.ajax({
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
                        var newData = response;
                        var activeCount = newData ? newData[0] : '';
                        var inactiveCount = newData ? newData[1] : '';

                        var selectorActive = "[data-count=" + selector + "active]";
                        var selectorInactive = "[data-count=" + selector + "inactive]";

                        $(selectorActive).removeClass('d-none').html(activeCount);
                        $(selectorInactive).removeClass('d-none').html(inactiveCount);
                    },
                    error: function (e) {
                        if (e.statusText !== 'abort') {
                            // console.log(e);
                        }
                    }
                });
            }
        }, 400); // 400ms debounce
    }

    // });

    // function toHumanReadable(a) {
    //     return a.length === 1 ? a[0] : [a.slice(0, a.length - 1).join(", "), a[a.length - 1]].join(" and ");
    // }


    $('.delete-artifact').on('click', function () {
        var folderKey = $(this).data('folder-key');
        var folderName = $(this).data('folder-name');
        var traditionalGroupPlaceholderSmallUrl = base_url + "/proofing-assets/img/traditionalGroupPlaceholderImage.png";
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (folderKey) {
            var targetUrl = base_url + "/franchise/config-job/delete-file";
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

$(document).ready(function () {
    var $list = $('#tnj-image-count-list');
    if ($list.length === 0) {
        return;
    }

    $.ajax({
        url: $list.data('url'),
        method: 'GET',
        success: function (data) {
            var html;
            if (data.totalTSSubjectImages != data.totalBPSubjectImages) {
                html = '<li><span class="text-danger">' +
                    $list.data('msg-mismatch')
                        .replace('__TNJ__', data.totalTSSubjectImages)
                        .replace('__BP__', data.totalBPSubjectImages) +
                    '</span></li>';
            } else {
                var missing = data.bpSubjectCount > data.totalTSSubjectImages
                    ? $list.data('msg-missing').replace('__COUNT__', data.bpSubjectCount - data.totalTSSubjectImages)
                    : '';
                html = '<li>' + $list.data('msg-match')
                    .replace('__TNJ__', data.totalTSSubjectImages)
                    .replace('__SUB__', data.bpSubjectCount)
                    .replace('__MISSING__', missing) + '</li>';
            }
            $('#tnj-image-count-loading').replaceWith(html);
        },
        error: function () {
            $('#tnj-image-count-loading').text($list.data('msg-error'));
        }
    });
});
