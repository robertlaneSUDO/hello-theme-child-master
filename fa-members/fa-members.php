<?php
/**
 * @var string $name
 */

function mod_mem_enqueue_styles() {

    wp_enqueue_style('mod-mem-style', get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-members-dir/partials/assets/style.css', array(), '1.0', 'all');
}
add_action('wp_enqueue_scripts', 'mod_mem_enqueue_styles');

function enqueue_datatables_scripts() {
    // Enqueue DataTables CSS
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css');

    // Enqueue DataTables JavaScript
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js', array('jquery'), null, true);

    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true);


    // DataTables Buttons
    wp_enqueue_script('datatables-buttons', 'https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js', array('datatables-js'), null, true);
    wp_enqueue_script('buttons-html5', 'https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js', array('datatables-buttons'), null, true);  // For HTML 5 buttons
    wp_enqueue_script('buttons-print', 'https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js', array('datatables-buttons'), null, true);  // For print button

    wp_enqueue_style('datatables-buttons-css', 'https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css');



}
add_action('wp_enqueue_scripts', 'enqueue_datatables_scripts');


class FA_Members_SC {
    // Constructor to register the shortcode
    public function __construct() {
        add_shortcode('fa-members', array($this, 'render_shortcode'));
    }

    // Method to handle the shortcode output
    public function render_shortcode($atts) {

        // Default to an empty module if not set
        $module = isset($atts['module']) ? $atts['module'] : 'default';

        // Determine the template file based on module
        $template_file = $this->get_template_file($module);

        // Start output buffering
        ob_start();

        // Check if template file exists and include it
        if (file_exists($template_file)) {
            include($template_file);
        } else {
            // Fallback content if the template file does not exist
            echo '<p>Module not found</p>';
        }

        // Return the buffered output
        return ob_get_clean();
    }

    // Helper method to determine the template file path
    private function get_template_file($post_type) {
        // Define the path to your custom template files
        $template_directory = get_stylesheet_directory() . '/fa-members/fa-mod/mod-' . $post_type;

        // Construct the file name based on post_type
        $template_file_name = '/mod-' . $post_type . '.php';

        // Full path to the template file
        $template_file_path = $template_directory . $template_file_name;

        return $template_file_path;
    }
}

new FA_Members_SC();

function get_current_user_info($passed_id = null) {
    // Check if user is logged in
    if (is_user_logged_in()) {
        // Get current user data
        $user = wp_get_current_user();
        $user_id = $user->ID; // User ID

        // Check if a user ID is passed and if it matches the current user's ID
        $is_current_user = ($passed_id !== null && $passed_id == $user_id);

        // Return true if the passed ID matches the current user ID, or just return true because the user is logged in
        return $is_current_user || true;
    } else {
        return false; // User is not logged in
    }
}

// Prevent loop by skipping redirect on specific pages and actions
function check_user_redirect() {
    // Avoid redirect on login, register, or any other specific pages
    if (is_page('login') || is_page('register')) {
        return;
    }

    // Avoid redirect during certain actions (login, logout, admin)
    if (in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php']) || is_admin()) {
        return;
    }

    // Check user info and redirect if necessary
    if (!get_current_user_info()) {
        wp_redirect(home_url('/login'));
        exit();
    }
}
add_action('template_redirect', 'check_user_redirect');

// Function to get all capabilities of the logged-in user
function get_logged_in_user_capabilities() {
    // Get the current user object
    $current_user = wp_get_current_user();

    // If the user is logged in, return their capabilities as an array
    if ($current_user instanceof WP_User) {
        return $current_user->allcaps; // This returns all capabilities as an array
    }

    // If not logged in, return an empty array
    return array();
}

// Make the capabilities globally available
function set_global_user_capabilities() {
    global $user_capabilities;
    $user_capabilities = get_logged_in_user_capabilities();

}
add_action('init', 'set_global_user_capabilities');

