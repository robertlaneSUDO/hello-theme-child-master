jQuery(document).ready(function ($) {
    const notificationsField = $('select[name="acf[field_notifications_roles][]"]');

    // Function to toggle role options based on "None" or "All"
    function toggleRoleOptions() {
        const selectedOptions = notificationsField.val() || [];
        const isNoneOrAllSelected = selectedOptions.includes('none') || selectedOptions.includes('all');

        notificationsField.find('option').each(function () {
            const value = $(this).val();

            if (isNoneOrAllSelected) {
                // Disable all options
                $(this).prop('disabled', true);

                // Keep "None" and "All" enabled if they are already selected
                if (value === 'none' || value === 'all') {
                    $(this).prop('disabled', false);
                }
            } else {
                // Re-enable all options
                $(this).prop('disabled', false);
            }
        });

        // Trigger a change event to update the UI
        notificationsField.trigger('change.select2');
    }

    // Initialize the toggle logic on page load and field change
    toggleRoleOptions();
    notificationsField.on('change', toggleRoleOptions);
});

