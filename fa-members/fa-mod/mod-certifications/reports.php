<?php
add_action('init', 'certifications_module_init');

function certifications_module_init() {
    add_action('admin_menu', function() {
        global $submenu;
        $parent_slug = 'edit.php?post_type=certification';
        $slug_exists = false;

        if (isset($submenu[$parent_slug])) {
            foreach ($submenu[$parent_slug] as $item) {
                if ($item[2] === 'certification-reports') {
                    $slug_exists = true;
                    break;
                }
            }
        }

        if (!$slug_exists) {
            add_submenu_page(
                $parent_slug,
                'Reports',
                'Reports',
                'manage_options',
                'certification-reports',
                'render_certification_reports_page'
            );
        }
    });

    add_action('admin_enqueue_scripts', function($hook) {
        if ($hook !== 'certification_page_certification-reports') return;

        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', ['jquery'], null, true);
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        wp_enqueue_script('datatables-buttons-js', 'https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js', ['datatables-js'], null, true);
        wp_enqueue_script('datatables-jszip-js', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js', [], null, true);
        wp_enqueue_script('datatables-pdfmake-js', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js', [], null, true);
        wp_enqueue_script('datatables-vfsfonts-js', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js', [], null, true);
        wp_enqueue_script('datatables-buttons-html5-js', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js', ['datatables-buttons-js'], null, true);
        wp_enqueue_style('datatables-buttons-css', 'https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css');
    });
}

function render_certification_reports_page() {
    $expiring_filter = isset($_GET['expiring']) ? sanitize_text_field($_GET['expiring']) : 'all';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    $today = date('Y-m-d');
    $range_end = '';
    $range_start = $today;

    if ($expiring_filter === '15') {
        $range_end = date('Y-m-d', strtotime('+15 days'));
    } elseif ($expiring_filter === '30') {
        $range_end = date('Y-m-d', strtotime('+30 days'));
    } elseif ($expiring_filter === 'expired') {
        $range_end = $today;
    } elseif ($expiring_filter === 'custom' && $start_date && $end_date) {
        $range_start = $start_date;
        $range_end = $end_date;
    }

    ?>
    <div class="wrap">
        <h1>Certification Reports</h1>
        <form method="get" style="margin-bottom:20px;">
            <input type="hidden" name="post_type" value="certification">
            <input type="hidden" name="page" value="certification-reports">
            <label>Expiring:
                <select name="expiring" id="expiring-filter">
                    <option value="all">All</option>
                    <option value="15" <?= $expiring_filter === '15' ? 'selected' : '' ?>>Next 15 Days</option>
                    <option value="30" <?= $expiring_filter === '30' ? 'selected' : '' ?>>Next 30 Days</option>
                    <option value="expired" <?= $expiring_filter === 'expired' ? 'selected' : '' ?>>All Expired</option>
                    <option value="custom" <?= $expiring_filter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </label>
            <span id="custom-range-fields" style="<?= $expiring_filter === 'custom' ? '' : 'display:none;' ?>">
                <label>From: <input type="date" name="start_date" value="<?= esc_attr($start_date) ?>"></label>
                <label>To: <input type="date" name="end_date" value="<?= esc_attr($end_date) ?>"></label>
            </span>
            <input type="submit" class="button button-primary" value="Search">
        </form>

        <table id="certifications-report-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Certification</th>
                    <th>Certification Type</th>
                    <th>Expiration Date</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $users = get_users();
            foreach ($users as $user) {
                $certs = get_user_meta($user->ID, 'user_certifications', true);
                if (!empty($certs) && is_array($certs)) {
                    foreach ($certs as $cert_item) {
                        $cert_post = get_post($cert_item['cert_id']);
                        if (!$cert_post) continue;

                        $cert_type_terms = wp_get_post_terms($cert_post->ID, 'certifications-type');
                        if (!is_wp_error($cert_type_terms) && !empty($cert_type_terms)) {
                            $cert_type_names = wp_list_pluck($cert_type_terms, 'name');
                        } else {
                            $cert_type_names = ['No Type Assigned'];
                        }

                        $expire_date = $cert_item['expire_date'] ?? '';

                        $show_row = true;
                        if (!empty($range_end) && $expire_date) {
                            if ($expiring_filter === 'expired') {
                                $show_row = ($expire_date < $today);
                            } elseif ($expiring_filter === 'custom') {
                                $show_row = ($expire_date >= $range_start && $expire_date <= $range_end);
                            } else {
                                $show_row = ($expire_date >= $today && $expire_date <= $range_end);
                            }
                        }

                        if ($show_row) {
                            $row_class = ($expire_date < $today) ? 'style="background-color:#ffe5e5;"' : '';

                            if (current_user_can('administrator')) {
                                $member_link = get_edit_user_link($user->ID);
                            } else {
                                $member_link = home_url('/members/?view=view&id=' . $user->ID);
                            }

                            echo "<tr {$row_class}>";
                            echo '<td><a href="' . esc_url($member_link) . '">' . esc_html($user->display_name) . '</a></td>';
                            echo '<td>' . esc_html($cert_post->post_title) . '</td>';
                            echo '<td>' . esc_html(implode(', ', $cert_type_names)) . '</td>';
                            echo '<td>' . esc_html($expire_date) . '</td>';
                            echo '</tr>';
                        }
                    }
                }
            }
            ?>
            </tbody>
        </table>

        <script>
        jQuery(document).ready(function($) {
            $('#certifications-report-table').DataTable({
                paging: false,
                searching: false,
                info: false,
                ordering: true,
                dom: 'Bfrtip',
                buttons: [
                    'csvHtml5',
                    'pdfHtml5'
                ]
            });

            $('#expiring-filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-range-fields').show();
                } else {
                    $('#custom-range-fields').hide();
                }
            }).trigger('change');
        });
        </script>
    </div>
    <?php
}
