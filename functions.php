<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );



include 'fa-members/fa-members.php';

add_action('wp_footer', function() {
    $included_files = get_included_files();
    $filtered_files = array_filter($included_files, function($file) {
        return strpos($file, 'fa-members') !== false;
    });

    if (!empty($filtered_files)) {
        echo '<pre style="background: #000; color: #0f0; padding: 10px; font-size: 14px;">';
        echo 'Included FA-Members Files:<br>';
        echo implode('<br>', $filtered_files);
        echo '</pre>';
    }
});
