<?php
/**
 * Domingo AI Notes theme.
 *
 * @package domingo
 */

defined( 'ABSPATH' ) || exit;

define( 'DOMINGO_VERSION', '0.2.0' );

/**
 * Google Fonts（Noto Sans TC／Noto Serif TC）。之後可改成主機本地字型以加快速度。
 */
function domingo_fonts_url() {
	return 'https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&family=Noto+Serif+TC:wght@700;900&display=swap';
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'domingo-fonts', domingo_fonts_url(), array(), null );
		wp_enqueue_style(
			'domingo-theme',
			get_theme_file_uri( 'assets/css/theme.css' ),
			array(),
			DOMINGO_VERSION
		);
		wp_enqueue_script(
			'domingo-theme',
			get_theme_file_uri( 'assets/js/theme.js' ),
			array(),
			DOMINGO_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
);

add_action(
	'after_setup_theme',
	function () {
		add_editor_style( array( domingo_fonts_url(), 'assets/css/theme.css' ) );
		load_theme_textdomain( 'domingo', get_template_directory() . '/languages' );
	}
);

/* 預先連線字型主機，縮短首次繪製時間 */
add_filter(
	'wp_resource_hints',
	function ( $urls, $relation ) {
		if ( 'preconnect' === $relation ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array(
				'href'        => 'https://fonts.gstatic.com',
				'crossorigin' => 'anonymous',
			);
		}
		return $urls;
	},
	10,
	2
);

/* 區塊樣板分類 */
add_action(
	'init',
	function () {
		register_block_pattern_category( 'domingo', array( 'label' => '多明哥 AI 筆記' ) );
	}
);

/* 按鈕樣式：萊姆綠 */
add_action(
	'init',
	function () {
		register_block_style(
			'core/button',
			array(
				'name'  => 'lime',
				'label' => '萊姆綠',
			)
		);
		register_block_style(
			'core/button',
			array(
				'name'  => 'ghost',
				'label' => '外框',
			)
		);
		register_block_style(
			'core/button',
			array(
				'name'  => 'dark',
				'label' => '黑底',
			)
		);
	}
);

/* 拿掉用不到的 emoji 腳本，減少前端負擔 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
