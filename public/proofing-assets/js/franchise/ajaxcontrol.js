$(document).ready(function () {

    var successMessage = sessionStorage.getItem('successMessage');
    if (successMessage) {
        // Display the success message in the alert div
        $('.alert-success').removeClass('d-none').html(successMessage);
        // Clear the message from sessionStorage
        sessionStorage.removeItem('successMessage');
    }

    $('#season-select').select2();
    $('#job-select').select2();

    // $('#job-select').on('change', function () {
    //     var seasonId = $('#season-select').val(); // Get the selected season id
    //     var jobId = $(this).val(); // Get the selected job id

    //     if (seasonId && jobId) {
    //         document.getElementById("seasonId").value = seasonId;
    //         document.getElementById("jobId").value = jobId;
    //         $('#job-select-form').submit();
    //     }
    // }); 

    // $('#season-select').on('change', function () {
    //     var seasonId = $(this).val();
    //     $.ajax({
    //         url: base_url + '/franchise/jobs-by-season/' + seasonId,
    //         type: 'GET',
    //             success: function(data) {
    //                 var jobSelect = $('#job-select');
    //                 jobSelect.empty();
    //                 jobSelect.append('<option value="">Choose one</option>');
    //                 $.each(data, function(key, job) {
    //                     jobSelect.append('<option value="' + job.id + '">' + job.name + '</option>');
    //                 });
    //                 jobSelect.trigger('change');
    //             }
    //     });
    // });

    $(document).on('click', '#open-job-link', function (event) {
        event.preventDefault(); // Prevent the default link behavior

        var jobKey = $(this).data('job');

        if (!jobKey) {
            console.error('jobKey is missing.');
            return;
        }

        $.ajax({
            url: base_url + '/proofing/openJob',
            type: 'GET',
            data: { jobKey: jobKey },
            dataType: 'json',
            beforeSend: function () {
                $('#open-job-link').prop('disabled', true);
            },
            success: function (response) {
                if (response.success) {
                    window.location.href = response.redirectUrl;
                } else {
                    console.error('Error:', response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            },
            complete: function () {
                $('#open-job-link').prop('disabled', false);
            }
        });
    });


    //Restore Job
    $(document).on('click', '#restore-job', function (event) {
        event.preventDefault(); // Prevent the default link behavior
        var job = $(this).data('job');
        var jobName = $(this).data('name');
        var row = $(this).closest('tr');

        if (!job) {
            console.error('Job ID is missing.');
            return;
        }

        if (confirm('Are you sure you want to restore ' + jobName + '?')) {
            $.ajax({
                type: 'POST',
                url: base_url + '/proofing/jobs/restore',
                data: {
                    job,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.redirect) {
                        // Store the success message in sessionStorage
                        sessionStorage.setItem('successMessage', response.message);
                        // Reload the page
                        window.location.href = response.redirect;
                    } else if (response.message) {
                        // Store the success message in sessionStorage
                        sessionStorage.setItem('successMessage', response.message);
                        // Reload the page
                        location.reload();
                    }
                },
                error: function () {

                }
            });
        }
    });

    jQuery.noConflict();
    var searchDataTable;
    if ($('#searchData').length) {
        searchDataTable = $('#searchData').DataTable({
            // "dom": 'Blfrtip',
            "paging": true,
            "pageLength": 10,
            "lengthMenu": [
                [10, 20, 50, 100, 200, 500, -1],
                [10, 20, 50, 100, 200, 500, 'All']
            ],
            "language": {
                "lengthMenu": "Display _MENU_",
                "emptyTable": "No Jobs Found"
            },
            "lengthChange": true,
            "searching": true, // Enable searching
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });
        $('#searchData').removeClass('hide-until-ready');
        searchDataTable.columns.adjust().draw(false);
    }

    // Update search on input change
    $('#searchData_filter').on('keyup', function () {
        if(searchDataTable) searchDataTable.search(this.value).draw();
    });

    var schoolsTable;
    if ($('#schools-table').length) {
        schoolsTable = $('#schools-table').DataTable({
            // "dom": 'Blfrtip',
            "paging": true,
            "pageLength": 5,
            "lengthMenu": [
                [5, 10, 20, 50, 100, 200, 500, -1],
                [5, 10, 20, 50, 100, 200, 500, 'All']
            ],
            "language": {
                "lengthMenu": "Display _MENU_",
                "emptyTable": "No Jobs Found"
            },
            "lengthChange": true,
            "searching": true, // Enable searching
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });
        $('#schools-table').removeClass('hide-until-ready');
        schoolsTable.columns.adjust().draw(false);
    }

    // Update search on input change
    $('#schools-table_filter').on('keyup', function () {
        if(schoolsTable) schoolsTable.search(this.value).draw();
    });

    // Hide and Show Archived Jobs
    var isArchivedHidden = true;

    $('.show-hide-archived').on('click', function () {
        isArchivedHidden = !isArchivedHidden;

        $(this).text(
            isArchivedHidden ? 'Show Archived Jobs' : 'Hide Archived Jobs'
        );

        const url = $(this).data('toggle-url');

        // Clear the table before fetching new data
        searchDataTable.clear().draw();

        // Fetch jobs (either archived or active/none based on toggle)
        $.ajax({
            url: url,
            data: {
                includeArchived: !isArchivedHidden
            },
            success: function (response) {
                response.data.forEach(function (job) {
                    const folderCounts = job.folderCounts || {};
                    const formattedCounts = Object.entries(folderCounts)
                        .map(([statusName, count]) => `${statusName}: ${count}`)
                        .join('<br>');

                    let actionHtml = '';
                    if (isArchivedHidden) {
                        // Action for Active/None jobs
                        actionHtml = `
                            <a href="#" id="open-job-link" data-job="${job.jobKeyHash}">Open Job</a> |
                            <a href="${job.config_url}">Configure</a> |
                            <a href="#" class="archive-job" data-job="${job.hash}" data-name="${job.ts_jobname}">Archive</a>
                        `;
                    } else {
                        // Action for Archived jobs
                        actionHtml = `
                            <a href="#" 
                                data-name="${job.ts_jobname}" 
                                id="restore-job" 
                                data-job="${job.hash}">
                                Restore
                            </a>
                        `;
                    }

                    const newRow = searchDataTable.row.add([
                        '',
                        job.ts_jobkey,
                        job.ts_jobname,
                        job.season_code,
                        job.review_statuses
                            ? job.review_statuses.status_external_name
                            : '',
                        formattedCounts,
                        job.proof_start ? moment(job.proof_start).format('YYYY-MM-DD') : '',
                        job.proof_warning ? moment(job.proof_warning).format('YYYY-MM-DD') : '',
                        job.proof_due ? moment(job.proof_due).format('YYYY-MM-DD') : '',
                        actionHtml
                    ]).node();

                    if (!isArchivedHidden) {
                        $(newRow).addClass('archived');
                    }

                    // Date-based coloring
                    const now = moment();
                    if (job.proof_start && moment(job.proof_start).isSameOrBefore(now)) {
                        $(newRow).find('td:eq(6)').addClass('text-success alert-link');
                    }
                    if (job.proof_warning && moment(job.proof_warning).isSameOrBefore(now)) {
                        $(newRow).find('td:eq(7)').addClass('text-warning alert-link');
                    }
                    if (job.proof_due && moment(job.proof_due).isSameOrBefore(now)) {
                        $(newRow).find('td:eq(8)').addClass('text-danger alert-link');
                    }
                });

                searchDataTable.draw();
                updateRowNumbers(searchDataTable);
            },
            error: function () {
                console.error('Error fetching job data.');
            }
        });
    });


    //Archive Job
    $(document).on('click', '.archive-job', function (e) {
        e.preventDefault();
        var job = $(this).data('job');
        var row = $(this).closest('tr');
        var jobName = $(this).data('name');
        if (confirm('Are you sure you want to archive ' + jobName + '?')) {
            $.ajax({
                type: 'POST',
                url: base_url + '/proofing/jobs/archive',
                data: {
                    job,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.redirect) {
                        // Store the success message in sessionStorage
                        sessionStorage.setItem('successMessage', response.message);
                        // Reload the page
                        window.location.href = response.redirect;
                    } else if (response.message) {
                        // Store the success message in sessionStorage
                        sessionStorage.setItem('successMessage', response.message);
                        // Reload the page
                        location.reload();
                    }
                },
                error: function () {

                }
            });
        }
    });

    // === Open Job Button ===
    $(document).on('click', '.openJobBtn', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const $row = $btn.closest('tr');
        const jobId = $btn.data('job-id');
        const jobKey = $btn.data('job-key');

        const jobKeyText = $row.find('.idx-job-key').text() || jobKey;
        const jobName = $row.find('.idx-name').text();
        const season = $row.find('.idx-description').text();

        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const currentBaseUrl = typeof base_url !== 'undefined' ? base_url : window.location.origin;

        // prevent double click
        if ($btn.data('loading')) return;
        $btn.data('loading', true);

        // Grey transparent loading screen
        let $overlay = $('#dynamic-loader-overlay');
        if ($overlay.length === 0) {
            $overlay = $('<div id="dynamic-loader-overlay" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999; display: flex; flex-direction: column; align-items: center; justify-content: center;"><i class="fa fa-spinner fa-spin fa-3x fa-fw text-light mb-3"></i><h4 class="text-light">Syncing & Opening Job...</h4></div>');
            $('body').append($overlay);
        }
        $overlay.fadeIn(200);
        $btn.prop('disabled', true);

        $.ajax({
            url: currentBaseUrl + '/proxy-sync-job',
            type: "POST",
            data: { _token: csrfToken, jobKey: jobKey },
            success: function (proxyResponse) {
                if (proxyResponse.success) {

                    // Slowly remove row
                    $row.fadeOut(600, function () {

                        // Reconstruct the 10 columns for DataTables to prevent <td> misalignment
                        let searchDataTableInstance = $('#searchData').DataTable();
                        let addedNode = searchDataTableInstance.row.add([
                            '', // ID
                            jobKeyText,
                            jobName,
                            season,
                            'Syncing...', // Job Proofing Status
                            'Syncing...', // Folder statuses
                            '', // Start
                            '', // Warning
                            '', // Due
                            '<span class="text-primary font-weight-bold"><i class="fa fa-spinner fa-spin"></i> Preparing...</span>'
                        ]).draw(false).node();

                        // Fade in the new Datatables node
                        let $addedRow = $(addedNode);
                        $addedRow.hide();
                        $addedRow.fadeIn(600, function () {

                            // Call OpenJob redirect
                            $.ajax({
                                url: currentBaseUrl + '/proofing/openJob',
                                type: "GET",
                                data: { jobKey: jobKey },
                                success: function (response) {
                                    if (response.success) {
                                        if (response.redirectUrl) {
                                            window.location.href = response.redirectUrl;
                                        } else {
                                            window.location.href = currentBaseUrl + '/proofing';
                                        }
                                    } else {
                                        $overlay.fadeOut(200);
                                        $btn.prop('disabled', false);
                                        $btn.removeData('loading');
                                        alert('Failed to open job: ' + (response.message || 'Unknown error'));
                                    }
                                },
                                error: function (xhr, status, error) {
                                    $overlay.fadeOut(200);
                                    $btn.prop('disabled', false);
                                    $btn.removeData('loading');
                                    alert('Server error while opening job: ' + error);
                                }
                            });
                        });
                    });

                } else {
                    $overlay.fadeOut(200);
                    $btn.prop('disabled', false);
                    $btn.removeData('loading');
                    alert('Sync failed: ' + (proxyResponse.message || 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                $overlay.fadeOut(200);
                $btn.prop('disabled', false);
                $btn.removeData('loading');
                alert('Server error during sync: ' + error);
            }
        });
    });


    //Updating Row Numbers
    function updateRowNumbers(tbl) {
        tbl.rows().nodes().each(function (row, index) {
            $('td:eq(0)', row).html(index + 1);
        });
    }
});
