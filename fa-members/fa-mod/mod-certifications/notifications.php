<?php
/**
 * Notifications Module
 */

if (!defined('ABSPATH')) exit;

// Add Notifications submenu
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=certification',
        'Notifications',
        'Notifications',
        'manage_options',
        'certification-notifications',
        'render_certification_notifications_page'
    );
});

// Add weekly schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['weekly'] = ['interval' => 604800, 'display' => __('Once Weekly')];
    return $schedules;
});

// Schedule cron event
if (!wp_next_scheduled('send_certification_notifications_cron')) {
    wp_schedule_event(time(), 'daily', 'send_certification_notifications_cron');
}
add_action('send_certification_notifications_cron', 'send_certification_notifications');

function render_certification_notifications_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('certification_notifications_settings')) {
        $settings = [
            'frequency' => sanitize_text_field($_POST['frequency']),
            'notify_expired' => true,
            'notify_7days' => isset($_POST['notify_7days']),
            'notify_15days' => isset($_POST['notify_15days']),
            'admin_emails' => sanitize_text_field($_POST['admin_emails']),
            'admin_message' => sanitize_textarea_field($_POST['admin_message']),
            'notify_members' => isset($_POST['notify_members']),
            'member_frequency' => sanitize_text_field($_POST['member_frequency']),
            'member_7days' => isset($_POST['member_7days']),
            'member_15days' => isset($_POST['member_15days']),
            'member_message' => sanitize_textarea_field($_POST['member_message']),
        ];
        update_option('certification_notifications_settings', $settings);
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }

    $settings = get_option('certification_notifications_settings', []);

    ?>
    <div class="wrap">
        <h1>Certification Notifications</h1>
        <form method="post">
            <?php wp_nonce_field('certification_notifications_settings'); ?>
            <h2>Admin Notifications</h2>
            <p>
                Frequency:
                <select name="frequency">
                    <option value="daily" <?= ($settings['frequency'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= ($settings['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly (Sunday)</option>
                </select>
            </p>
            <p>
                <input type="checkbox" checked disabled> Expired<br>
                <input type="checkbox" name="notify_7days" <?= !empty($settings['notify_7days']) ? 'checked' : '' ?>> 7 Days<br>
                <input type="checkbox" name="notify_15days" <?= !empty($settings['notify_15days']) ? 'checked' : '' ?>> 15 Days
            </p>
            <p>
                Admin Emails (comma separated):<br>
                <input type="text" name="admin_emails" value="<?= esc_attr($settings['admin_emails'] ?? '') ?>" style="width: 400px;">
            </p>
            <p>
                Admin Message:<br>
                <textarea name="admin_message" style="width:400px; height:100px;"><?= esc_textarea($settings['admin_message'] ?? '') ?></textarea>
            </p>
            <h2>Notify Members</h2>
            <p>
                <input type="checkbox" name="notify_members" id="notify_members" <?= !empty($settings['notify_members']) ? 'checked' : '' ?>> Notify Members
            </p>
            <div id="member_settings" style="<?= !empty($settings['notify_members']) ? '' : 'display:none;' ?>">
                <p>
                    Frequency:
                    <select name="member_frequency">
                        <option value="daily" <?= ($settings['member_frequency'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="weekly" <?= ($settings['member_frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly (Sunday)</option>
                    </select>
                </p>
                <p>
                    <input type="checkbox" checked disabled> Expired<br>
                    <input type="checkbox" name="member_7days" <?= !empty($settings['member_7days']) ? 'checked' : '' ?>> 7 Days<br>
                    <input type="checkbox" name="member_15days" <?= !empty($settings['member_15days']) ? 'checked' : '' ?>> 15 Days
                </p>
                <p>
                    Member Message:<br>
                    <textarea name="member_message" style="width:400px; height:100px;"><?= esc_textarea($settings['member_message'] ?? '') ?></textarea>
                </p>
            </div>
            <p>
                <input type="submit" class="button button-primary" value="Save Settings">
            </p>
        </form>
        <script>
            document.getElementById('notify_members').addEventListener('change', function() {
                document.getElementById('member_settings').style.display = this.checked ? '' : 'none';
            });
        </script>
    </div>
    <?php
}

function send_certification_notifications() {
    $settings = get_option('certification_notifications_settings', []);
    $today = date('Y-m-d');
    $soon_7 = date('Y-m-d', strtotime('+7 days'));
    $soon_15 = date('Y-m-d', strtotime('+15 days'));

    $users = get_users();
    $admin_report = [];
    $user_reports = [];

    foreach ($users as $user) {
        $certs = get_user_meta($user->ID, 'user_certifications', true);
        $user_lines = [];

        if (!empty($certs) && is_array($certs)) {
            foreach ($certs as $cert_item) {
                $expire_date = $cert_item['expire_date'] ?? '';
                if (!$expire_date) continue;

                $cert_name = get_the_title($cert_item['cert_id']);
                $expired = $expire_date < $today;
                $expiring_7 = $expire_date <= $soon_7;
                $expiring_15 = $expire_date <= $soon_15;

                if ($expired || (!empty($settings['notify_7days']) && $expiring_7) || (!empty($settings['notify_15days']) && $expiring_15)) {
                    $user_lines[] = "$cert_name, $expire_date";
                    $admin_report[] = "{$user->display_name}, $cert_name, $expire_date";
                }
            }
        }

        if (!empty($user_lines)) {
            $user_reports[$user->ID] = $user_lines;
        }
    }

    // Send admin email
    if (!empty($admin_report)) {
        $admin_body = "Hello Admin,\n\n" . ($settings['admin_message'] ?? '') . "\n\nThe following certifications are expiring/expired:\n";
        $admin_body .= implode("\n", $admin_report);
        $admin_emails = array_map('trim', explode(',', $settings['admin_emails']));
        wp_mail($admin_emails, 'Certification Notifications', $admin_body);
    }

    // Send user emails
    if (!empty($settings['notify_members'])) {
        foreach ($user_reports as $user_id => $lines) {
            $user = get_userdata($user_id);
            $user_body = "Hello {$user->display_name},\n\n" . ($settings['member_message'] ?? '') . "\n\nYour certifications:\n";
            $user_body .= implode("\n", $lines);
            wp_mail([$user->user_email], 'Certification Notification', $user_body);
        }
    }
}
