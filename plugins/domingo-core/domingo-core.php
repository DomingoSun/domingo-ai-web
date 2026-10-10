<?php
/**
 * Plugin Name: Domingo Core
 * Description: 多明哥 AI 筆記網站功能：AI 適性診斷、診斷報告卡、工具與產業跑馬燈、評論、預約表單與時段。換主題時這些內容不會消失。
 * Version: 0.3.0
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Author: 多明哥 AI 筆記
 * Text Domain: domingo-core
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

define( 'DOMINGO_CORE_VERSION', '0.3.0' );
define( 'DOMINGO_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'DOMINGO_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once DOMINGO_CORE_DIR . 'includes/settings.php';
require_once DOMINGO_CORE_DIR . 'includes/testimonials.php';
require_once DOMINGO_CORE_DIR . 'includes/booking-settings.php';
require_once DOMINGO_CORE_DIR . 'includes/bookings.php';
require_once DOMINGO_CORE_DIR . 'includes/booking-api.php';
require_once DOMINGO_CORE_DIR . 'includes/blocks.php';
require_once DOMINGO_CORE_DIR . 'includes/setup.php';
