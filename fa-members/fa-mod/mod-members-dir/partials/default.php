<?php
$users = get_users([
    'role__not_in' => ['Administrator'], // Exclude administrators
]);

$tblHead = '';
$selectCol = '';
$userData = '';
$group_keys =  $atts['acf-groups'];

foreach ($users as $user) {
    $first_name = get_user_meta($user->ID, 'first_name', true);
    $last_name = get_user_meta($user->ID, 'last_name', true);
    $viewLink = '?view=view&id=' . esc_html($user->ID);

    $userData .= '<tr  style="cursor: pointer;"  onclick="window.location.href=\'' . $viewLink . '\'">';
    $userData.= '<td>' . esc_html($last_name) . ', '. esc_html($first_name) . '</td>';
    $userData.= '<td>' . esc_html($user->user_email) . '</td>';

    // Usage
    $user_id = $user->ID;  // or a specific user ID
    $user_data = get_acf_user_data($user_id, $group_keys);
    $colCnt = 2;
// Display the data
    foreach ($user_data as $group_key => $group) {
       // echo "<h3>{$group['group_label']}</h3>";
       
       foreach ($group['fields'] as $field_name => $data) {

              $field_id = esc_html($data['field_id']); // Fetch the ACF field ID
                

                if (get_acf_fields_permissions_check($field_id, 'read')) {

                    if (is_array($data['value'])) {
                        // Join array elements into a string separated by commas
                        $dataValue = implode(', ', $data['value']);
                    } else {
                        // If the value is not an array, just display it
                        $dataValue = $data['value'];
                    }

                    // Display field data along with its ID (optional: echo or use elsewhere)
                    $userData .= '<td>' . esc_html($dataValue) . '</td>';

                    // Now you can use $field_id as needed
                    $label = esc_html($data['label']);  // Sanitize the label first
                    if (strpos($tblHead, "<th>$label</th>") === false) {
                        $tblHead .= "<th>$label</th>";

                        // Example: Store the field ID as part of a column option (or use it elsewhere)
                        $selectCol .= '<option value="' . $colCnt++ .'">' . $label .  '</option>';
                    }
                }

                
            }

    }

    //$userData.= '<td><a href="?view=view&id=' . esc_html($user->ID) . '">View</a> <a href="?view=edit&id=' . esc_html($user->ID) . '">Edit</a></td>';


}
$output = '<div>';
$output .= '<label for="columnVisibility">Select Columns:</label>';
$output .= ' <select id="columnVisibility" multiple style="width: 200px;">';
$output .= $selectCol;
$output .= '</select>';
$output .= '</div>';
$output .= '<table id="members-dt" class="display" style="width:100%">';
$output .= '<thead><tr><th>Name</th><th>Email</th>' . $tblHead .'</tr></thead><tbody>';
$output .= $userData ;
$output .= '</tr>';
$output .= '</tbody></table>';


ob_start();
include('assets/tables.js.php');
$dataJs = ob_get_clean();

$output .= '<script>';
$output .= $dataJs;
$output .= '</script>';

echo $output;
