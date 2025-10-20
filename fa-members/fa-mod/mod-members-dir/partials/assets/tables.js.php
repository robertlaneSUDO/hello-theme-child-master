jQuery(document).ready(function($) {
    var table = $('#members-dt').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "scrollX": true,
        "columnDefs": [
            { "targets": [0, 1], "visible": true }, // Default visible columns
            { "targets": "_all", "visible": false } // Other columns hidden by default
        ],
        "dom": '<"top"Bfl>rt<"bottom"ip><"clear">', // Layout of table controls
        "buttons": [
            {
                extend: 'csvHtml5',
                text: 'Export CSV',
                exportOptions: {
                    columns: ':visible' // Export only visible columns
                }
            },
            {
                extend: 'print',
                text: 'Print',
                exportOptions: {
                    columns: ':visible' // Print only visible columns
                }
            }
        ]
    });

    // Handle column visibility toggling on option click
    $('#columnVisibility').on('click', 'option', function() {
        var column = table.column($(this).val()); // Get the column based on the option's value

        // Toggle the column visibility based on its current visibility state
        column.visible(!column.visible());  // Invert the visibility
    });
});
