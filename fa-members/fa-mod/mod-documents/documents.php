<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register Document Types taxonomy
 */
add_action('init', function() {
    register_taxonomy('document-type', ['document'], [
        'hierarchical' => true,
        'labels' => [
            'name' => 'Document Types',
            'singular_name' => 'Document Type',
            'menu_name' => 'Document Types',
            'all_items' => 'All Document Types',
            'edit_item' => 'Edit Document Type',
            'view_item' => 'View Document Type',
            'update_item' => 'Update Document Type',
            'add_new_item' => 'Add New Document Type',
            'new_item_name' => 'New Document Type Name',
            'search_items' => 'Search Document Types',
        ],
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'document-type'],
    ]);
});

/**
 * Rename uploaded files with CRC32 hash
 */
add_filter('wp_handle_upload_prefilter', function($file) {
    $info = pathinfo($file['name']);
    $ext = strtolower($info['extension'] ?? '');
    if (in_array($ext, ['pdf', 'doc', 'docx', 'txt'])) {
        $base = sanitize_file_name($info['filename']);
        $hash = strtolower(hash('crc32b', $file['name']));
        $file['name'] = "{$base}-{$hash}.{$ext}";
    }
    return $file;
});

/**
 * Add Document Files meta box
 */
add_action('add_meta_boxes', function() {
    add_meta_box('fa-documents-files', 'Document Files', 'fa_documents_files_metabox_cb', 'document', 'normal');
});
function fa_documents_files_metabox_cb($post) {
    $attachments = get_post_meta($post->ID, 'fa_document_files', true) ?: [];
    ?>
    <div id="fa-document-files-wrapper">
        <ul id="fa-document-files-list">
        <?php foreach ($attachments as $att_id):
            $url = wp_get_attachment_url($att_id);
            $attachment = get_post($att_id);
            $title = $attachment ? $attachment->post_title : 'Untitled';
            $date = $attachment ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $attachment->post_date) : '';
        ?>
            <li data-id="<?= esc_attr($att_id) ?>">
                <button type="button" class="button copy-shortcode-button" data-shortcode="[show_documents id=<?= esc_attr($att_id) ?>]" style="margin-right:5px; font-size:11px;">Create Shortcode</button>
                <a href="<?= esc_url($url) ?>" target="_blank"><?= esc_html($title) ?></a>
                <span style="margin-left:0.5em; color:#666;"><?= esc_html($date) ?></span>
                <a href="#" class="remove-document-file" style="margin-left:0.5em;color:#a00;">×</a>
                <input type="hidden" name="fa_document_files[]" value="<?= esc_attr($att_id) ?>">
            </li>
        <?php endforeach; ?>
        </ul>
        <div style="margin-top:10px;">
            <button type="button" class="button copy-shortcode-all-button" data-shortcode="[show_documents]" style="margin-right:10px;">Create Shortcode All</button>
            <button type="button" class="button button-primary" id="fa-add-document-file">Add File</button>
        </div>
    </div>
    <?php
}

/**
 * Enqueue admin JS + CSS
 */
