jQuery(function($){
    // Add Certification
    $('#add-certification-form').on('submit', function(e){
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('action', 'add_user_certification');

        $.ajax({
            url: certifications_ajax.ajax_url,
            method: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success: function(res){
                if(res.success){
                    $('#certifications-message').css('color', 'green').text(res.data.message);
                    $('#certifications-list').html(res.data.table_rows);
                    $('#add-certification-form')[0].reset();
                } else {
                    $('#certifications-message').css('color', 'red').text(res.data.message);
                }
            }
        });
    });

    // Delete Certification
    $(document).on('click', '.delete-cert-btn', function(){
        let cert_id = $(this).data('cert-id');
        if (!confirm('Are you sure you want to delete this certification?')) return;

        $.post(certifications_ajax.ajax_url, {
            action: 'delete_user_certification',
            cert_id: cert_id,
            user_id: $('input[name="user_id"]').val(),
            certifications_nonce: $('input[name="certifications_nonce"]').val()
        }, function(res){
            if(res.success){
                $('#certifications-message').css('color', 'green').text(res.data.message);
                $('#certifications-list').html(res.data.table_rows);
            } else {
                $('#certifications-message').css('color', 'red').text(res.data.message);
            }
        });
    });
});
