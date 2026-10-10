<?php
/**
 * 預約自動化（Make）：
 * - 有新預約 → 把資料送到 Make（建立 Google 日曆會議與 Meet、Claude 會前摘要、寄確認信）
 * - Make 做完 → 呼叫 /wp-json/domingo/v1/bookings/{id}/meeting 寫回 Meet 連結與會前摘要
 * - 後台把預約改成「已取消」→ 通知 Make 刪除會議、寄取消信
 *
 * Make 接收網址優先讀主機環境變數 DOMINGO_MAKE_WEBHOOK_URL（或 wp-config 常數），沒有才用後台設定。
 * 回呼密碼由網站自動產生，隨每次資料一起送給 Make，Make 回呼時原樣帶回即可，不需要另外保存。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

const DOMINGO_AUTOMATION_KEY = 'domingo_automation';

/**
 * 讀環境變數或常數。
 */
function domingo_env( $name ) {
	$v = getenv( $name );
	if ( false !== $v && '' !== $v ) {
		return (string) $v;
	}
	return defined( $name ) ? (string) constant( $name ) : '';
}

/**
 * Make 接收網址。
 */
function domingo_webhook_url() {
	$env = domingo_env( 'DOMINGO_MAKE_WEBHOOK_URL' );
	if ( $env ) {
		return $env;
	}
	$s = get_option( DOMINGO_AUTOMATION_KEY, array() );
	return is_array( $s ) && ! empty( $s['webhook_url'] ) ? (string) $s['webhook_url'] : '';
}

/**
 * 回呼密碼（第一次用到時自動產生）。
 */
function domingo_hook_secret() {
	$env = domingo_env( 'DOMINGO_HOOK_SECRET' );
	if ( $env ) {
		return $env;
	}
	$s = get_option( 'domingo_hook_secret' );
	if ( ! $s ) {
		$s = wp_generate_password( 40, false, false );
		update_option( 'domingo_hook_secret', $s, false );
	}
	return $s;
}

/* ---------- 送出到 Make ---------- */

/**
 * 組出要送給 Make 的資料。
 */
function domingo_booking_payload( $post_id, $event ) {
	$m     = fn( $k ) => get_post_meta( $post_id, '_dm_' . $k, true );
	$slot  = (string) $m( 'slot' );
	$dur   = (int) domingo_booking_settings()['duration'];
	$start = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $slot, wp_timezone() );
	$score = $m( 'score' );
	$needs = array_values( array_filter( (array) $m( 'needs' ) ) );

	return array(
		'event'        => $event,
		'booking_id'   => (int) $post_id,
		'name'         => $m( 'name' ),
		'company'      => $m( 'company' ),
		'email'        => $m( 'email' ),
		'phone'        => $m( 'phone' ),
		'size'         => $m( 'size' ),
		'industry'     => $m( 'industry' ),
		'needs'        => $needs,
		'needs_text'   => implode( '、', $needs ),
		'ai_level'     => $m( 'ai_level' ),
		'note'         => $m( 'note' ),
		'quiz'         => $m( 'quiz' ),
		'score'        => '' === $score ? null : (int) $score,
		'score_level'  => '' === $score ? '沒有做診斷' : ( (int) $score >= 70 ? '高' : ( (int) $score >= 50 ? '中' : '待準備' ) ),
		'slot_label'   => domingo_slot_label( $slot ),
		'start'        => $start ? $start->format( 'c' ) : '',
		'end'          => $start ? $start->modify( "+{$dur} minutes" )->format( 'c' ) : '',
		'duration'     => $dur,
		'timezone'     => wp_timezone_string(),
		'event_id'     => $m( 'event_id' ),
		'meet_url'     => $m( 'meet_url' ),
		'admin_url'    => admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' ),
		'callback_url' => rest_url( 'domingo/v1/bookings/' . (int) $post_id . '/meeting' ),
		'secret'       => domingo_hook_secret(),
		'site'         => home_url( '/' ),
	);
}

/**
 * 實際送出。回傳 [成功與否, 說明]。
 */