// Function to check if the current user has a specific capability
function check_user_capability($capability) {
    global $user_capabilities;

    // Ensure the global variable is populated
    if (empty($user_capabilities)) {
        set_global_user_capabilities(); // Ensure it's populated
    }

    // Print capabilities for debugging
   // print_r($user_capabilities); // Debugging: to check if the global is set
   // print_r($user_capabilities); // Debugging: to check if the global is set

    // Check if the user has the specific capability
    if (isset($user_capabilities[$capability]) && $user_capabilities[$capability]) {
        return true;
    }

    return false;
}
function add_crud_meta_to_acf_field($field) {
    // Ensure the $field has the necessary data
    if (!isset($field['key'])) {
        return;
    }

    // Get all available user roles
    global $wp_roles;
    $roles = $wp_roles->roles;

    // Prepare roles in 'value => label' format for the select field
    $role_choices = [];
    foreach ($roles as $role_key => $role_data) {
        $role_choices[$role_key] = $role_data['name'];
    }

    // Add "Create" permission field
    acf_render_field_setting($field, array(
        'label' => __('Create Permission'),
        'instructions' => __('Select the user roles allowed to Create this field.'),
        'type' => 'select',
        'name' => 'acf_field_create_meta',
        'choices' => $role_choices,
        'multiple' => true,
        'ui' => true,  // Adds the fancy multi-select UI
        'allow_null' => true,
        'placeholder' => __('Select user roles'),
    ));

    // Add "Read" permission field
    acf_render_field_setting($field, array(
        'label' => __('Read Permission'),
        'instructions' => __('Select the user roles allowed to Read this field.'),
        'type' => 'select',
        'name' => 'acf_field_read_meta',
        'choices' => $role_choices,
        'multiple' => true,
        'ui' => true,
        'allow_null' => true,
        'placeholder' => __('Select user roles'),
    ));

    // Add "Update" permission field
    acf_render_field_setting($field, array(
        'label' => __('Update Permission'),
        'instructions' => __('Select the user roles allowed to Update this field.'),
        'type' => 'select',
        'name' => 'acf_field_update_meta',
        'choices' => $role_choices,
        'multiple' => true,
        'ui' => true,
        'allow_null' => true,
        'placeholder' => __('Select user roles'),
    ));

    // Add "Delete" permission field
    acf_render_field_setting($field, array(
        'label' => __('Delete Permission'),
        'instructions' => __('Select the user roles allowed to Delete this field.'),
        'type' => 'select',
        'name' => 'acf_field_delete_meta',
        'choices' => $role_choices,
        'multiple' => true,
        'ui' => true,
        'allow_null' => true,
        'placeholder' => __('Select user roles'),
    ));
}

add_action('acf/render_field_settings', 'add_crud_meta_to_acf_field', 10, 1);

function save_crud_roles_meta($field) {
    // Save "Create" roles
    if (isset($field['acf_field_create_meta'])) {
        update_post_meta($field['ID'], 'acf_field_create_meta', $field['acf_field_create_meta']);
    }

    // Save "Read" roles
    if (isset($field['acf_field_read_meta'])) {
        update_post_meta($field['ID'], 'acf_field_read_meta', $field['acf_field_read_meta']);
    }

    // Save "Update" roles
    if (isset($field['acf_field_update_meta'])) {
        update_post_meta($field['ID'], 'acf_field_update_meta', $field['acf_field_update_meta']);
    }

    // Save "Delete" roles
    if (isset($field['acf_field_delete_meta'])) {
        update_post_meta($field['ID'], 'acf_field_delete_meta', $field['acf_field_delete_meta']);
    }
    
    // Return the field to ensure it is saved by ACF
    return $field;
}
add_filter('acf/update_field', 'save_crud_roles_meta', 10, 1);

