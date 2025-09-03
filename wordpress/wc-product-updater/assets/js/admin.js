jQuery(document).ready(function($) {
    $('#manual-sync').on('click', function() {
        var button = $(this);
        var status = $('#sync-status');
        
        button.prop('disabled', true);
        status.html('<span class="spinner is-active" style="float:none;margin:0 5px"></span> Syncing...');
        
        $.ajax({
            url: wc_product_updater.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_product_updater_manual_sync',
                nonce: wc_product_updater.nonce
            },
            success: function(response) {
                if (response.success) {
                    status.html('<span style="color:green">✓ Sync completed: ' + 
                                response.data.updated + ' products updated</span>');
                    $('#last-sync-time').text(new Date().toLocaleString());
                } else {
                    status.html('<span style="color:red">✗ Sync failed</span>');
                }
            },
            error: function() {
                status.html('<span style="color:red">✗ Sync error</span>');
            },
            complete: function() {
                button.prop('disabled', false);
                
                // Clear status after 5 seconds
                setTimeout(function() {
                    status.html('');
                }, 5000);
            }
        });
    });
});