function domingo_post_to_make( array $payload ) {
	$url = domingo_webhook_url();
	if ( ! $url ) {
		return array( false, '還沒設定 Make 接收網址' );
	}
	$res = wp_remote_post(
		$url,
		array(
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE ),
		)
	);
	if ( is_wp_error( $res ) ) {
		return array( false, $res->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	return array( $code >= 200 && $code < 300, 'HTTP ' . $code );
}

/**
 * 送出某筆預約，記錄結果；失敗時排程重試（最多 3 次）。
 */
function domingo_send_booking_event( $post_id, $event, $attempt = 1 ) {
	list( $ok, $msg ) = domingo_post_to_make( domingo_booking_payload( $post_id, $event ) );
	$log              = sprintf( '%s｜%s｜%s', wp_date( 'Y-m-d H:i:s' ), 'created' === $event ? '新預約' : '取消', $ok ? '已送出' : '失敗：' . $msg );
	update_post_meta( $post_id, '_dm_hook_log', $log );
	update_post_meta( $post_id, '_dm_hook_ok', $ok ? 1 : 0 );
	if ( ! $ok && domingo_webhook_url() && $attempt < 3 ) {
		wp_schedule_single_event( time() + 300 * $attempt, 'domingo_retry_booking_event', array( (int) $post_id, $event, $attempt + 1 ) );
	}
	return $ok;
}
add_action( 'domingo_retry_booking_event', 'domingo_send_booking_event', 10, 3 );

/* 新預約：先回應客戶，再在背景送出，客戶不用等 Make */
add_action(
	'domingo_booking_created',
	function ( $post_id ) {
		if ( ! domingo_webhook_url() ) {
			update_post_meta( $post_id, '_dm_hook_log', '還沒設定 Make 接收網址，沒有送出' );
			return;
		}
		register_shutdown_function(
			function () use ( $post_id ) {
				if ( function_exists( 'fastcgi_finish_request' ) ) {
					fastcgi_finish_request();
				}
				domingo_send_booking_event( $post_id, 'created' );
			}
		);
	}
);

/* 後台改成「已取消」：通知 Make */
add_action(
	'updated_post_meta',
	function ( $meta_id, $post_id, $key, $value ) {
		if ( '_dm_status' === $key && 'cancelled' === $value && 'dm_booking' === get_post_type( $post_id ) && domingo_webhook_url() ) {
			domingo_send_booking_event( $post_id, 'cancelled' );
		}
	},
	10,
	4
);

/* ---------- Make 回呼：寫回 Meet 連結與會前摘要 ---------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'domingo/v1',
			'/bookings/(?P<id>\d+)/meeting',
			array(
				'methods'             => 'POST',
				'callback'            => 'domingo_rest_meeting_callback',
				'permission_callback' => function ( WP_REST_Request $req ) {
					$given = (string) $req->get_header( 'x-domingo-secret' );
					return '' !== $given && hash_equals( domingo_hook_secret(), $given );
				},
			)
		);
	}
);

/**
 * POST /bookings/{id}/meeting
 * 欄位：meet_url、event_id、summary（會前摘要，可省略）
 */
function domingo_rest_meeting_callback( WP_REST_Request $req ) {
	$id = (int) $req['id'];
	if ( 'dm_booking' !== get_post_type( $id ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => '找不到這筆預約' ), 404 );
	}
	$p = $req->get_json_params();
	$p = is_array( $p ) ? $p : $req->get_params();

	$meet = esc_url_raw( (string) ( $p['meet_url'] ?? '' ) );
	if ( $meet && ! preg_match( '#^https://meet\.google\.com/#', $meet ) ) {
		$meet = '';
	}
	if ( $meet ) {
		update_post_meta( $id, '_dm_meet_url', $meet );
	}
	if ( ! empty( $p['event_id'] ) ) {
		update_post_meta( $id, '_dm_event_id', sanitize_text_field( (string) $p['event_id'] ) );
	}
	if ( ! empty( $p['summary'] ) ) {
		update_post_meta( $id, '_dm_summary', mb_substr( sanitize_textarea_field( (string) $p['summary'] ), 0, 6000 ) );
	}
	if ( 'new' === get_post_meta( $id, '_dm_status', true ) ) {
		update_post_meta( $id, '_dm_status', 'confirmed' );
	}
	update_post_meta( $id, '_dm_hook_log', wp_date( 'Y-m-d H:i:s' ) . '｜Make 已完成：會議建立、確認信已寄出' );
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* ---------- 後台：預約內容多顯示會議與摘要 ---------- */

add_action(
	'add_meta_boxes_dm_booking',
	function () {
		add_meta_box( 'dm_booking_meeting', '會議與會前摘要', 'domingo_booking_meeting_box', 'dm_booking', 'normal', 'default' );
	}
);

/**
 * 會議資訊。
 */
function domingo_booking_meeting_box( $post ) {
	$meet    = get_post_meta( $post->ID, '_dm_meet_url', true );
	$summary = get_post_meta( $post->ID, '_dm_summary', true );
	$log     = get_post_meta( $post->ID, '_dm_hook_log', true );
	echo '<table class="widefat striped"><tbody>';
	printf( '<tr><th style="width:150px">Google Meet</th><td>%s</td></tr>', $meet ? '<a href="' . esc_url( $meet ) . '" target="_blank" rel="noopener">' . esc_html( $meet ) . '</a>' : '—（Make 完成後會自動填入）' );
	printf( '<tr><th>自動化紀錄</th><td>%s</td></tr>', esc_html( $log ? $log : '—' ) );
	echo '</tbody></table>';
	if ( $summary ) {
		echo '<h4 style="margin:1em 0 .4em">Claude 會前摘要</h4><div style="white-space:pre-wrap;background:#f6f7f7;padding:12px 14px;border-radius:4px">' . esc_html( $summary ) . '</div>';
	}
}

/* 列表多一欄「會議」 */
add_filter(
	'manage_dm_booking_posts_columns',
	function ( $cols ) {
		$out = array();
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'dm_status' === $k ) {
				$out['dm_meet'] = '會議';
			}
		}
		return $out;
	},
	20
);
add_action(
	'manage_dm_booking_posts_custom_column',
	function ( $col, $id ) {
		if ( 'dm_meet' !== $col ) {
			return;
		}
		$meet = get_post_meta( $id, '_dm_meet_url', true );
		if ( $meet ) {
			printf( '<a href="%s" target="_blank" rel="noopener">開啟 Meet</a>', esc_url( $meet ) );
		} elseif ( '0' === (string) get_post_meta( $id, '_dm_hook_ok', true ) ) {
			echo '<span style="color:#b32d2e">送出失敗，會自動重試</span>';
		} else {
			echo '—';
		}
	},
	10,
	2
);