/*
add_action('acf/render_field_group_settings', 'add_crud_meta_to_acf_group');

function add_crud_meta_to_acf_group($field_group) {
    // Get all available user roles
    global $wp_roles;
    $roles = $wp_roles->roles;

    // Prepare roles in 'value => label' format for select field
    $role_choices = [];
    foreach ($roles as $role_key => $role_data) {
        $role_choices[$role_key] = $role_data['name'];
    }

    // Load previously saved "Create" roles for the group
    $create_roles = get_option('acf_group_create_meta_' . $field_group['key'], []);


    // Load previously saved "Read" roles for the group
    $read_roles = get_option('acf_group_read_meta_' . $field_group['key'], []);

    // Add "Read" permission field with multi-select for user roles
    acf_render_field_setting($field_group, array(
        'label' => __('Read Permission'),
        'instructions' => __('Select the user roles allowed to Read fields in this group.'),
        'type' => 'select',
        'name' => 'acf_group_read_meta',
        'choices' => $role_choices,
        'multiple' => true,
        'ui' => true,
        'allow_null' => true,
        'default_value' => $read_roles ? $read_roles : [],
        'placeholder' => __('Select user roles'),
    ));

}


add_action('acf/update_field_group', 'save_crud_roles_meta_group', 10, 1);

function save_crud_roles_meta_group($field_group) {
    // Print the post data to inspect the structure
    // print_r($_POST);
    // Check for "Read" roles in the top-level $_POST
    if (isset($_POST['acf_group_read_meta'])) {
        update_option('acf_group_read_meta_' . $field_group['key'], $_POST['acf_group_read_meta']);
    }

}
 */

function user_has_read_permission($field_group_key, $user_id = null) {

    
    $user = wp_get_current_user();
   // print_r($user);
    // Get the ACF field group key
    // Get the "Read" roles for the ACF field group from the options table
    $read_roles = get_option('acf_group_read_meta_' . $field_group_key, []);

    if (empty($read_roles)) {
        return true;
    }
    // Check if the user's roles intersect with the allowed "Read" roles
    return (bool) array_intersect($user->roles, $read_roles);
}

function get_acf_fields_permissions_check($acf_field_id, $permission_type) {
    // Available permission types
    $valid_permissions = ['create', 'read', 'update', 'delete'];

    // Check if the provided permission type is valid
    if (!in_array($permission_type, $valid_permissions)) {
        return false; // Invalid permission type
    }

    global $wpdb;

    // Step 1: Retrieve the post ID where post_name matches the ACF field ID
    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'acf-field'",
        $acf_field_id
    ));

    // If no post is found, return false
    if (!$post_id) {
        return false;
    }

    // Step 2: Construct the meta key based on permission type
    $meta_key = 'acf_field_' . $permission_type . '_meta';
  // echo  $meta_key;
    // Step 3: Retrieve the allowed roles from post meta
    $allowed_roles = get_post_meta($post_id, $meta_key, true);

    // Check if there are any allowed roles set
    if (empty($allowed_roles) || !is_array($allowed_roles)) {
        return false; // No permissions set, deny access by default
    }

    // Step 4: Get the current user roles
    $current_user = wp_get_current_user();
    if (empty($current_user->roles)) {
        return false; // User has no roles, deny access
    }

    // Step 5: Check if the user's roles match any of the allowed roles
    if (array_intersect($current_user->roles, $allowed_roles)) {
        return true; // At least one role matches, grant access
    }

    return false; // No matching roles, deny access
}


/**
 * Get the hierarchy position of a user's role based on members_role_hierarchy.
 *
 * @param int $user_id The user ID.
 * @return int|string Hierarchy position or error message.
 */
function get_user_role_hierarchy_position($user_id) {
    // Validate user ID
    if (!is_numeric($user_id) || $user_id <= 0) {
        return 'Invalid user ID.';
    }

    // Fetch user data
    $user = get_userdata($user_id);
    if (!$user) {
        return 'User not found.';
    }

    // Fetch the role hierarchy from the database
    $role_hierarchy = get_option('members_role_hierarchy');
    if (!$role_hierarchy || !is_array($role_hierarchy)) {
        return 'Role hierarchy is not defined.';
    }

    // Get the user's primary role
    $roles = $user->roles; // Array of roles
    $primary_role = $roles[0] ?? null; // Assume the first role is the primary

    // Check if the role exists in the hierarchy
    if (!$primary_role || !isset($role_hierarchy[$primary_role])) {
        return 'User role not found in the hierarchy.';
    }

    // Return the hierarchy position
    return $role_hierarchy[$primary_role];
}

