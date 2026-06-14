<?php 

/**
 * Customizer
 * 
 * @package WordPress
 * @subpackage deepora
 * @since deepora 1.0
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function deepora_customize_register( $wp_customize ) {
	$wp_customize->add_section( new Deepora_Upsell_Section($wp_customize,'upsell_section',array(
		'title'            => __( 'Deepora Pro', 'deepora' ),
		'button_text'      => __( 'Upgrade Pro', 'deepora' ),
		'url'              => esc_url( DEEPORA_BUY_NOW ),
		'priority'         => 0,
	)));
}
add_action( 'customize_register', 'deepora_customize_register' );

/**
 * Enqueue script for custom customize control.
 */
function deepora_custom_control_scripts() {
	wp_enqueue_script( 'deepora-custom-controls-js', get_template_directory_uri() . '/assets/js/custom-controls.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), '1.0', true );
}
add_action( 'customize_controls_enqueue_scripts', 'deepora_custom_control_scripts' );
?>