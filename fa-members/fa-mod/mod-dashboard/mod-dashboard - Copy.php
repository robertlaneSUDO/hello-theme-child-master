<?php


/**
 * Plugin Name: Role-Based Permissions Meta Box
 * Description: A meta box in the post and page edit screen that allows setting permissions based on user roles.
 */

// Add the meta box to both posts and pages
function rbp_add_meta_box() {
    $screens = ['post', 'page']; // Add meta box to both posts and pages
    foreach ($screens as $screen) {
        add_meta_box(
            'rbp_role_permissions', // ID
            __('Role-Based Permissions', 'text_domain'), // Title
            'rbp_display_role_permissions_meta_box', // Callback
            $screen, // Screen (post type)
            'side', // Context
            'high' // Priority
        );
    }
}
add_action('add_meta_boxes', 'rbp_add_meta_box');

// Display the meta box
function rbp_display_role_permissions_meta_box($post) {
    // Get the saved roles
    $saved_roles = get_post_meta($post->ID, '_rbp_roles', true);

    // Get all roles
    global $wp_roles;
    $roles = $wp_roles->roles;

    // Use nonce for verification
    wp_nonce_field('rbp_save_role_permissions', 'rbp_role_permissions_nonce');

    // Display checkboxes for each role
    echo '<p>' . __('Select roles that can view this content:', 'text_domain') . '</p>';
    foreach ($roles as $role_key => $role) {
        $checked = is_array($saved_roles) && in_array($role_key, $saved_roles) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="rbp_roles[]" value="' . esc_attr($role_key) . '" ' . $checked . '>';
        echo esc_html($role['name']);
        echo '</label><br>';
    }
}

// Save the meta box data
function rbp_save_role_permissions($post_id) {
    // Check if our nonce is set.
    if (!isset($_POST['rbp_role_permissions_nonce'])) {
        return $post_id;
    }
    $nonce = $_POST['rbp_role_permissions_nonce'];

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($nonce, 'rbp_save_role_permissions')) {
        return $post_id;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // Check the user's permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    // Sanitize and save the roles
    $roles = isset($_POST['rbp_roles']) ? array_map('sanitize_text_field', $_POST['rbp_roles']) : array();
    update_post_meta($post_id, '_rbp_roles', $roles);
}
add_action('save_post', 'rbp_save_role_permissions');

if (is_single() || is_page()) {
    $post_roles = get_post_meta(get_the_ID(), '_rbp_roles', true);
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;

    // Check if the user has one of the allowed roles
    $has_access = false;
    $has_access = false;
    if (is_array($post_roles)) {
        foreach ($user_roles as $role) {
            if (in_array($role, $post_roles)) {
                $has_access = true;
                break;
            }
        }
    }

    if (!$has_access) {
        wp_die(__('You do not have permission to view this content.', 'text_domain'));
    }
}

