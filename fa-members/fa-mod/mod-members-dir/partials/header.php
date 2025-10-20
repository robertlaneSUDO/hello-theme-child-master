<?php

$post_id = get_queried_object_id();
$page_slug = get_post_field('post_name', $post_id);

$current_user_id = get_current_user_id(); // Get the current logged-in user's ID

?>
<div id="members-dir-wrapper">
    <div class="members-bc">
        <a href="<?php echo '/' . $page_slug; ?>">Members Directory</a>
    </div>
    <div id="members-dir">
        <?php
        // Check if the user has the 'crud_users' capability or is an administrator
        if (check_user_capability('crud_users') || current_user_can('administrator')) {
            if ($view == 'default') { ?>
                <div><a href="?view=add" class="members-btn">ADD USER</a></div>
            <?php }
            if ($view == 'view') { ?>
                <div>
                    <a href="?view=edit&id=<?php echo $userID; ?>" class="members-btn">EDIT USER</a>
                    <a href="?view=delete&id=<?php echo $userID; ?>" class="members-btn">DELETE USER</a>
                </div>
            <?php }
        }

        // Exception: Allow users to edit their own profile
        if ($view == 'view' && $userID == $current_user_id) { ?>
            <div>
                <a href="?view=edit&id=<?php echo $userID; ?>" class="members-btn">EDIT YOUR PROFILE</a>
            </div>
        <?php } ?>
    </div>
</div>
