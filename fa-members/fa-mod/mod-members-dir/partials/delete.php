<?php
if (!check_user_capability('crud_users')) {
    // Check if the user is an administrator
    if (!is_admin()) {
        echo 'You do not have permission to access this page.';
        exit;
    }
}

if (isset($_GET['view']) && $_GET['view'] === 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $current_user_id = get_current_user_id();
    $confirm = isset($_GET['confirm']) ? $_GET['confirm'] : false;

    if ($user_id === $current_user_id) {
        echo '<p>You cannot delete your own account.</p>';
        exit;
    }

    if ($confirm) {
        // Delete user and their metadata
        require_once(ABSPATH.'wp-admin/includes/user.php');
        wp_delete_user($user_id);

        echo '<p>User has been successfully deleted.</p>';
        exit;
    } else {
        $user = get_userdata($user_id);

        if ($user) {
            $first_name = get_user_meta($user_id, 'first_name', true);
            $last_name = get_user_meta($user_id, 'last_name', true);
            $username = $user->user_login;
        } else {
            echo '<p>User not found.</p>';
            exit;
        }
    }
} else {
    echo '<p>No user specified.</p>';
    exit;
}
?>

<?php if (!$confirm): ?>
    <h2>Delete User</h2>
    <p>Are you sure you want to delete the following user?</p>
    <ul>
        <li><strong>Username:</strong> <?php echo esc_html($username); ?></li>
        <li><strong>First Name:</strong> <?php echo esc_html($first_name); ?></li>
        <li><strong>Last Name:</strong> <?php echo esc_html($last_name); ?></li>
    </ul>
    <a href="?view=delete&id=<?php echo esc_attr($user_id); ?>&confirm=1">Confirm</a>
<?php endif; ?>
