<?php
// Shortcode [user_certifications user_id=""]

add_shortcode('user_certifications', function ($atts) {
    $atts = shortcode_atts([
        'user_id' => get_current_user_id(),
    ], $atts);

    $user_id = intval($atts['user_id']);
    if (!$user_id) {
        return '<p>Error: Invalid user ID.</p>';
    }

    ob_start();

    $assigned_data = get_user_meta($user_id, 'user_certifications', true);
    $assigned_data = is_array($assigned_data) ? $assigned_data : [];

    $all_certifications = get_posts([
        'post_type' => 'certification',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    ?>

    <div id="user-certifications-wrap" style="margin-top: 20px;">

        <h3>Your Certifications</h3>
        <table style="width:100%; border-collapse: collapse; margin-bottom:20px; font-size:14px;">
            <thead>
            <tr style="background:#f7f7f7;">
                <th style="border:1px solid #ddd; padding:10px;">Certification</th>
                <th style="border:1px solid #ddd; padding:10px;">Expiration Date</th>
                <th style="border:1px solid #ddd; padding:10px;">View</th>
                <th style="border:1px solid #ddd; padding:10px;">Delete</th>
            </tr>
            </thead>
            <tbody id="certifications-list">
            <?php
            if (!empty($assigned_data)) {
                foreach ($assigned_data as $item) {
                    $cert = get_post($item['cert_id']);
                    $cert_name = $cert ? esc_html($cert->post_title) : 'Unknown Certification';
                    $expire_date = !empty($item['expire_date']) ? esc_html(date_i18n('F j, Y', strtotime($item['expire_date']))) : 'N/A';
                    $file_url = !empty($item['file_url']) ? esc_url($item['file_url']) : '';

                    echo '<tr data-cert-id="' . esc_attr($item['cert_id']) . '">';
                    echo '<td style="border:1px solid #ddd; padding:10px;">' . $cert_name . '</td>';
                    echo '<td style="border:1px solid #ddd; padding:10px;">' . $expire_date . '</td>';
                    echo '<td style="border:1px solid #ddd; padding:10px;">';
                    if ($file_url) {
                        echo '<a href="' . $file_url . '" target="_blank">View</a>';
                    } else {
                        echo 'N/A';
                    }
                    echo '</td>';
                    echo '<td style="border:1px solid #ddd; padding:10px; text-align:center;">
                        <button class="delete-cert-btn" data-cert-id="' . esc_attr($item['cert_id']) . '">Delete</button>
                    </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4" style="border:1px solid #ddd; padding:10px;">No certifications assigned yet.</td></tr>';
            }
            ?>
            </tbody>
        </table>

        <h3>Add New Certification</h3>
        <div id="certifications-message" style="margin-bottom:10px; color: green;"></div>
        <form id="add-certification-form" enctype="multipart/form-data">
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <select name="certification_type" required style="flex: 1; min-width: 180px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    <option value="">Select type</option>
                    <?php foreach ($all_certifications as $cert_type) {
                        echo '<option value="' . esc_attr($cert_type->ID) . '">' . esc_html($cert_type->post_title) . '</option>';
                    } ?>
                </select>
                <input type="date" name="certification_expire_date" required style="flex: 1; min-width: 180px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                <input type="file" name="certification_file" accept=".pdf,.jpg,.png" required style="flex: 2; min-width: 220px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                <button type="submit" style="padding:8px 15px;">Add</button>
            </div>
            <?php wp_nonce_field('add_user_certification', 'certifications_nonce'); ?>
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
        </form>

    </div>

    <?php
    // Enqueue JS
    wp_enqueue_script('user-certifications-ajax', get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-certifications/user-certifications.js', ['jquery'], null, true);
    wp_localize_script('user-certifications-ajax', 'certifications_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);

    return ob_get_clean();
});
