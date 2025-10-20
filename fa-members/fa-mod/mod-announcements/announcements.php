<?php

// Enqueue ACF notifications JS in admin
function announcements_enqueue_acf_notifications_script($hook) {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'announcements') {
        wp_enqueue_script(
            'acf-notifications-script',
            get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-announcements/js/acf-notifications.js',
            ['jquery'],
            '1.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'announcements_enqueue_acf_notifications_script');

// Enqueue announcements CSS on front end
function announcements_enqueue_styles_for_slugs_and_post_types() {
    $slug = get_post_field('post_name', get_the_ID());
    if ($slug === 'announcements') {
        wp_enqueue_style(
            'announcements-style',
            get_stylesheet_directory_uri() . '/fa-members/fa-mod/mod-announcements/css/announcements.css',
            [],
            '1.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'announcements_enqueue_styles_for_slugs_and_post_types');

// Add ACF fields
function announcements_add_notifications_field_to_acf() {
    if (function_exists('acf_add_local_field_group')) {
        global $wp_roles;
        $roles = $wp_roles->roles;

        $role_choices = ['none' => 'None', 'all' => 'All'];
        foreach ($roles as $role_key => $role_data) {
            if ($role_key !== 'administrator') {
                $role_choices[$role_key] = $role_data['name'];
            }
        }

        acf_add_local_field_group([
            'key' => 'group_notifications_settings',
            'title' => 'Notifications Settings',
            'fields' => [
                [
                    'key' => 'field_notifications_roles',
                    'label' => 'Notifications',
                    'name' => 'notifications_roles',
                    'type' => 'select',
                    'choices' => $role_choices,
                    'default_value' => [],
                    'ui' => 1,
                    'multiple' => 1,
                    'instructions' => 'Select roles to notify. Choose "None" for no notifications or "All" for all roles.',
                ],
                [
                    'key' => 'field_scheduled_datetime',
                    'label' => 'Scheduled Date & Time',
                    'name' => 'scheduled_datetime',
                    'type' => 'date_time_picker',
                    'instructions' => 'Leave empty to send immediately. Only future times are allowed.',
                    'display_format' => 'F j, Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                    'picker' => 'datetime',
                ],
                [
                    'key' => 'field_email_message',
                    'label' => 'Email Message',
                    'name' => 'email_message',
                    'type' => 'textarea',
                    'instructions' => 'Use placeholders: %member%, %title%, %date%, %excerpt%, %image%, %link%',
                    'default_value' => "Dear %member%,\n\nNew Announcement:\n%title%\n%date%\n\n%excerpt%\n\n%image%\n\nRead more:\n%link%\n\nSite Admin",
                ],
                [
                    'key' => 'field_email_sent_timestamp',
                    'label' => 'Email Sent Timestamp',
                    'name' => 'email_sent_timestamp',
                    'type' => 'text',
                    'readonly' => 1,
                    'wrapper' => ['class' => 'acf-email-sent-timestamp'],
                ],
            ],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'announcements']]],
        ]);
    }
}
add_action('acf/init', 'announcements_add_notifications_field_to_acf');

// Lock out scheduling after sent
add_filter('acf/load_field/name=scheduled_datetime', function($field) {
    global $post;
    if (!$post) return $field;

    $sent = get_field('email_sent_timestamp', $post->ID);
    if ($sent) {
        $field['disabled'] = 1;
        $field['instructions'] = 'This announcement has already been sent on ' . $sent;
    } else {
        $field['min'] = current_time('Y-m-d\TH:i');
    }
    return $field;
});

// Handle email logic on save
add_action('acf/save_post', function($post_id) {
    if (get_post_type($post_id) !== 'announcements') return;

    $roles = get_field('notifications_roles', $post_id);
    $scheduled_datetime = get_field('scheduled_datetime', $post_id);
    $sent_timestamp = get_field('email_sent_timestamp', $post_id);

    if (!$roles || in_array('none', $roles) || $sent_timestamp) return;

    if (in_array('all', $roles)) {
        global $wp_roles;
        $roles = array_diff(array_keys($wp_roles->roles), ['administrator']);
    }

    $now = current_time('mysql');

    if (!$scheduled_datetime) {
        announcements_send_announcement_email($post_id, $roles);
        update_field('email_sent_timestamp', $now, $post_id);
    } else {
        $ts = strtotime($scheduled_datetime);
        if ($ts > time()) {
            wp_schedule_single_event($ts, 'announcements_send_scheduled_announcement', [$post_id, $roles]);
            update_field('email_sent_timestamp', $scheduled_datetime, $post_id);
        }
    }
}, 30);

// Email function with placeholders
function announcements_send_announcement_email($post_id, $roles) {
    $post = get_post($post_id);
    $subject = 'New Announcement: ' . $post->post_title;
    $raw_message = get_field('email_message', $post_id);

    $excerpt = has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($post->post_content, 55);
    $image = get_the_post_thumbnail_url($post_id, 'medium');
    $link = get_permalink($post_id);

    $query = new WP_User_Query(['role__in' => $roles, 'fields' => ['ID', 'user_email', 'display_name']]);
    $users = $query->get_results();

    foreach ($users as $user) {
        $message = str_replace(
            ['%member%', '%title%', '%date%', '%excerpt%', '%image%', '%link%'],
            [
                $user->display_name,
                $post->post_title,
                get_the_date('', $post_id),
                $excerpt,
                $image ? "<img src='$image' alt=''>" : '',
                "<a href='$link'>$link</a>"
            ],
            $raw_message
        );

        wp_mail($user->user_email, $subject, nl2br($message), ['Content-Type: text/html; charset=UTF-8']);
    }
}

// Scheduled hook
add_action('announcements_send_scheduled_announcement', function($post_id, $roles) {
    announcements_send_announcement_email($post_id, $roles);
}, 10, 2);
