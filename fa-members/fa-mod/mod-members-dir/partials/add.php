<?php
if (!check_user_capability('crud_users')) {
    // Check if the user is an administrator
    if (!is_admin()) {
        echo 'You do not have permission to access this page.';
        exit;
    }
}

// Continue with the rest of the code

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['first_name'], $_POST['last_name'])) {
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'subscriber';
        $send_notification = isset($_POST['send_notification']) ? true : false;

        $user_id = wp_create_user($username, $password, $email);
        if (!is_wp_error($user_id)) {
            $user = get_user_by('id', $user_id);
            $user->set_role($role);

            // Update first name and last name
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);

            // Send notification email if the checkbox is checked
            if ($send_notification) {
                $subject = __('Welcome to Our Website!', 'textdomain');
                $message = sprintf(
                    __('Hello %s %s,<br><br>Thank you for registering at our website. Here are your login details:<br>Username: %s<br>Password: %s<br>We recommend changing your password after your first login.', 'textdomain'),
                    esc_html($first_name),
                    esc_html($last_name),
                    esc_html($username),
                    esc_html($password)
                );
                $headers = array('Content-Type: text/html; charset=UTF-8');

                wp_mail($email, $subject, $message, $headers);
            }

            $editPage = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?view=edit&id=' . $user_id . '&act=added';
            wp_redirect(home_url($editPage));
            exit;
        } else {
            echo 'Error: ' . $user_id->get_error_message();
        }
    } else {
        echo 'Required fields are missing.';
    }
}
?>

<form method="post" action="?view=add">
    <input type="hidden" name="action" value="create_new_user">
    <label for="first_name">First Name:</label>
    <input type="text" id="first_name" name="first_name" required>
    <br>
    <label for="last_name">Last Name:</label>
    <input type="text" id="last_name" name="last_name" required>
    <br>
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>
    <br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <button type="button" onclick="generatePassword()">Generate Password</button>
    <br>
    <label for="role">Role:</label>
    <select id="role" name="role">
        <?php
        global $wp_roles;
        foreach ($wp_roles->roles as $key => $value) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($value['name']) . '</option>';
        }
        ?>
    </select>
    <br>
    <label for="send_notification">
        <input type="checkbox" id="send_notification" name="send_notification" value="1">
        Send welcome email
    </label>
    <br>
    <input type="submit" value="Create User">
</form>

<script>
    function generatePassword() {
        var length = 12,
            charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{:;?><,./-=",
            password = "";
        for (var i = 0, n = charset.length; i < length; ++i) {
            password += charset.charAt(Math.floor(Math.random() * n));
        }
        document.getElementById("password").value = password;
    }
</script>
