<?php
/**
 * 註冊互動區塊。內容都由伺服器輸出 HTML（Google 與 AI 讀得到），動畫與互動再由 front.js 補上。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		wp_register_script(
			'domingo-core-editor',
			DOMINGO_CORE_URL . 'assets/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor' ),
			DOMINGO_CORE_VERSION,
			true
		);
		wp_register_script(
			'domingo-core-front',
			DOMINGO_CORE_URL . 'assets/front.js',
			array(),
			DOMINGO_CORE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_register_script(
			'domingo-core-booking',
			DOMINGO_CORE_URL . 'assets/booking.js',
			array(),
			DOMINGO_CORE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_register_style(
			'domingo-core-blocks',
			DOMINGO_CORE_URL . 'assets/blocks.css',
			array(),
			DOMINGO_CORE_VERSION
		);

		foreach ( array( 'diagnosis-card', 'quiz', 'marquees', 'testimonials', 'booking' ) as $name ) {
			register_block_type( DOMINGO_CORE_DIR . 'blocks/' . $name );
		}
	}
);

add_filter(
	'block_categories_all',
	function ( $cats ) {
		array_unshift(
			$cats,
			array(
				'slug'  => 'domingo',
				'title' => '多明哥 AI 筆記',
			)
		);
		return $cats;
	}
);

/* ---------- 圖表共用工具 ---------- */

/**
 * 半圓儀表的 SVG。$value 0–100。
 */
function domingo_gauge_svg( $value, $caption ) {
	$value = max( 0, min( 100, (int) $value ) );
	$off   = 100 - $value;
	return sprintf(
		'<svg class="dm-gauge" viewBox="0 0 200 118" role="img" aria-label="%1$s %2$d 分">
			<path class="dm-gauge-track" d="M18 104 A82 82 0 0 1 182 104" pathLength="100"/>
			<path class="dm-gauge-fill" d="M18 104 A82 82 0 0 1 182 104" pathLength="100" style="stroke-dasharray:100;stroke-dashoffset:%3$d" data-value="%2$d"/>
			<text class="dm-gauge-num" x="100" y="92" text-anchor="middle" data-count="%2$d">%2$d</text>
			<text class="dm-gauge-cap" x="100" y="112" text-anchor="middle">%1$s</text>
		</svg>',
		esc_html( $caption ),
		$value,
		$off
	);
}

/**
 * 雷達圖上某一軸、某半徑的座標。
 */
function domingo_radar_point( $i, $n, $r ) {
	$a = deg2rad( -90 + ( 360 / $n ) * $i );
	return array( round( cos( $a ) * $r, 2 ), round( sin( $a ) * $r, 2 ) );
}

/**
 * 依 0–10 分數產生多邊形的 points 字串。
 */
function domingo_radar_points( $values, $radius = 104 ) {
	$n   = count( $values );
	$pts = array();
	foreach ( array_values( $values ) as $i => $v ) {
		$p     = domingo_radar_point( $i, $n, ( max( 0, min( 10, (float) $v ) ) / 10 ) * $radius );
		$pts[] = $p[0] . ',' . $p[1];
	}
	return implode( ' ', $pts );
}
