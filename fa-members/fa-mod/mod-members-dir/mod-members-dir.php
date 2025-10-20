<?php
/**
 * @var string $atts
 */

$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'default';
$userID = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
$action = isset($_GET['act']) ? sanitize_text_field($_GET['act']) : '';

include ('partials/header.php');

if ($view === 'default') {
    include ('partials/default.php');
} elseif ($view === 'view') {
    include ('partials/view.php');
} elseif ($view === 'edit') {
    include ('partials/edit.php');
} elseif ($view === 'add') {
  include ('partials/add.php');
} elseif ($view === 'delete') {
    include ('partials/delete.php');
}

include ('partials/footer.php');


function get_acf_user_data($user_id, $group_keys) {
    $all_user_data = [];

    // Convert group keys from string to array if necessary
    if (!is_array($group_keys)) {
        $group_keys = explode(',', $group_keys);
    }

    foreach ($group_keys as $key) {
        // Fetch each field group by key
        $group = acf_get_field_group($key);
        if ($group) {
            $fields = acf_get_fields($group);
            $group_data = [
                'group_label' => $group['title'],  // Retrieve and store the group label
                'fields' => []
            ];

            foreach ($fields as $field) {
                $value = get_field($field['key'], 'user_' . $user_id); // Fetching by field key (ID)

                // Store field label, value, type, and field key (ID)
                $group_data['fields'][$field['name']] = [
                    'label' => $field['label'],
                    'value' => $value,
                    'type' => $field['type'],
                    'field_id' => $field['key']  // Adding the field ID (key) here
                ];
            }

            $all_user_data[$group['key']] = $group_data;
        }
    }

    return $all_user_data;
}

add_action('admin_post_nopriv_create_new_user', 'handle_create_new_user');
add_action('admin_post_create_new_user', 'handle_create_new_user'); // for logged-in users

function get_acf_post($excerpt_search) {
    global $wpdb; // Access to the WordPress database object

    // Manually appending/prepending % for the wildcard search
    //$excerpt_search = '%' . $wpdb->esc_like($excerpt_search) . '%';

    // Prepare the SQL query, ensuring wildcards are included in the variable, not in the SQL string
    $query = "SELECT * FROM {$wpdb->posts} WHERE post_excerpt = '{$excerpt_search}' AND post_status = 'publish' AND post_type = 'acf-field' LIMIT 1";
    $post = $wpdb->get_row($query, ARRAY_A); // Get the result as an associative array

    // Check if a post was found
    if ($post) {
        return $post; // Return the post array if found
    } else {
        return null; // Return null if no post was found
    }
}

function check_if_group_has_permissions($field_group_key) {
    // Retrieve all permissions for the field group
    $create_roles = get_option('acf_field_create_meta_' . $field_group_key, []);
    $read_roles = get_option('acf_fiel_dread_meta_' . $field_group_key, []);
    $update_roles = get_option('acf_field_update_meta_' . $field_group_key, []);
    $delete_roles = get_option('acf_field_delete_meta_' . $field_group_key, []);

    // Check if any of the permissions are set (non-empty)
    if (!empty($create_roles) || !empty($read_roles) || !empty($update_roles) || !empty($delete_roles)) {
        return true; // Permissions are set
    } else {
        return false; // No permissions are set
    }
}
