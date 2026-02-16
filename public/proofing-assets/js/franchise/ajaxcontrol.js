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
    if ($('#searchData').length) {
        var table = $('#searchData').DataTable({
            // "dom": 'Blfrtip',
            "paging": true,
            "pageLength": 20,
            "lengthMenu": [
                [20, 50, 100, 200, 500, -1],
                [20, 50, 100, 200, 500, 'All']
            ],
            "language": {
                "lengthMenu": "Display _MENU_"
            },
            "lengthChange": true,
            "searching": true, // Enable searching
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });
    }

    // Update search on input change
    $('#searchData_filter').on('keyup', function () {
        table.search(this.value).draw();
    });

    // Hide and Show Archived Jobs
    var isArchivedHidden = true;

    $('.show-hide-archived').on('click', function () {
        isArchivedHidden = !isArchivedHidden;

        $(this).text(
            isArchivedHidden ? 'Show Archived Jobs' : 'Hide Archived Jobs'
        );

        const url = $(this).data('toggle-url');

        // Always remove archived rows first
        table.rows('.archived').remove().draw();

        // If we are hiding archived, stop here
        if (isArchivedHidden) {
            updateRowNumbers(table);
            return;
        }

        // Otherwise fetch archived jobs
        $.ajax({
            url: url,
            data: {
                includeArchived: true
            },
            success: function (response) {

                response.data.forEach(function (job) {

                    const folderCounts = job.folderCounts || {};
                    const formattedCounts = Object.entries(folderCounts)
                        .map(([statusName, count]) => `${statusName}: ${count}`)
                        .join('<br>');

                    const newRow = table.row.add([
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
                        `<a href="#" 
                                data-name="${job.ts_jobname}" 
                                id="restore-job" 
                                data-job="${job.hash}">
                                Restore
                            </a>`
                    ]).node();

                    // Mark row as archived
                    $(newRow).addClass('archived');

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

                table.draw();
                updateRowNumbers(table);
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


    //Updating Row Numbers
    function updateRowNumbers(table) {
        table.rows().nodes().each(function (row, index) {
            $('td:eq(0)', row).html(index + 1);
        });
    }
});
