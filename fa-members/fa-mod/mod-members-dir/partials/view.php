<?php

/**
 * @var string $view
 * @var string $userID
 * @var string $atts
 */

$user_info = get_userdata($userID);

echo '<div class="mod-edit-wrapper">';
echo '<p><strong>First Name:</strong>  ' . esc_html($user_info->first_name) . '</p>';
echo '<p><strong>Last Name:</strong>  ' . esc_html($user_info->last_name) . '</p>';
echo '<p><strong>Email:</strong>  ' . esc_html($user_info->user_email) . '</p>';
echo '<p><strong>Roles:</strong>  ' . implode(', ', array_map('esc_html', $user_info->roles)) . '</p>';

$user_id = $userID;
$group_keys = $atts['acf-groups'];
$user_data = get_acf_user_data($user_id, $group_keys);

foreach ($user_data as $group_key => $group) {
    $group_output = '';

    foreach ($group['fields'] as $field_name => $data) {
        $field_id = esc_html($data['field_id']);

        if (get_acf_fields_permissions_check($field_id, 'read')) {
            if (is_array($data['value']) && isset($data['value'][0]['cert_id'])) {
                // Sort certifications by expiration date
                usort($data['value'], function ($a, $b) {
                    return strtotime($a['expire_date']) - strtotime($b['expire_date']);
                });

                $table_id = 'certifications-table-' . uniqid();
                $table_output = '<table id="' . esc_attr($table_id) . '" class="display" style="width:100%; margin-bottom:20px;">
                    <thead>
                        <tr>
                            <th>Certification</th>
                            <th>Expiration Date</th>
                        </tr>
                    </thead>
                    <tbody>';

                foreach ($data['value'] as $cert_item) {
                    $cert = get_post($cert_item['cert_id']);
                    $cert_title = $cert ? esc_html($cert->post_title) : 'Unknown Certification';
                    $expire_date = !empty($cert_item['expire_date']) ? esc_html($cert_item['expire_date']) : 'No date';
                    $table_output .= "<tr><td>{$cert_title}</td><td>{$expire_date}</td></tr>";
                }

                $table_output .= '</tbody></table>';

                // Initialize DataTable with search, paging, and info disabled
                $table_output .= "<script>
                    jQuery(document).ready(function($) {
                        $('#{$table_id}').DataTable({
                            paging: false,
                            searching: false,
                            info: false
                        });
                    });
                </script>";

                $group_output .= $table_output;
            } elseif (is_array($data['value'])) {
                $dataValue = implode(', ', array_map('esc_html', $data['value'])) . "<br>";
                $group_output .= "<p><strong>{$data['label']}:</strong> {$dataValue}</p>";
            } else {
                $dataValue = esc_html($data['value']) . "<br>";
                $group_output .= "<p><strong>{$data['label']}:</strong> {$dataValue}</p>";
            }
        }
    }

    if (!empty($group_output)) {
        echo "<h3>" . esc_html($group['group_label']) . "</h3>";
        echo $group_output;
    }
}

echo '</div>';