// Example usage:

/**
 * Check if a user's role hierarchy position satisfies a condition.
 *
 * @param int $user_id The user ID.
 * @param int $position The position to compare against.
 * @param string $operator The comparison operator ('<', '>', '==', '<=', '>=', '!=').
 * @return bool|string True if the condition is met, false otherwise, or an error message.
 */
function check_role_hierarchy_position($user_id, $position, $operator) {
    // Validate user ID
    if (!is_numeric($user_id) || $user_id <= 0) {
        return 'Invalid user ID.';
    }

    // Validate operator
    $valid_operators = ['<', '>', '==', '<=', '>=', '!='];
    if (!in_array($operator, $valid_operators, true)) {
        return 'Invalid comparison operator.';
    }

    // Fetch user data
    $user = get_userdata($user_id);
    if (!$user) {
        return 'User not found.';
    }

    // Fetch the role hierarchy from the database
    $role_hierarchy = get_option('members_role_hierarchy');
    if (!$role_hierarchy || !is_array($role_hierarchy)) {
        return 'Role hierarchy is not defined.';
    }

    // Get the user's primary role
    $roles = $user->roles; // Array of roles
    $primary_role = $roles[0] ?? null; // Assume the first role is the primary

    // Check if the role exists in the hierarchy
    if (!$primary_role || !isset($role_hierarchy[$primary_role])) {
        return 'User role not found in the hierarchy.';
    }

    // Get the user's role position
    $user_position = $role_hierarchy[$primary_role];

    // Evaluate the condition
    switch ($operator) {
        case '<':
            return $user_position < $position;
        case '>':
            return $user_position > $position;
        case '==':
            return $user_position == $position;
        case '<=':
            return $user_position <= $position;
        case '>=':
            return $user_position >= $position;
        case '!=':
            return $user_position != $position;
        default:
            return 'Invalid operator.';
    }
}

function get_current_post_type_frontend() {
    if (is_singular()) { // Check if currently viewing a single post/page/custom post type
        return get_post_type(); // Return the current post type
    }

    return null; // Return null if no valid post type is found
}

add_action('admin_footer', function () {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Select the "Administrator" checkbox by its value
            const adminCheckbox = document.querySelector('input[type="checkbox"][name="members_access_role[]"][value="administrator"]');

            if (adminCheckbox) {
                // Check the checkbox
                adminCheckbox.checked = true;

                // Disable the checkbox to make it unchangeable
                adminCheckbox.disabled = true;
            }
        });
    </script>
    <?php
});

add_action('template_redirect', function() {
    if (is_user_logged_in() && isset($_GET['view']) && $_GET['view'] === 'my-profile') {
        $current_user = wp_get_current_user();
        $profile_url = add_query_arg([
            'view' => 'edit',
            'id'   => $current_user->ID,
        ], home_url('/members/'));

        wp_safe_redirect($profile_url);
        exit;
    }
});

add_filter('nav_menu_css_class', function($classes, $item) {
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();

        $is_edit_page = isset($_GET['view'], $_GET['id']) && $_GET['view'] === 'edit' && intval($_GET['id']) === $current_user_id;
        $is_myprofile_url = strpos($item->url, '/members/?view=my-profile') !== false;

        if ($is_edit_page && $is_myprofile_url) {
            $classes[] = 'current-menu-item';
        }
    }

    return $classes;
}, 10, 2);

function enqueue_mods_js() {
    wp_enqueue_script('mod-js', get_stylesheet_directory_uri() . '/fa-members/fa-mod/assets/js/mods.js', [], false, true);
}

add_action('wp_enqueue_scripts', 'enqueue_mods_js');

include 'fa-mod/mod-dashboard/mod-dashboard.php';
include 'fa-mod/mod-announcements/announcements.php';
include 'fa-mod/mod-certifications/certifications.php';
include 'fa-mod/mod-documents/documents.php';
certifications_module_init();
include 'admin/admin.php';
