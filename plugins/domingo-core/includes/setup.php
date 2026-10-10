<?php
/**
 * 一鍵初始設定：建立首頁、AI 筆記、預約頁，設定首頁、網址格式與時區。可以重複執行，不會重複建立頁面。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * 找頁面：先用代稱，再用標題。
 */
function domingo_find_page( $slug, $title ) {
	$p = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $p && 'trash' !== $p->post_status ) {
		return $p;
	}
	$q = get_posts(
		array(
			'post_type'   => 'page',
			'title'       => $title,
			'post_status' => array( 'publish', 'draft', 'private' ),
			'numberposts' => 1,
		)
	);
	return $q ? $q[0] : null;
}

/**
 * 確保頁面存在且已發布，回傳 ID。
 */
function domingo_ensure_page( $slug, $title, $content = '', $template = '' ) {
	$p = domingo_find_page( $slug, $title );
	if ( $p ) {
		if ( 'publish' !== $p->post_status ) {
			wp_update_post(
				array(
					'ID'          => $p->ID,
					'post_status' => 'publish',
				)
			);
		}
		$id = $p->ID;
	} else {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			)
		);
	}
	if ( $template && $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * 執行初始設定，回傳做了哪些事。
 */
function domingo_run_setup( $hide_from_search, $trash_samples ) {
	$done = array();

	$home    = (int) get_option( 'page_on_front' );
	$home    = ( $home && 'publish' === get_post_status( $home ) ) ? $home : domingo_ensure_page( 'home', '首頁' );
	$notes   = domingo_ensure_page( 'notes', 'AI 筆記' );
	$booking = domingo_ensure_page(
		'booking',
		'先花 30 分鐘，看清楚第一步',
		"<!-- wp:paragraph -->\n<p>先填幾個問題，再選一個方便的時間。<br>會前我會先讀過你的回答。</p>\n<!-- /wp:paragraph -->",
		'page-booking'
	);
	$done[] = '建立（或確認）頁面：首頁、AI 筆記、預約頁';

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $notes );
	$done[] = '首頁設為「首頁」，文章列表設為「AI 筆記」';

	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	flush_rewrite_rules( false );
	$done[] = '網址格式設為「文章名稱」（例如 /booking/）';

	update_option( 'timezone_string', 'Asia/Taipei' );
	update_option( 'date_format', 'Y.m.d' );
	$done[] = '時區設為台北，日期格式 2026.10.10';

	if ( $hide_from_search ) {
		update_option( 'blog_public', '0' );
		$done[] = '先不讓搜尋引擎收錄（正式上線時到「設定 → 閱讀」取消勾選）';
	}

	if ( $trash_samples ) {
		$hello = get_post( 1 );
		if ( $hello && 'post' === $hello->post_type && 'trash' !== $hello->post_status && in_array( $hello->post_name, array( 'hello-world', 'hello-world-2' ), true ) ) {
			wp_trash_post( 1 );
			$done[] = '預設文章「Hello world!」移到垃圾桶';
		}
		$sample = get_page_by_path( 'sample-page', OBJECT, 'page' );
		if ( $sample && 'trash' !== $sample->post_status ) {
			wp_trash_post( $sample->ID );
			$done[] = '預設頁面「Sample Page」移到垃圾桶';
		}
	}

	update_option( 'domingo_setup_done', current_time( 'mysql' ), false );
	return $done;
}

add_action(
	'admin_post_domingo_setup',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '沒有權限。' );
		}
		check_admin_referer( 'domingo_setup' );
		$done = domingo_run_setup( ! empty( $_POST['hide_from_search'] ), ! empty( $_POST['trash_samples'] ) );
		set_transient( 'domingo_setup_result_' . get_current_user_id(), $done, 300 );
		wp_safe_redirect( admin_url( 'admin.php?page=domingo-settings' ) );
		exit;
	}
);

/**
 * 網站設定頁最上方的初始設定卡片。
 */
function domingo_render_setup_box() {
	$key    = 'domingo_setup_result_' . get_current_user_id();
	$result = get_transient( $key );
	if ( $result ) {
		delete_transient( $key );
		echo '<div class="notice notice-success"><p><strong>初始設定完成：</strong></p><ul style="list-style:disc;margin-left:1.5em">';
		foreach ( $result as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		printf( '</ul><p><a href="%s" target="_blank" rel="noopener">打開網站看看 →</a></p></div>', esc_url( home_url( '/' ) ) );
	}

	$done  = get_option( 'domingo_setup_done' );
	$theme = wp_get_theme();
	?>
	<div style="background:#fff;border:1px solid #c3c4c7;border-left:4px solid #0d9488;padding:12px 18px;margin:16px 0;max-width:820px">
		<h2 style="margin:.4em 0">一鍵初始設定</h2>
		<?php if ( 'domingo' !== $theme->get_stylesheet() ) : ?>
			<p style="color:#b32d2e"><strong>請先到「外觀 → 佈景主題」啟用「Domingo」主題，</strong>再回來按這個按鈕。（目前使用：<?php echo esc_html( $theme->get( 'Name' ) ); ?>）</p>
		<?php endif; ?>
		<p>會建立首頁、AI 筆記、預約頁，並設好首頁、網址格式（/booking/）和台北時區。可以重複按，不會重複建立頁面。<?php echo $done ? '<br>上次執行：' . esc_html( $done ) : ''; ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'domingo_setup' ); ?>
			<input type="hidden" name="action" value="domingo_setup">
			<p><label><input type="checkbox" name="hide_from_search" value="1" <?php checked( ! $done || '0' === get_option( 'blog_public' ) ); ?>> 先不讓搜尋引擎收錄（內容還沒定稿前建議勾選）</label><br>
			<label><input type="checkbox" name="trash_samples" value="1" checked> 把 WordPress 預設的「Hello world!」文章和「Sample Page」移到垃圾桶</label></p>
			<?php submit_button( $done ? '再執行一次' : '開始初始設定', 'primary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
