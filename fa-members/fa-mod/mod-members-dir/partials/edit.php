<?php
/**
 * @var string $view
 * @var string $userID
 *  @var string $atts
 *   @var string $action
 */

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $user_data = [];
        $acf_data = [];

        $user_data['ID'] = $userID;

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'acf') === 0) {
                $acf_data[$key] = $value;
            } else {
                $user_data[$key] = $value;
            }
        }

        wp_update_user($user_data);

        foreach ($acf_data as $field_key => $value) {
            $field_key = str_replace('acf_', '', $field_key); // Strip prefix to get the field key
            update_field($field_key, $value, 'user_' . $userID);
        }

    }
$user_info = get_userdata($userID);
$all_roles = wp_roles()->roles;
$available_roles = array_keys($all_roles);

// Usage
$user_id = $userID;  // or a specific user ID
$group_keys =  $atts['acf-groups'];
$user_data = get_acf_user_data($user_id, $group_keys);

// Display the data
if( $action == 'added'){
    echo '<div>User Added!</div>';
}
?>
<form method="post" id="mod-members-edit" action="?view=edit&id=<?php echo $userID; ?>"">
<label>First Name:<input type="text" name="first_name" value="<?php echo esc_attr($user_info->first_name); ?>" /></label>
<label>Last Name:<input type="text" name="last_name" value="<?php echo esc_attr($user_info->last_name); ?>" /></label>
<label>Email:<input type="email" name="user_email" value="<?php echo esc_attr($user_info->user_email); ?>" /></label>
<?php

if (check_user_capability('crud_users') || current_user_can('administrator')) {
    // If the user has permission to edit roles
    $user_roles = $user_info->roles;

    echo '<label>User Roles:';
    echo '<select name="roles[]" multiple="multiple">';
    foreach ($available_roles as $role) {
        // Check if the user has this role
        $selected = in_array($role, $user_roles) ? ' selected="selected"' : '';
        echo "<option value='{$role}'{$selected}>{$all_roles[$role]['name']}</option>";
    }
    echo '</select>';
    echo '</label>';

} else {
    // If the user does not have permission to edit roles, display roles in a non-editable format
    $user_roles = $user_info->roles;

    echo '<label>User Roles:</label>';
    echo '<ul>';
    foreach ($user_roles as $role) {
        echo '<li>' . esc_html($all_roles[$role]['name']) . '</li>';
    }
    echo '</ul>';

}

// Generate fields for ACF data
foreach ($user_data as $group_key => $group) {

    $group_output = '';

    foreach ($group['fields'] as $field_key => $data) {

        $field_id = esc_html($data['field_id']); // Fetch the ACF field ID        

        if (get_acf_fields_permissions_check($field_id, 'update')) {

            // Buffer each field's output
            ob_start();

            echo "<label>{$data['label']}";

            $post_data = get_acf_post($field_key);
            $acfKey = $post_data['post_name'];

            switch ($data['type']) {
                case 'text':
                    echo "<input type='text' name='acf_{$acfKey}' value='{$data['value']}' />";
                    break;
                case 'textarea':
                case 'select':
                    $selected = '';
                    $acfData = unserialize($post_data['post_content']);

                    if ($acfData['multiple']) {
                        $multiSelect = ' multiple="multiple"';
                        $acfKey = $acfKey . '[]';
                    } else {
                        $multiSelect = '';
                    }

                    echo "<select name='acf_{$acfKey}' {$multiSelect}>";
                    foreach ($acfData['choices'] as $option_key => $option_value) {

                        if (is_array($data['value'])) {
                            $selected = in_array($option_key, $data['value']) ? 'selected' : '';
                        } else {
                            $selected = ($option_value === $data['value']) ? 'selected' : '';
                        }

                        echo "<option value='{$option_key}' {$selected}>{$option_value}</option>";
                    }
                    echo "</select>";
                    break;
                case 'checkbox':
                    echo "<input type='checkbox' name='acf_{$acfKey}' value='1' " . (!empty($data['value']) ? 'checked' : '') . " />";
                    break;
                case 'date_picker':
                    if ($data['value']) {
                        $input_value = htmlspecialchars($data['value']);
                        $date_object = DateTime::createFromFormat('m/d/Y', $input_value);
                        $iso_date = $date_object->format('Y-m-d');
                    } else {
                        $iso_date = '';
                    }
                    echo "<input type='date' name='acf_{$acfKey}' value='{$iso_date}' />";
                    break;
            }
            echo "</label>";

            $group_output .= ob_get_clean();
        }
    }

    // Only display the group label and its fields if output exists
    if (!empty($group_output)) {
        echo "<h3>{$group['group_label']}</h3>";
        echo $group_output;
    }
}
?>
<input type="submit" value="Update Information" />
</form>
<?php
// 👇 Render the Certifications Shortcode below the form
echo do_shortcode('[user_certifications user_id="' . intval($userID) . '"]');
?>
