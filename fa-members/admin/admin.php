<?php

if (!function_exists('wp_doing_rest')) {
    function wp_doing_rest() {
        return defined('REST_REQUEST') && REST_REQUEST;
    }
}

// Add the admin menu page
// 1. Add the Members Admin Main Page and Submenu
function members_admin_menu_pages() {
    // Main Members Admin Page
    add_menu_page(
        'Members Admin Page',      // Page title
        'Members Admin',           // Menu title
        'manage_options',          // Capability
        'members-admin-page',      // Menu slug
        'members_admin_page_content', // Callback function
        'dashicons-groups',        // Icon
        90                         // Position
    );

    // Submenu for Menu Items
    add_submenu_page(
        'members-admin-page',      // Parent slug
        'Menu Items',              // Page title
        'Menu Items',              // Menu title
        'manage_options',          // Capability
        'menu-items-page',         // Menu slug
        'menu_items_page_content'  // Callback function
    );
}
add_action('admin_menu', 'members_admin_menu_pages');

// 2. Render the Main Members Admin Page
function members_admin_page_content() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Members Admin Page', 'text-domain'); ?></h1>
        <p><?php esc_html_e('Hello! Welcome to the Members Admin page.', 'text-domain'); ?></p>
    </div>
    <?php
}

// 3. Render the Menu Items Submenu Page
function menu_items_page_content() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $menu, $submenu; // Access the WP-Admin menu and sub-menu items
    $roles = wp_roles()->roles; // Fetch all roles
    $role_hierarchy = get_option('members_role_hierarchy', []); // Fetch role hierarchy
    $allowed_menu_settings = get_option('allowed_admin_menu_items_by_role', []); // Saved settings

    // Save settings when form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin_menu_visibility'])) {
        check_admin_referer('allowed_admin_menu_items_nonce_action', 'allowed_admin_menu_items_nonce');
        $allowed_menu_items = $_POST['allowed_menu_items'] ?? [];
        update_option('allowed_admin_menu_items_by_role', $allowed_menu_items);
        echo '<div class="updated notice"><p>Settings saved successfully!</p></div>';
        $allowed_menu_settings = $allowed_menu_items; // Refresh saved data
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Menu Items', 'text-domain'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('allowed_admin_menu_items_nonce_action', 'allowed_admin_menu_items_nonce'); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th><?php esc_html_e('Role', 'text-domain'); ?></th>
                    <th><?php esc_html_e('WP-Admin Menu Items to Show', 'text-domain'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($roles as $role_key => $role): ?>
                    <?php
                    // Exclude administrators and roles with a hierarchy of 50 or less
                    $hierarchy = $role_hierarchy[$role_key] ?? 0;
                    if ($role_key === 'administrator' || $hierarchy <= 50) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($role['name']); ?></strong></td>
                        <td>
                            <div class="role-section" data-role-section="<?php echo esc_attr($role_key); ?>">
                                <?php foreach ($menu as $menu_item): ?>
                                    <?php
                                    $slug = $menu_item[2];
                                    if (strpos($slug, 'separator') !== false || empty($slug)) {
                                        continue;
                                    }
                                    $menu_title = wp_strip_all_tags($menu_item[0]);
                                    ?>
                                    <!-- Main Menu Item -->
                                    <label>
                                        <input type="checkbox" class="menu-checkbox" data-role="<?php echo esc_attr($role_key); ?>" data-menu-id="<?php echo esc_attr($slug); ?>"
                                               name="allowed_menu_items[<?php echo esc_attr($role_key); ?>][]"
                                               value="<?php echo esc_attr($slug); ?>"
                                            <?php echo isset($allowed_menu_settings[$role_key]) && in_array($slug, $allowed_menu_settings[$role_key]) ? 'checked' : ''; ?>>
                                        <strong><?php echo esc_html($menu_title); ?></strong>
                                    </label><br>

                                    <!-- Submenu Items -->
                                    <?php if (isset($submenu[$slug])): ?>
                                        <ul class="submenu-list" style="margin-left: 20px; display: none;" data-parent-id="<?php echo esc_attr($slug); ?>">
                                            <?php foreach ($submenu[$slug] as $sub_item): ?>
                                                <?php
                                                $sub_slug = $sub_item[2];
                                                if (strpos($sub_slug, 'separator') !== false || empty($sub_slug)) {
                                                    continue;
                                                }
                                                $sub_title = wp_strip_all_tags($sub_item[0]);
                                                ?>
                                                <li>
                                                    <label>
                                                        <input type="checkbox"
                                                               name="allowed_menu_items[<?php echo esc_attr($role_key); ?>][]"
                                                               value="<?php echo esc_attr($sub_slug); ?>"
                                                            <?php echo isset($allowed_menu_settings[$role_key]) && in_array($sub_slug, $allowed_menu_settings[$role_key]) ? 'checked' : ''; ?>>
                                                        <?php echo esc_html($sub_title); ?>
                                                    </label>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><input type="submit" name="save_admin_menu_visibility" class="button button-primary" value="Save Changes"></p>
        </form>
    </div>

    <!-- JavaScript to Dynamically Show/Hide Submenus -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.role-section').forEach(function (roleSection) {
                const menuCheckboxes = roleSection.querySelectorAll('.menu-checkbox');

                menuCheckboxes.forEach(function (checkbox) {
                    toggleSubmenus(checkbox, roleSection); // Initial state check
                    checkbox.addEventListener('change', function () {
                        toggleSubmenus(checkbox, roleSection);
                    });
                });

                function toggleSubmenus(checkbox, section) {
                    const menuId = checkbox.dataset.menuId;
                    const submenus = section.querySelectorAll(`.submenu-list[data-parent-id="${menuId}"]`);
                    submenus.forEach(function (submenu) {
                        if (checkbox.checked) {
                            submenu.style.display = 'block';
                        } else {
                            submenu.style.display = 'none';
                        }
                    });
                }

                // Show submenus for checked parent menus on page load
                menuCheckboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        toggleSubmenus(checkbox, roleSection);
                    }
                });
            });
        });
    </script>
    <?php
}

