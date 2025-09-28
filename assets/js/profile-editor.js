jQuery(document).ready(function($){
    $('#acf-frontend-profile-form').on('submit', function(e){
        e.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: profileEditorAjax.ajax_url,
            data: formData,
            success: function(response){
                var msgBox = $('#acf-frontend-profile-message');
                msgBox.removeClass('success error').show();

                if (response.success) {
                    msgBox.addClass('success').html('<p>'+response.data.message+'</p>');
                } else {
                    msgBox.addClass('error').html('<p>'+response.data.message+'</p>');
                }
            },
            error: function(){
                var msgBox = $('#acf-frontend-profile-message');
                msgBox.removeClass('success').addClass('error').show()
                      .html('<p>Something went wrong.</p>');
            }
        });
    });
});
