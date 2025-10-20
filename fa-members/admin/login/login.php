<?php
// Enqueue jQuery
function acf_fieldlogin_scripts() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'acf_fieldlogin_scripts');

// Handle AJAX login
function ajax_login() {
    check_ajax_referer('ajax-login-nonce', 'security');

    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);

    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        echo json_encode(array('loggedin' => false, 'message' => __('Wrong username or password.')));
    } else {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        echo json_encode(array(
            'loggedin' => true,
            'message' => __('Login successful, redirecting...'),
            'redirect_url' => home_url('/members-area/dashboard')
        ));
    }

    wp_die();
}
add_action('wp_ajax_nopriv_ajax_login', 'ajax_login');

// Handle AJAX password reset
function ajax_reset_password() {
    check_ajax_referer('ajax-reset-nonce', 'security');

    $email = sanitize_email($_POST['email']);
    $user = get_user_by('email', $email);

    if (!$user) {
        echo json_encode(array('reset' => false, 'message' => __('Email address not found.')));
    } else {
        $reset_key = get_password_reset_key($user);
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login));
        wp_mail($user->user_email, 'Password Reset', 'Click here to reset your password: ' . $reset_url);

        echo json_encode(array('reset' => true, 'message' => __('Password reset email sent.')));
    }

    wp_die();
}
add_action('wp_ajax_nopriv_ajax_reset_password', 'ajax_reset_password');

// Shortcode for combined login and reset password form
function fa_members_login_form_shortcode() {
    ob_start();
    ?>
    <div id="login-form-container">
        <form id="ajax-login-form" action="login" method="post">
            <p class="status"></p>
            <label for="username">Username</label>
            <input id="username" type="text" name="username">
            <label for="password">Password</label>
            <input id="password" type="password" name="password">
            <input class="button" type="submit" value="Login">
            <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
            <p><a href="#" id="show-reset-form">Forgot your password?</a></p>
        </form>

        <form id="ajax-reset-password-form" action="reset_password" method="post" style="display: none;">
            <p class="status"></p>
            <label for="email">Email</label>
            <input id="email" type="email" name="email">
            <input class="button" type="submit" value="Reset Password">
            <?php wp_nonce_field('ajax-reset-nonce', 'security'); ?>
            <p><a href="#" id="show-login-form">Back to login</a></p>
        </form>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show reset form
            $('#show-reset-form').on('click', function(e) {
                e.preventDefault();
                $('#ajax-login-form').hide();
                $('#ajax-reset-password-form').show();
            });

            // Show login form
            $('#show-login-form').on('click', function(e) {
                e.preventDefault();
                $('#ajax-reset-password-form').hide();
                $('#ajax-login-form').show();
            });

            // Perform AJAX login on form submit
            $('#ajax-login-form').on('submit', function(e) {
                e.preventDefault();

                var username = $('#username').val();
                var password = $('#password').val();
                var security = $('#ajax-login-form #security').val();

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        'action': 'ajax_login',
                        'username': username,
                        'password': password,
                        'security': security
                    },
                    success: function(response) {
                        $('.status').text(response.message);
                        if (response.loggedin == true) {
                            document.location.href = response.redirect_url;
                        }
                    }
                });
            });

            // Perform AJAX password reset on form submit
            $('#ajax-reset-password-form').on('submit', function(e) {
                e.preventDefault();

                var email = $('#email').val();
                var security = $('#ajax-reset-password-form #security').val();

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        'action': 'ajax_reset_password',
                        'email': email,
                        'security': security
                    },
                    success: function(response) {
                        $('.status').text(response.message);
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('members_login', 'fa_members_login_form_shortcode');

// Redirect logic for members area and child pages
function restrict_members_area() {
    if (is_page()) {
        global $post;

        // Define the parent page slug
        $parent_slug = 'members';

        // Check if user is logged in and on /members-area page
        if (is_page($parent_slug) && is_user_logged_in()) {
            wp_redirect(home_url('/' . $parent_slug . '/dashboard'));
            exit;
        }

        // Get the ancestors of the current page
        $ancestors = get_post_ancestors($post);

        if ($ancestors) {
            $top_ancestor_id = end($ancestors);
            $top_ancestor_slug = get_post($top_ancestor_id)->post_name;

            // Check if the top ancestor is 'members-area'
            if ($top_ancestor_slug === $parent_slug && !is_user_logged_in()) {
                wp_redirect(home_url('/' . $parent_slug));
                exit;
            }
        }
    }
}
add_action('template_redirect', 'restrict_members_area');

function add_member_role() {
    add_role(
        'member', // System name of the role.
        'Member', // Display name for the role.
        array(
            'read' => true, // Allows a user to read content.
            // Additional capabilities can be added here.
        )
    );
}

add_action('init', 'add_member_role');

// Disable Gutenberg on the back end.
add_filter( 'use_block_editor_for_post', '__return_false' );

// Disable Gutenberg for widgets.
add_filter( 'use_widgets_block_editor', '__return_false' );

add_action( 'wp_enqueue_scripts', function() {
    // Remove CSS on the front end.
    wp_dequeue_style( 'wp-block-library' );

    // Remove Gutenberg theme.
    wp_dequeue_style( 'wp-block-library-theme' );

    // Remove inline global CSS on the front end.
    wp_dequeue_style( 'global-styles' );

    // Remove classic-themes CSS for backwards compatibility for button blocks.
    wp_dequeue_style( 'classic-theme-styles' );
}, 20 );