// 4. Hide All Menu and Sub-Menu Items by Default, Allow Selected Ones
add_action('admin_menu', 'filter_admin_menu_items_by_role', 999);
function filter_admin_menu_items_by_role() {
    if (current_user_can('administrator')) {
        return; // Administrators see all menu items
    }

    global $menu, $submenu; // Access the WP-Admin menu and sub-menu items
    $user = wp_get_current_user();
    $roles = $user->roles;
    $allowed_menu_settings = get_option('allowed_admin_menu_items_by_role', []);

    // Combine allowed menu items for all roles the user has
    $allowed_menus = [];
    foreach ($roles as $role) {
        if (isset($allowed_menu_settings[$role])) {
            $allowed_menus = array_merge($allowed_menus, $allowed_menu_settings[$role]);
        }
    }

    // Remove duplicate entries
    $allowed_menus = array_unique($allowed_menus);

    // Hide all main menu items not in the allowed list
    foreach ($menu as $menu_item) {
        $menu_slug = $menu_item[2];
        if (!in_array($menu_slug, $allowed_menus)) {
            remove_menu_page($menu_slug); // Remove main menu item
        }
    }

    // Hide all sub-menu items not in the allowed list
    foreach ($submenu as $parent_slug => $sub_items) {
        foreach ($sub_items as $sub_item) {
            $sub_menu_slug = $sub_item[2];
            if (!in_array($sub_menu_slug, $allowed_menus)) {
                remove_submenu_page($parent_slug, $sub_menu_slug); // Remove sub-menu item
            }
        }
    }
}


function disable_admin_bar_with_css_based_on_role_hierarchy() {
    $user_id = get_current_user_id(); // Get the current user ID

    // Check if the user's role position satisfies the condition
    if (is_user_logged_in() && check_role_hierarchy_position($user_id, 100, '<')) {
        // Add inline CSS to hide the admin bar and fix the margin
        add_action('wp_head', function() {
            echo '<style>
                #wpadminbar { display: none !important; }
                html { margin-top: 0 !important; }
                #wpbody { margin-top: -34px !important; }
            </style>';
        });

        // Also add it for the admin area
        add_action('admin_head', function() {
            echo '<style>
                #wpadminbar { display: none !important; }
                html { margin-top: 0 !important; }
                #wpbody { margin-top: -34px !important; }
            </style>';
        });
    }
}
add_action('after_setup_theme', 'disable_admin_bar_with_css_based_on_role_hierarchy');


