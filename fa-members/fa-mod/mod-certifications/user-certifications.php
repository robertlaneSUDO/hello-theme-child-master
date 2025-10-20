<?php
// Hook to show in user profile
add_action('show_user_profile', 'render_user_certifications_section');
add_action('edit_user_profile', 'render_user_certifications_section');

// Hook to save
add_action('personal_options_update', 'save_user_certifications_section');
add_action('edit_user_profile_update', 'save_user_certifications_section');

// Add enctype to user form
add_action('user_edit_form_tag', function () {
    echo ' enctype="multipart/form-data"';
});

function render_user_certifications_section($user) {
    if (!current_user_can('edit_user', $user->ID)) {
        return;
    }

    // Fetch assigned certs
    $assigned_data = get_user_meta($user->ID, 'user_certifications', true);
    $assigned_data = is_array($assigned_data) ? $assigned_data : [];

    // Get all certifications
    $all_certifications = get_posts([
        'post_type' => 'certification',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);

    echo '<h2 style="margin-top:30px; border-bottom:1px solid #ccc; padding-bottom:5px;">Certifications</h2>';
    echo '<table style="width:100%; border-collapse: collapse; margin-bottom:20px; font-size:14px;">
        <thead>
            <tr style="background:#f7f7f7;">
                <th style="border:1px solid #ddd; padding:10px; text-align:left;">Certification</th>
                <th style="border:1px solid #ddd; padding:10px; text-align:left;">Expiration Date</th>
                <th style="border:1px solid #ddd; padding:10px; text-align:left;">View</th>
                <th style="border:1px solid #ddd; padding:10px; text-align:left;">Delete</th>
            </tr>
        </thead>
        <tbody>';

    if (!empty($assigned_data)) {
        foreach ($assigned_data as $item) {
            $cert = get_post($item['cert_id']);
            $cert_name = $cert ? esc_html($cert->post_title) : 'Unknown Certification';
            $expire_date = !empty($item['expire_date']) ? esc_html(date_i18n('F j, Y', strtotime($item['expire_date']))) : 'N/A';
            $file_url = !empty($item['file_url']) ? esc_url($item['file_url']) : '';

            echo '<tr>';
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
                <input type="checkbox" name="certifications[' . esc_attr($item['cert_id']) . '][delete]" value="1">
            </td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4" style="border:1px solid #ddd; padding:10px;">No certifications assigned yet.</td></tr>';
    }

    echo '</tbody></table>';

    // Add new certification block
    echo '<div style="margin-top:30px; border:1px solid #ccc; padding:15px; background:#f9f9f9; border-radius:5px;">';
    echo '<h3 style="margin-top:0;">Add New Certification</h3>';
    echo '<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">';
    echo '<select name="new_certification_type" style="flex: 1; min-width: 180px; padding:8px; border:1px solid #ccc; border-radius:4px;">
        <option value="">Select type</option>';
    foreach ($all_certifications as $cert_type) {
        echo '<option value="' . esc_attr($cert_type->ID) . '">' . esc_html($cert_type->post_title) . '</option>';
    }
    echo '</select>';
    echo '<input type="date" name="new_certification_expire_date" style="flex: 1; min-width: 180px; padding:8px; border:1px solid #ccc; border-radius:4px;">';
    echo '<input type="file" name="new_certification_file" accept=".pdf,.jpg,.png" style="flex: 2; min-width: 220px; padding:8px; border:1px solid #ccc; border-radius:4px;">';
    echo '</div>';
    echo '</div>';
}

function save_user_certifications_section($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $existing_certs = get_user_meta($user_id, 'user_certifications', true);
    $existing_certs = is_array($existing_certs) ? $existing_certs : [];

    $upload_dir = wp_normalize_path(get_stylesheet_directory() . '/fa-members/fa-mod/mod-certifications/certs/');
    $upload_url = get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-certifications/certs/';

    // Ensure certs folder exists
    if (!file_exists($upload_dir)) {
        if (!wp_mkdir_p($upload_dir)) {
            error_log("Failed to create directory: " . $upload_dir);
            return;
        }
    }

    // Handle deletes
    if (!empty($_POST['certifications'])) {
        foreach ($existing_certs as $index => $cert_item) {
            $cert_id = $cert_item['cert_id'];
            if (isset($_POST['certifications'][$cert_id]['delete'])) {
                if (!empty($cert_item['file_url'])) {
                    $file_path = str_replace(get_stylesheet_directory_uri(), get_stylesheet_directory(), $cert_item['file_url']);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                unset($existing_certs[$index]); // Remove from array
            }
        }
    }

    // Add new certification
    if (!empty($_POST['new_certification_type']) && !empty($_POST['new_certification_expire_date'])) {
        $selected_cert_id = intval($_POST['new_certification_type']);
        $expire_date = sanitize_text_field($_POST['new_certification_expire_date']);
        $file_url = '';

        if (isset($_FILES['new_certification_file']) && $_FILES['new_certification_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['new_certification_file']['tmp_name'];
            $file_name = sanitize_file_name($_FILES['new_certification_file']['name']);
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = 'certification-user' . $user_id . '-' . uniqid() . '.' . $ext;
            $destination = wp_normalize_path($upload_dir . $new_file_name);

            if (is_uploaded_file($file_tmp)) {
                if (move_uploaded_file($file_tmp, $destination)) {
                    $file_url = $upload_url . $new_file_name;
                    error_log("✅ File uploaded successfully to: " . $destination);
                } else {
                    error_log("❌ Failed to move file to: " . $destination);
                }
            } else {
                error_log("❌ File is not a valid uploaded file: " . $file_tmp);
            }
        }

        $existing_certs[] = [
            'cert_id' => $selected_cert_id,
            'expire_date' => $expire_date,
            'file_url' => $file_url
        ];
    }

    // Save back
    update_user_meta($user_id, 'user_certifications', array_values($existing_certs));
}