/* ---------- 後台設定頁：網站設定 → 自動化 ---------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'domingo-settings', '預約自動化（Make）', '自動化', 'manage_options', 'domingo-automation', 'domingo_render_automation_page' );
	},
	21
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'domingo_automation_group',
			DOMINGO_AUTOMATION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $in ) {
					$url = esc_url_raw( trim( (string) ( $in['webhook_url'] ?? '' ) ) );
					if ( $url && ! preg_match( '#^https://#', $url ) ) {
						add_settings_error( DOMINGO_AUTOMATION_KEY, 'dm-url', 'Make 接收網址要以 https:// 開頭。' );
						$url = '';
					}
					return array( 'webhook_url' => $url );
				},
				'default'           => array(),
			)
		);
	}
);

add_action(
	'admin_post_domingo_test_hook',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '沒有權限。' );
		}
		check_admin_referer( 'domingo_test_hook' );
		$tz      = wp_timezone();
		$start   = ( new DateTimeImmutable( 'tomorrow 20:00', $tz ) );
		$payload = array(
			'event'        => 'test',
			'booking_id'   => 0,
			'name'         => '測試王小明',
			'company'      => '測試公司',
			'email'        => get_option( 'admin_email' ),
			'phone'        => '',
			'size'         => '11–30 人',
			'industry'     => '經銷／貿易',
			'needs'        => array( 'LINE／客服詢問回覆', '報價單／文件製作' ),
			'needs_text'   => 'LINE／客服詢問回覆、報價單／文件製作',
			'ai_level'     => '有人自己偶爾用',
			'note'         => '每天要回 50 則 LINE 詢價，報價要翻三個 Excel。',
			'quiz'         => '9-9-6-9-9',
			'score'        => 84,
			'score_level'  => '高',
			'slot_label'   => domingo_date_label( $start ) . ' 20:00',
			'start'        => $start->format( 'c' ),
			'end'          => $start->modify( '+30 minutes' )->format( 'c' ),
			'duration'     => 30,
			'timezone'     => wp_timezone_string(),
			'event_id'     => '',
			'meet_url'     => '',
			'admin_url'    => admin_url( 'edit.php?post_type=dm_booking' ),
			'callback_url' => rest_url( 'domingo/v1/bookings/0/meeting' ),
			'secret'       => domingo_hook_secret(),
			'site'         => home_url( '/' ),
		);
		list( $ok, $msg ) = domingo_post_to_make( $payload );
		set_transient( 'domingo_test_hook_' . get_current_user_id(), array( $ok, $msg ), 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=domingo-automation' ) );
		exit;
	}
);

/**
 * 自動化設定頁。
 */