function remove_dashboard_menu_for_low_roles() {

    $user_caps = get_logged_in_user_capabilities(); // Get user capabilities


    if (!empty($user_caps['hide_post_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('edit.php'); // Remove the Posts menu
        });
    }
    if (!empty($user_caps['hide_settings_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('options-general.php'); // Remove the Posts menu
        });
    }
    if (!empty($user_caps['hide_comments_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('edit-comments.php'); // Remove the Posts menu
        });
    }
    if (!empty($user_caps['hide_dashboard_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('index.php'); // Remove the Posts menu
        });
    }

    if (!empty($user_caps['hide_tools_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('tools.php'); // Remove the Posts menu
        });
    }

    if (!empty($user_caps['hide_profile_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('profile.php'); // Remove the Posts menu
        });
    }

    if (!empty($user_caps['hide_templates_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('edit.php?post_type=elementor_library'); // Remove the Posts menu
        });
    }

    if (!empty($user_caps['hide_settings_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('options-general.php'); // Remove the Posts menu
        });
    }

    if (!empty($user_caps['hide_acf_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('edit.php?post_type=acf-field-group'); // Remove the Posts menu
        });
    }

    if (!empty($user_caps['hide_pages_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page('edit.php?post_type=page'); // Remove the Posts menu
        });
    }
    if (!empty($user_caps['hide_collapse_menu'])) { // Check if the user capability exists and is true
        add_action('admin_menu', function() {
            remove_menu_page(''); // Remove the Posts menu
        });
    }
}

//add_action('after_setup_theme', 'remove_dashboard_menu_for_low_roles');


function redirect_from_admin_for_low_roles() {
    $user_id = get_current_user_id(); // Get the current user ID

    // Check if the user's role position satisfies the condition
    if (is_user_logged_in() && check_role_hierarchy_position($user_id, 50, '<')) {
        // Ensure the user is in the admin area and not accessing Ajax or REST API

        if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX) && !wp_doing_rest()) {
            // Get the current admin page
            $current_page = $_SERVER['REQUEST_URI'] ?? '';

            // Redirect only if the current page is exactly `/wp-admin/`
            if (trim(parse_url($current_page, PHP_URL_PATH), '/') === 'wp-admin') {
                wp_redirect(home_url()); // Redirect to the homepage
                exit; // Stop further execution
            }
        }
    }
}
add_action('admin_init', 'redirect_from_admin_for_low_roles');



// File: functions.php

add_action('admin_init', 'redirect_no_admin_capability_users');

/**
 * Redirect users with 'no_admin' capability away from wp-admin.
 */
function redirect_no_admin_capability_users() {
    // Check if the user is logged in, is trying to access the admin area, and has the 'no_admin' capability.
    if (is_user_logged_in() && is_admin() && current_user_can('no_admin')) {
        wp_redirect(home_url('/')); // Redirect to the homepage.
        exit; // Stop further script execution.
    }


}


// File: functions.php

add_action('admin_init', 'restrict_dashboard_access');

/**
 * Restrict access to the WordPress dashboard page for non-administrators.
 */
function restrict_dashboard_access() {
    // Check if the user is logged in and is in the admin area.
    $user_id = get_current_user_id();
    if (is_user_logged_in() && is_admin()) {
        // Check the user's role hierarchy position.
        if (!check_role_hierarchy_position($user_id, 50, '>')) {
            // Redirect non-eligible users to the homepage or a custom "No Access" page.
            wp_redirect(home_url('/')); // Custom "No Access" page.
            exit;
        }
    }
}



function add_custom_header_above_admin_menu() {
    // Get the site logo URL
    $custom_logo_id = get_theme_mod('custom_logo'); // Retrieve the logo ID from theme settings
    $logo_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'medium') : ''; // Get the logo URL
    $default_logo_url = '/wp-content/plugins/elementor-pro/assets/images/logo-placeholder.png'; // Fallback default logo if no custom logo is set

    // Use the custom logo URL or fallback to default
    $logo_src = $logo_url ? $logo_url : $default_logo_url;

    echo '<div id="custom-admin-menu-header" style="
        background: none;
        color: white;
        padding: 10px;
        font-size: 16px;
        text-align: center;
        font-weight: bold;
        position: fixed;
        top: 0;
        left: 0;
        width: 140px; /* Match admin menu width */
        z-index: 10000;">
        <a href="/"><img width="100" src="' . esc_url($logo_src) . '"></a>
    </div>';
    echo '<style>
        #adminmenu { margin-top: 144px !important; } /* Push menu down by header height */
    </style>';
}
add_action('in_admin_header', 'add_custom_header_above_admin_menu', 0); // Add at the very top



