<?php
/**
 * 預約 API：/wp-json/domingo/v1/slots（讀時段）、/wp-json/domingo/v1/bookings（送出預約）。
 *
 * 防垃圾：隱藏欄位、表單至少要填 4 秒、同一個來源每小時最多 5 次、時段上鎖避免重複預約。
 * 預約成功後會觸發 do_action( 'domingo_booking_created', $post_id, $data )，第 5 階段的 Make 由這裡接。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'domingo/v1',
			'/slots',
			array(
				'methods'             => 'GET',
				'callback'            => 'domingo_rest_slots',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'domingo/v1',
			'/bookings',
			array(
				'methods'             => 'POST',
				'callback'            => 'domingo_rest_create_booking',
				'permission_callback' => '__return_true',
			)
		);
	}
);

/**
 * 表單憑證：記下發出時間，送出時檢查「填太快」或「放太久」。
 */
function domingo_form_token() {
	$t = time();
	return $t . '.' . substr( wp_hash( 'dm_booking|' . $t, 'nonce' ), 0, 20 );
}

/**
 * 檢查表單憑證。
 */
function domingo_check_token( $token ) {
	if ( ! preg_match( '/^(\d+)\.([a-f0-9]{20})$/', (string) $token, $m ) ) {
		return false;
	}
	if ( ! hash_equals( substr( wp_hash( 'dm_booking|' . $m[1], 'nonce' ), 0, 20 ), $m[2] ) ) {
		return false;
	}
	$age = time() - (int) $m[1];
	return $age >= 4 && $age <= 3 * HOUR_IN_SECONDS;
}

/**
 * 不讓主機或瀏覽器快取時段。
 */
function domingo_no_store( WP_REST_Response $res ) {
	$res->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
	$res->header( 'X-Accel-Expires', '0' );
	return $res;
}

/**
 * GET /slots
 */
function domingo_rest_slots() {
	$s = domingo_booking_settings();
	return domingo_no_store(
		new WP_REST_Response(
			array(
				'duration' => (int) $s['duration'],
				'tz'       => '台灣時間',
				'days'     => domingo_available_slots(),
				'token'    => domingo_form_token(),
			)
		)
	);
}

/**
 * 錯誤回應。
 */
function domingo_rest_error( $code, $message, $status = 400, $field = '' ) {
	return domingo_no_store(
		new WP_REST_Response(
			array(
				'ok'      => false,
				'code'    => $code,
				'message' => $message,
				'field'   => $field,
			),
			$status
		)
	);
}

/**
 * 訪客 IP（只用來限制送出次數，存成雜湊）。
 */
function domingo_client_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return 'dm_rl_' . substr( wp_hash( $ip, 'nonce' ), 0, 16 );
}

/**
 * POST /bookings
 */
