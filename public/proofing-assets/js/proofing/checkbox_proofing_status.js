$(document).ready(function() {

    $('#change-folder-status').on('click', function(e) {
        e.preventDefault();

        var JobId = $(this).data('job');
        // Get the selected status
        var selectedStatus = $('#folder_status').val();
        
        // Get the text of the selected option (for the confirmation message)
        var selectedStatusText = $('#folder_status option:selected').text();
    
        if (selectedStatus === '') {
            alert('Please select a status.');
            return;
        }
    
        // Get all checked folder checkboxes
        var checkedFolders = $('.folder-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
    
        if (checkedFolders.length === 0) {
            alert('Please select at least one folder.');
            return;
        }
    
        // Create the confirmation message
        var confirmMsg = `Are you sure you want to change the status of selected folders to ${selectedStatusText}?`;
    
        if (confirm(confirmMsg)) {
            $.ajax({
                url: base_url + '/franchise/folders/update-folder-status',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    folder_ids: checkedFolders,
                    JobId: JobId,
                    new_status: selectedStatus
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                }
            });
        }
    });

    $('.change-single-folder-status').on('click', function(e) {
        e.preventDefault();
        var FolderId = $(this).data('folder');
        var JobId = $(this).data('job');
        var ChangeStatus = $(this).data('value');
        var confirmMsg = $(this).data('confirm-msg');

        if (confirm(confirmMsg)) {
            $.ajax({
                url: base_url + '/franchise/folders/update-folder-status',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    folder_ids: FolderId,
                    JobId: JobId,
                    new_status: ChangeStatus
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                }
            });
        }
    });

    $('.change-job-status').on('click', function(e) {
        e.preventDefault();
        var JobId = $(this).data('job');
        var ChangeStatus = $(this).data('value');
        var confirmMsg = $(this).data('confirm-msg');

        if (confirm(confirmMsg)) {
            $.ajax({
                url: base_url + '/franchise/jobs/update-job-status',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    JobId: JobId,
                    ChangeStatus: ChangeStatus
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                }
            });
        }
    });

    // Handle 'check_all' checkbox functionality
    $('#check_all').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.folder-checkbox').prop('checked', isChecked);
    });

    // Handle individual folder-checkbox change
    $('.folder-checkbox').on('change', function() {
        if ($('.folder-checkbox:checked').length === $('.folder-checkbox').length) {
            $('#check_all').prop('checked', true);
        } else {
            $('#check_all').prop('checked', false);
        }
    });
});