function hide_collapse_main_menu_button_script() {
    wp_enqueue_script(
        'hide-collapse-button',
        get_stylesheet_directory_uri() . '/fa-members/admin/js/admin.js',
        array('jquery'),
        '1.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'hide_collapse_main_menu_button_script');

add_shortcode('dashboard_button', 'dashboard_button_shortcode');

/**
 * Shortcode to display a button linking to the WordPress dashboard.
 * The button is shown only if the user's role hierarchy position is greater than 50.
 *
 * @return string The button HTML or an empty string if the user is not eligible.
 */
function dashboard_button_shortcode($atts) {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
        return ''; // Do not show the button to logged-out users
    }

    // Get the current user's ID
    $user_id = get_current_user_id();

    // Check if the user has the required role hierarchy
    if (!check_role_hierarchy_position($user_id, 50, '>')) {
        return ''; // Do not show the button if the user does not meet the criteria
    }

    // Generate the button HTML
    return '<a href="' . esc_url(admin_url()) . '" class="dashboard-button" style="display: inline-block; padding: 10px 20px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px;">Admin Dashboard</a>';
}


// 1. Add a Custom Meta Field for Role Hierarchy in Menu Items
function add_menu_item_role_hierarchy_field($item_id, $item, $depth, $args) {
    // Retrieve the saved role hierarchy position for the menu item
    $role_hierarchy = get_post_meta($item_id, '_menu_item_role_hierarchy', true);
    ?>
    <p class="description description-wide">
        <label for="edit-menu-item-role-hierarchy-<?php echo $item_id; ?>">
            <?php _e('Role Hierarchy Required (Min Position)', 'text-domain'); ?><br>
            <input type="number" id="edit-menu-item-role-hierarchy-<?php echo $item_id; ?>" class="widefat edit-menu-item-role-hierarchy"
                   name="menu-item-role-hierarchy[<?php echo $item_id; ?>]" value="<?php echo esc_attr($role_hierarchy); ?>" placeholder="50" />
        </label>
    </p>
    <?php
}
add_action('wp_nav_menu_item_custom_fields', 'add_menu_item_role_hierarchy_field', 10, 4);

// 2. Save the Custom Role Hierarchy Meta Field
function save_menu_item_role_hierarchy_field($menu_id, $menu_item_db_id) {
    if (isset($_POST['menu-item-role-hierarchy'][$menu_item_db_id])) {
        $role_hierarchy = sanitize_text_field($_POST['menu-item-role-hierarchy'][$menu_item_db_id]);
        update_post_meta($menu_item_db_id, '_menu_item_role_hierarchy', $role_hierarchy);
    } else {
        delete_post_meta($menu_item_db_id, '_menu_item_role_hierarchy');
    }
}
add_action('wp_update_nav_menu_item', 'save_menu_item_role_hierarchy_field', 10, 2);

// 3. Filter Menu Items Based on Role Hierarchy
function filter_nav_menu_items_by_role_hierarchy($items, $args) {
    // Skip filtering for administrators
    if (current_user_can('administrator')) {
        return $items;
    }

    $user_id = get_current_user_id();

    foreach ($items as $key => $item) {
        // Retrieve the required role hierarchy for the menu item
        $required_hierarchy = get_post_meta($item->ID, '_menu_item_role_hierarchy', true);

        // Skip items with no restriction
        if (empty($required_hierarchy)) {
            continue;
        }

        // Check if the user meets the required role hierarchy
        if (!check_role_hierarchy_position($user_id, $required_hierarchy, '>')) {
            unset($items[$key]); // Remove menu item if the user does not meet the requirement
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'filter_nav_menu_items_by_role_hierarchy', 10, 2);

// Remove all dashboard widgets for non-administrators
function remove_dashboard_widgets_for_non_admins() {
    if (!current_user_can('administrator')) { // Check if the user is NOT an administrator
        global $wp_meta_boxes;
        $wp_meta_boxes['dashboard'] = []; // Clear all dashboard widgets
    }
}
add_action('wp_dashboard_setup', 'remove_dashboard_widgets_for_non_admins', 999);

// Add blank dashboard content only on the dashboard page for non-administrators
function add_blank_dashboard_content_for_non_admins() {
    if (!current_user_can('administrator')) { // Check if the user is NOT an administrator
        // Check if the current screen is the dashboard
        $current_screen = get_current_screen();
        if ($current_screen && $current_screen->base === 'dashboard') {
            ?>
            <div class="blank-dashboard-wrapper" style="display: flex; align-items: center; justify-content: center; height: 100vh;">
                <h1 style="font-size: 2rem; color: #0073aa;">Welcome to the Admin Dashboard</h1>
            </div>
            <style>
                .blank-dashboard-wrapper {
                    background: #f1f1f1; /* Light grey background */
                    text-align: center;
                }
                #wpcontent, #wpbody { margin-top: 0 !important; } /* Ensure no extra spacing */

                 .wrap, #screen-meta-links {
                    display: none;
                 }
            </style>
            <?php
        }
    }
}
add_action('admin_notices', 'add_blank_dashboard_content_for_non_admins');