function domingo_render_automation_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	settings_errors( DOMINGO_AUTOMATION_KEY );
	$key  = 'domingo_test_hook_' . get_current_user_id();
	$test = get_transient( $key );
	if ( $test ) {
		delete_transient( $key );
		printf(
			'<div class="notice notice-%s"><p>%s</p></div>',
			$test[0] ? 'success' : 'error',
			esc_html( $test[0] ? '測試資料已送到 Make（' . $test[1] . '）。到 Make 看看有沒有收到。' : '送出失敗：' . $test[1] )
		);
	}
	$from_env = (bool) domingo_env( 'DOMINGO_MAKE_WEBHOOK_URL' );
	$saved    = get_option( DOMINGO_AUTOMATION_KEY, array() );
	$url      = domingo_webhook_url();
	?>
	<div class="wrap">
		<h1>預約自動化（Make）</h1>
		<p>有人預約時，網站會把資料送到 Make。Make 會建立 Google 日曆會議和 Meet 連結、請 Claude 寫會前摘要、寄確認信給客戶，最後把 Meet 連結寫回這裡的「預約」。在後台把預約改成「已取消」時，也會通知 Make 刪除會議並寄取消信。</p>

		<h2>Make 接收網址</h2>
		<?php if ( $from_env ) : ?>
			<p>已由主機設定（環境變數 <code>DOMINGO_MAKE_WEBHOOK_URL</code>），這裡不用填。</p>
		<?php else : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'domingo_automation_group' ); ?>
				<p><input type="url" class="large-text code" name="<?php echo esc_attr( DOMINGO_AUTOMATION_KEY ); ?>[webhook_url]" value="<?php echo esc_attr( $saved['webhook_url'] ?? '' ); ?>" placeholder="https://hook.eu2.make.com/xxxxxxxx" autocomplete="off"></p>
				<p class="description">在 Make 建立「Webhooks → Custom webhook」後，複製它給的網址貼在這裡。這個網址等同鑰匙，不要公開分享。</p>
				<?php submit_button( '儲存' ); ?>
			</form>
		<?php endif; ?>

		<h2>測試</h2>
		<p>送一筆假的預約資料到 Make，用來讓 Make 認得資料欄位。<?php echo $url ? '' : '<strong>請先填好上面的網址。</strong>'; ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'domingo_test_hook' ); ?>
			<input type="hidden" name="action" value="domingo_test_hook">
			<?php submit_button( '送出測試資料', 'secondary', 'submit', false, $url ? array() : array( 'disabled' => 'disabled' ) ); ?>
		</form>

		<h2>給 Make 用的回寫網址</h2>
		<p>每筆資料裡已經帶著 <code>callback_url</code>（回寫網址）和 <code>secret</code>（回寫密碼）。在 Make 最後一步用 HTTP 模組送回來即可，不需要另外抄寫。</p>
		<p><code>POST <?php echo esc_html( rest_url( 'domingo/v1/bookings/{預約編號}/meeting' ) ); ?></code><br>標頭 <code>X-Domingo-Secret: {{secret}}</code>，內容 <code>{"meet_url": "...", "event_id": "...", "summary": "..."}</code></p>
	</div>
	<?php
}
