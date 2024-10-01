jQuery(document).ready(function($) {
    $('#book_isbn').on('change', function() {
        var isbn = $(this).val();
        var postId = $('#post_ID').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_isbn',
                isbn: isbn,
                post_id: postId,
            },
            success: function(response) {
                alert('ISBN saved successfully!');
            },
            error: function() {
                alert('Error saving ISBN.');
            }
        });
    });
});