function domingo_rest_create_booking( WP_REST_Request $req ) {
	$p = $req->get_json_params();
	$p = is_array( $p ) ? $p : $req->get_params();

	/* 機器人：隱藏欄位有填、或憑證不對，就當作成功但不存（不讓機器人知道被擋）。 */
	if ( ! empty( $p['website'] ) ) {
		return domingo_no_store( new WP_REST_Response( array( 'ok' => true ) ) );
	}
	if ( ! domingo_check_token( $p['token'] ?? '' ) ) {
		return domingo_rest_error( 'token', '表單停留太久或送出太快，請重新整理頁面再試一次。', 400 );
	}

	$rl = domingo_client_key();
	$n  = (int) get_transient( $rl );
	if ( $n >= 5 ) {
		return domingo_rest_error( 'rate', '送出次數太多了，請一小時後再試，或直接寫信給我。', 429 );
	}

	$s    = domingo_booking_settings();
	$pick = function ( $value, $key ) use ( $s ) {
		$value = sanitize_text_field( (string) $value );
		return in_array( $value, domingo_lines( $s[ $key ] ), true ) ? $value : '';
	};

	$data = array(
		'size'     => $pick( $p['size'] ?? '', 'sizes' ),
		'industry' => $pick( $p['industry'] ?? '', 'industries' ),
		'needs'    => array_values( array_filter( array_map( fn( $v ) => $pick( $v, 'needs' ), (array) ( $p['needs'] ?? array() ) ) ) ),
		'ai_level' => $pick( $p['ai_level'] ?? '', 'ai_levels' ),
		'note'     => mb_substr( sanitize_textarea_field( (string) ( $p['note'] ?? '' ) ), 0, 1000 ),
		'name'     => mb_substr( sanitize_text_field( (string) ( $p['name'] ?? '' ) ), 0, 60 ),
		'company'  => mb_substr( sanitize_text_field( (string) ( $p['company'] ?? '' ) ), 0, 80 ),
		'email'    => sanitize_email( (string) ( $p['email'] ?? '' ) ),
		'phone'    => preg_replace( '/[^0-9+\-\s#()]/', '', (string) ( $p['phone'] ?? '' ) ),
		'slot'     => sanitize_text_field( (string) ( $p['slot'] ?? '' ) ),
		'quiz'     => '',
		'score'    => '',
	);
	$data['phone'] = mb_substr( trim( $data['phone'] ), 0, 30 );

	$quiz = (string) ( $p['quiz'] ?? '' );
	if ( preg_match( '/^\d{1,2}(-\d{1,2}){1,9}$/', $quiz ) && isset( $p['score'] ) && is_numeric( $p['score'] ) ) {
		$data['quiz']  = $quiz;
		$data['score'] = (string) max( 0, min( 100, (int) $p['score'] ) );
	}

	$required = array(
		'size'     => '請選公司規模。',
		'industry' => '請選產業。',
		'needs'    => '請至少選一個想解決的問題。',
		'name'     => '請填姓名。',
		'company'  => '請填公司名稱。',
	);
	foreach ( $required as $k => $msg ) {
		if ( empty( $data[ $k ] ) ) {
			return domingo_rest_error( 'invalid', $msg, 400, $k );
		}
	}
	if ( ! is_email( $data['email'] ) ) {
		return domingo_rest_error( 'invalid', 'Email 格式好像不對，請再檢查一次。', 400, 'email' );
	}
	if ( empty( $p['consent'] ) ) {
		return domingo_rest_error( 'invalid', '請勾選同意，我才能用這些資料跟你聯絡。', 400, 'consent' );
	}

	/* 同一時段上鎖，避免兩個人同時按下去 */
	$lock = 'dm_slot_lock_' . md5( $data['slot'] );
	if ( ! add_option( $lock, time(), '', false ) ) {
		if ( time() - (int) get_option( $lock ) > 30 ) {
			delete_option( $lock );
		}
		return domingo_rest_error( 'busy', '這個時段剛好有人在預約，請等幾秒再試，或換一個時間。', 409, 'slot' );
	}

	try {
		if ( ! domingo_slot_is_open( $data['slot'] ) ) {
			return domingo_rest_error( 'taken', '這個時段剛被預約走了，請換一個時間。', 409, 'slot' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'dm_booking',
				'post_status' => 'publish',
				'post_title'  => $data['name'] . '｜' . $data['company'],
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return domingo_rest_error( 'save', '系統暫時存不進去，請稍後再試，或直接寫信給我。', 500 );
		}
		foreach ( $data as $k => $v ) {
			update_post_meta( $post_id, '_dm_' . $k, $v );
		}
		update_post_meta( $post_id, '_dm_status', 'new' );
	} finally {
		delete_option( $lock );
	}

	set_transient( $rl, $n + 1, HOUR_IN_SECONDS );

	domingo_notify_new_booking( $post_id, $data );

	/** 第 5 階段：Make 自動化從這裡接（建立日曆會議、寄確認信）。 */
	do_action( 'domingo_booking_created', $post_id, $data );

	return domingo_no_store(
		new WP_REST_Response(
			array(
				'ok'    => true,
				'slot'  => domingo_slot_label( $data['slot'] ),
				'email' => $data['email'],
			),
			201
		)
	);
}

/**
 * 寄信通知站長有新預約。
 */
function domingo_notify_new_booking( $post_id, $data ) {
	$s = domingo_booking_settings();
	if ( empty( $s['notify'] ) ) {
		return;
	}
	$to   = $s['notify_to'] ? $s['notify_to'] : get_option( 'admin_email' );
	$body = implode(
		"\n",
		array(
			'時段：' . domingo_slot_label( $data['slot'] ),
			'姓名：' . $data['name'],
			'公司：' . $data['company'],
			'Email：' . $data['email'],
			'電話：' . ( $data['phone'] ? $data['phone'] : '—' ),
			'規模：' . $data['size'],
			'產業：' . $data['industry'],
			'想解決：' . implode( '、', $data['needs'] ),
			'用 AI 的程度：' . ( $data['ai_level'] ? $data['ai_level'] : '—' ),
			'診斷分數：' . ( '' === $data['score'] ? '沒有做診斷' : $data['score'] . ' 分' ),
			'補充：' . ( $data['note'] ? $data['note'] : '—' ),
			'',
			'後台查看：' . admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' ),
		)
	);
	wp_mail(
		$to,
		'【新預約】' . domingo_slot_label( $data['slot'] ) . '｜' . $data['company'],
		$body,
		array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' )
	);
}

/**
 * 現在是不是預約頁。
 */
function domingo_is_booking_page() {
	return is_page() && ( 'page-booking' === get_page_template_slug() || has_block( 'domingo/booking', get_queried_object() ) );
}

/* 預約頁一進來就停在最上方：在 <head> 最前面、畫面畫出來之前執行 */
add_action(
	'wp_head',
	function () {
		if ( domingo_is_booking_page() ) {
			echo '<script id="domingo-top-guard">' . file_get_contents( DOMINGO_CORE_DIR . 'assets/top-guard.js' ) . "</script>\n"; // phpcs:ignore
		}
	},
	0
);

/* 預約頁不要被快取（時段要即時） */
add_action(
	'template_redirect',
	function () {
		if ( domingo_is_booking_page() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
		}
	}
);
