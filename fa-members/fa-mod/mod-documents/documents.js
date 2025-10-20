jQuery(document).ready(function($) {
    var frame;

    // Add document files
    $('#fa-add-document-file').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Document Files',
            button: { text: 'Add to Document' },
            library: {
                type: ['application/pdf', 'application/msword', 'text/plain']
            },
            multiple: true
        });

        frame.on('select', function() {
            var selection = frame.state().get('selection').toArray();
            var list = $('#fa-document-files-list');

            selection.forEach(function(attachment) {
                var id = attachment.id;
                var url = attachment.get('url') || '';
                var title = attachment.get('title') || 'Untitled';

                // Avoid duplicates
                if (list.find('li[data-id="' + id + '"]').length) return;

                var li = $('<li>').attr('data-id', id);
                li.append(
                    '<button type="button" class="button copy-shortcode-button" data-shortcode="[show_documents id=' + id + ']" style="margin-right:5px; font-size:11px;">Create Shortcode</button>' +
                    '<a href="' + url + '" target="_blank">' + title + '</a> ' +
                    '<a href="#" class="remove-document-file" style="margin-left:0.5em; color:#a00;">×</a>' +
                    '<input type="hidden" name="fa_document_files[]" value="' + id + '">'
                );
                list.append(li);
            });
        });

        frame.open();
    });

    // Remove document file
    $('#fa-document-files-list').on('click', '.remove-document-file', function(e) {
        e.preventDefault();
        $(this).closest('li').remove();
    });

    // Copy individual shortcode
    $('#fa-document-files-list').on('click', '.copy-shortcode-button', function() {
        var $btn = $(this);
        var shortcode = $btn.data('shortcode');

        navigator.clipboard.writeText(shortcode).then(function() {
            $btn.text('Copied!').delay(1500).queue(function() {
                $btn.text('Create Shortcode').dequeue();
            });
        }).catch(function(err) {
            console.error('Copy failed', err);
            alert('Failed to copy shortcode.');
        });
    });

    // Copy all shortcode
    $('#fa-document-files-wrapper').on('click', '.copy-shortcode-all-button', function() {
        var $btn = $(this);
        var shortcode = $btn.data('shortcode');

        navigator.clipboard.writeText(shortcode).then(function() {
            $btn.text('Copied!').delay(1500).queue(function() {
                $btn.text('Create Shortcode All').dequeue();
            });
        }).catch(function(err) {
            console.error('Copy failed', err);
            alert('Failed to copy shortcode.');
        });
    });
});
