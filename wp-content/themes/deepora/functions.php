<?php

require get_template_directory() . '/inc/block-patterns.php';

function deepora_theme_enqueue_styles()
{
    wp_enqueue_style(
        'deepora_theme-main-css',
        get_template_directory_uri() . '/assets/css/main.css',
        array(),
        wp_get_theme()->get('Version'),
        'all'
    );   
}

add_action('wp_enqueue_scripts', 'deepora_theme_enqueue_styles');

function deepora_theme_enqueue_scripts()
{
    wp_enqueue_script(
        'deepora_adbar-js',
        get_template_directory_uri() . '/assets/js/adbar.js',
        array(),
        wp_get_theme()->get('Version'),
        true 
    );

    wp_enqueue_script(
        'deepora-effects-js',
        get_template_directory_uri() . '/assets/js/effects.js',
        array(),
        wp_get_theme()->get('Version'),
        true 
    );

    wp_enqueue_script(
        'deepora-scroll-top-js',
        get_template_directory_uri() . '/assets/js/scroll-top.js',
        array(),
        wp_get_theme()->get('Version'),
        true 
    );

    wp_enqueue_script(
        'deepora-navigation-bar-js',
        get_template_directory_uri() . '/assets/js/navigation-bar.js',
        array(),
        wp_get_theme()->get('Version'),
        true 
    );

    
}

add_action('wp_enqueue_scripts', 'deepora_theme_enqueue_scripts');

require get_template_directory() . '/inc/customizer.php';

if ( class_exists( 'WP_Customize_Section' ) ) {
	class Deepora_Upsell_Section extends WP_Customize_Section {
		public $type = 'deepora-upsell';
		public $button_text = '';
		public $url = '';
		public $background = '';
		public $text_color = '';
		protected function render() {
			$background = ! empty( $this->background ) ? esc_attr( $this->background ) : 'linear-gradient(90deg,rgb(0,0,0) 0%,rgb(0,0,0) 35%,rgb(0,0,0) 70%,rgb(0,0,0) 100%)
            ';
			$text_color       = ! empty( $this->text_color ) ? esc_attr( $this->text_color ) : '#fff';
			?>
			<li id="accordion-section-<?php echo esc_attr( $this->id ); ?>" class="deepora_upsell_section accordion-section control-section control-section-<?php echo esc_attr( $this->id ); ?> cannot-expand">
				<h3 class="accordion-section-title" style="border: 0; color:#fff; background:<?php echo esc_attr( $background ); ?>;">
					<?php echo esc_html( $this->title ); ?>
					<a href="<?php echo esc_url( $this->url ); ?>" class="button button-secondary alignright" target="_blank" style="margin-top: -4px;"><?php echo esc_html( $this->button_text ); ?></a>
				</h3>
			</li>
			<?php
		}
	}
}
require get_template_directory() . '/inc/get-started/get-started.php';


function deepora_notice() {
    $user_id = get_current_user_id();
    if ( !get_user_meta( $user_id, 'deepora_notice_dismissed' ) ) {
 
        ?>
        <div class="updated notice notice-success is-dismissible notice-get-started-class" data-notice="get-start" style="display: flex-inline;padding: 10px;">
        <h2 style="color: #FFC300"><?php esc_html_e('☆☆☆☆☆', 'deepora'); ?><br></h2>
            <p><?php esc_html_e('This is just a sample of what the Deepora Template can do, the Premium Version is waiting for you!', 'deepora'); ?></p>
            <a style="margin-top: 18px;" class="button button-primary" target="_blank"
               href="<?php echo esc_url('https://realtimethemes.com/theme-deepora'); ?>"><?php esc_html_e('See Premium Version', 'deepora') ?></a>
               <a href="?deepora-dismissed" style="margin-top: 18px;" class="button button-secondary"><?php esc_html_e('Dismiss', 'deepora'); ?></a>
        </div>
        <?php
        }
}
add_action( 'admin_notices', 'deepora_notice' ); 

function deepora_notice_dismissed() {
    $user_id = get_current_user_id();
    if ( isset( $_GET['deepora-dismissed'] ) ) 
        add_user_meta( $user_id, 'deepora_notice_dismissed', 'true', true );
}
add_action( 'admin_init', 'deepora_notice_dismissed' );

/* Theme credit link */
define('DEEPORA_BUY_NOW', 'https://realtimethemes.com/theme-deepora');
define('DEEPORA_PRO_DEMO', 'https://preview.realtimethemes.com/deepora/');
define('DEEPORA_REVIEW', 'https://realtimethemes.com/theme-deepora');
define('DEEPORA_SUPPORT', 'https://realtimethemes.com/');