add_action('admin_enqueue_scripts', function($hook) {
    global $post;
    if (in_array($hook, ['post-new.php', 'post.php']) && $post->post_type === 'document') {
        wp_enqueue_media();
        wp_enqueue_script('fa-documents-admin', get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-documents/documents.js', ['jquery'], filemtime(get_stylesheet_directory() . '/fa-members/fa-mod/mod-documents/documents.js'), true);
        wp_enqueue_style('fa-documents-style', get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-documents/documents.css', [], filemtime(get_stylesheet_directory() . '/fa-members/fa-mod/mod-documents/documents.css'));
    }
});

/**
 * Save document file IDs
 */
add_action('save_post', function($post_id) {
    if (!isset($_POST['fa_documents_nonce']) || !wp_verify_nonce($_POST['fa_documents_nonce'], 'fa_save_document_files')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'document') return;
    $ids = !empty($_POST['fa_document_files']) ? array_map('intval', $_POST['fa_document_files']) : [];
    if ($ids) update_post_meta($post_id, 'fa_document_files', $ids);
    else delete_post_meta($post_id, 'fa_document_files');
});

/**
 * [show_documents] shortcode (handles document or attachment ID)
 */
add_shortcode('show_documents', function($atts) {
    global $post;
    $atts = shortcode_atts(['id' => ''], $atts, 'show_documents');
    $target_id = $atts['id'] ? intval($atts['id']) : ($post ? $post->ID : 0);
    if (!$target_id) return '<p>No documents available.</p>';

    $target_post = get_post($target_id);
    if (!$target_post) return '<p>No documents available.</p>';
    if ($target_post->post_type === 'attachment') {
        $target_id = $target_post->post_parent;
        if (!$target_id) return '<p>No documents available.</p>';
    }

    $file_ids = get_post_meta($target_id, 'fa_document_files', true);
    if (empty($file_ids) || !is_array($file_ids)) return '<p>No documents available.</p>';

    $output = '<ul class="fa-document-files">';
    foreach ($file_ids as $file_id) {
        $url = add_query_arg('fa_download', $file_id, home_url('/'));
        $attachment = get_post($file_id);
        if (!$attachment) continue;
        $title = $attachment->post_title;
        $date = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $attachment->post_date);
        $output .= '<li><a href="' . esc_url($url) . '">' . esc_html($title) . '</a> ' . esc_html($date) . '</li>';
    }
    return $output . '</ul>';
});

/**
 * Secure file download handler
 */
add_action('init', function() {
    if (isset($_GET['fa_download'])) {
        $fid = intval($_GET['fa_download']);
        $path = get_attached_file($fid);
        if (!$fid || !$path || !file_exists($path)) wp_die('File not found.');
        $attachment = get_post($fid);
        $parent_id = $attachment->post_parent;
        if (!$parent_id || get_post_type($parent_id) !== 'document') wp_die('No permission to download this file.');
        $mime = mime_content_type($path);
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
});

/**
 * Load frontend CSS when shortcodes present
 */
add_action('wp_enqueue_scripts', function() {
    global $post;
    if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'documents') || has_shortcode($post->post_content, 'show_documents'))) {
        wp_enqueue_style('fa-documents-style', get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-documents/documents.css', [], filemtime(get_stylesheet_directory() . '/fa-members/fa-mod/mod-documents/documents.css'));
    }
});
add_shortcode('documents', function($atts) {
    $atts = shortcode_atts([
        'type' => '',
    ], $atts, 'documents');

    $selected_type = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : $atts['type'];
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'title';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';
    $paged = max(1, get_query_var('paged', 1));

    $query_args = [
        'post_type' => 'document',
        'posts_per_page' => 10,
        'paged' => $paged,
        'orderby' => $orderby,
        'order' => $order,
    ];

    if (!empty($selected_type)) {
        $query_args['tax_query'] = [[
            'taxonomy' => 'document-type',
            'field' => 'slug',
            'terms' => $selected_type,
        ]];
    }

    $q = new WP_Query($query_args);

    ob_start();

    $document_types = get_terms(['taxonomy' => 'document-type', 'hide_empty' => false]);

    echo '<form method="get" class="fa-documents-filter">';
    echo '<select name="document_type"><option value="">All Document Types</option>';
    foreach ($document_types as $type) {
        printf('<option value="%s"%s>%s</option>',
            esc_attr($type->slug),
            selected($selected_type, $type->slug, false),
            esc_html($type->name)
        );
    }
    echo '</select>';

    echo '<select name="orderby">';
    echo '<option value="title"' . selected($orderby, 'title', false) . '>Title</option>';
    echo '<option value="date"' . selected($orderby, 'date', false) . '>Date</option>';
    echo '</select>';

    echo '<select name="order">';
    echo '<option value="ASC"' . selected($order, 'ASC', false) . '>Ascending</option>';
    echo '<option value="DESC"' . selected($order, 'DESC', false) . '>Descending</option>';
    echo '</select>';

    echo '<button type="submit">Apply</button>';
    echo '</form>';

    if ($q->have_posts()) {
        echo '<ul class="fa-document-list">';
        while ($q->have_posts()) {
            $q->the_post();
            echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
        echo '</ul>';

        echo paginate_links([
            'total' => $q->max_num_pages,
            'current' => $paged,
            'type' => 'list',
        ]);
    } else {
        echo '<p>No documents found.</p>';
    }

    wp_reset_postdata();
    return ob_get_clean();
});
