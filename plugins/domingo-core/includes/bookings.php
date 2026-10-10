<?php
/**
 * 預約：資料存在後台「預約」，並計算還能預約的時段。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * 預約狀態。已取消的時段會重新開放。
 */
function domingo_booking_statuses() {
	return array(
		'new'       => '新預約',
		'confirmed' => '已確認',
		'done'      => '已完成',
		'cancelled' => '已取消',
	);
}

add_action(
	'init',
	function () {
		register_post_type(
			'dm_booking',
			array(
				'labels'          => array(
					'name'          => '預約',
					'singular_name' => '預約',
					'edit_item'     => '預約內容',
					'all_items'     => '所有預約',
					'menu_name'     => '預約',
					'not_found'     => '還沒有預約',
					'search_items'  => '搜尋預約',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => false,
				'menu_icon'       => 'dashicons-calendar-alt',
				'menu_position'   => 20,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}
);

/* ---------- 時段計算 ---------- */

/**
 * 已被預約（未取消）的時段，格式 Y-m-d H:i。
 */
function domingo_booked_slots() {
	$today = wp_date( 'Y-m-d' );
	$q     = new WP_Query(
		array(
			'post_type'      => 'dm_booking',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'AND',
				array(
					'key'     => '_dm_slot',
					'value'   => $today,
					'compare' => '>=',
				),
				array(
					'key'     => '_dm_status',
					'value'   => 'cancelled',
					'compare' => '!=',
				),
			),
		)
	);
	$set = array();
	foreach ( $q->posts as $id ) {
		$set[ get_post_meta( $id, '_dm_slot', true ) ] = true;
	}
	return $set;
}

/**
 * 日期的中文標籤，例如 10/13（二）。
 */
function domingo_date_label( DateTimeImmutable $d ) {
	$w = array( 1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日' );
	return $d->format( 'n/j' ) . '（' . $w[ (int) $d->format( 'N' ) ] . '）';
}

/**
 * 還能預約的時段，依日期分組。
 *
 * @return array<int, array{date:string,label:string,times:string[]}>
 */
function domingo_available_slots() {
	$s        = domingo_booking_settings();
	$tz       = wp_timezone();
	$now      = new DateTimeImmutable( 'now', $tz );
	$earliest = $now->modify( '+' . (int) $s['lead_hours'] . ' hours' );
	$today    = $now->setTime( 0, 0 );
	$dur      = (int) $s['duration'];
	$blocked  = domingo_blocked_dates( $s['blocked'] );
	$booked   = domingo_booked_slots();
	$days     = array();

	for ( $i = 0; $i <= (int) $s['max_days']; $i++ ) {
		$day = $today->modify( "+{$i} day" );
		$key = $day->format( 'Y-m-d' );
		if ( isset( $blocked[ $key ] ) ) {
			continue;
		}
		$times = array();
		foreach ( domingo_parse_ranges( $s['weekly'][ (int) $day->format( 'N' ) ] ?? '' ) as $r ) {
			for ( $m = $r[0]; $m + $dur <= $r[1]; $m += $dur ) {
				$at   = $day->setTime( intdiv( $m, 60 ), $m % 60 );
				$hhmm = $at->format( 'H:i' );
				if ( $at < $earliest || isset( $booked[ "{$key} {$hhmm}" ] ) ) {
					continue;
				}
				$times[] = $hhmm;
			}
		}
		if ( $times ) {
			$days[] = array(
				'date'  => $key,
				'label' => domingo_date_label( $day ),
				'times' => array_values( array_unique( $times ) ),
			);
		}
	}
	return $days;
}

/**
 * 這個時段現在還能預約嗎？
 */
function domingo_slot_is_open( $slot ) {
	if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})$/', (string) $slot, $m ) ) {
		return false;
	}
	foreach ( domingo_available_slots() as $day ) {
		if ( $day['date'] === $m[1] ) {
			return in_array( $m[2], $day['times'], true );
		}
	}
	return false;
}

/* ---------- 後台列表 ---------- */

add_filter(
	'manage_dm_booking_posts_columns',
	function ( $cols ) {
		return array(
			'cb'        => $cols['cb'],
			'dm_slot'   => '預約時段',
			'title'     => '姓名｜公司',
			'dm_email'  => 'Email',
			'dm_score'  => '診斷分數',
			'dm_status' => '狀態',
			'date'      => '送出時間',
		);
	}
);

add_action(
	'manage_dm_booking_posts_custom_column',
	function ( $col, $id ) {
		switch ( $col ) {
			case 'dm_slot':
				echo '<strong>' . esc_html( domingo_slot_label( get_post_meta( $id, '_dm_slot', true ) ) ) . '</strong>';
				break;
			case 'dm_email':
				$e = get_post_meta( $id, '_dm_email', true );
				printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $e ) );
				break;
			case 'dm_score':
				$sc = get_post_meta( $id, '_dm_score', true );
				echo '' === $sc ? '—' : esc_html( $sc . ' 分' );
				break;
			case 'dm_status':
				$st = get_post_meta( $id, '_dm_status', true );
				echo esc_html( domingo_booking_statuses()[ $st ] ?? '新預約' );
				break;
		}
	},
	10,
	2
);

add_filter(
	'manage_edit-dm_booking_sortable_columns',
	function ( $cols ) {
		$cols['dm_slot'] = 'dm_slot';
		return $cols;
	}
);

/* 預設依預約時段排序（最新的在上面） */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'dm_booking' !== $q->get( 'post_type' ) ) {
			return;
		}
		$orderby = $q->get( 'orderby' );
		if ( ! $orderby || 'dm_slot' === $orderby ) {
			$q->set( 'meta_key', '_dm_slot' );
			$q->set( 'orderby', 'meta_value' );
			if ( ! $q->get( 'order' ) || ! $orderby ) {
				$q->set( 'order', 'DESC' );
			}
		}
	}
);

/* 列表只留「檢視內容」與「刪除」，不需要快速編輯 */
add_filter(
	'post_row_actions',
	function ( $actions, $post ) {
		if ( 'dm_booking' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'], $actions['view'] );
		}
		return $actions;
	},
	10,
	2
);

/**
 * 「2026-10-13 19:30」→「10/13（二）19:30」。
 */
function domingo_slot_label( $slot ) {
	$d = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', (string) $slot, wp_timezone() );
	return $d ? domingo_date_label( $d ) . ' ' . $d->format( 'H:i' ) : (string) $slot;
}

/* ---------- 單筆預約畫面 ---------- */

add_action(
	'add_meta_boxes_dm_booking',
	function () {
		remove_meta_box( 'submitdiv', 'dm_booking', 'side' );
		add_meta_box( 'dm_booking_detail', '客戶填寫的內容', 'domingo_booking_detail_box', 'dm_booking', 'normal', 'high' );
		add_meta_box( 'dm_booking_status', '狀態', 'domingo_booking_status_box', 'dm_booking', 'side', 'high' );
	}
);

/**
 * 客戶填寫的內容（唯讀）。
 */
function domingo_booking_detail_box( $post ) {
	$m     = fn( $k ) => get_post_meta( $post->ID, $k, true );
	$needs = (array) $m( '_dm_needs' );
	$rows  = array(
		'預約時段'      => domingo_slot_label( $m( '_dm_slot' ) ),
		'姓名'        => $m( '_dm_name' ),
		'公司'        => $m( '_dm_company' ),
		'Email'     => $m( '_dm_email' ),
		'電話'        => $m( '_dm_phone' ) ? $m( '_dm_phone' ) : '—',
		'公司規模'      => $m( '_dm_size' ),
		'產業'        => $m( '_dm_industry' ),
		'想解決的問題'    => implode( '、', array_filter( $needs ) ),
		'團隊用 AI 的程度' => $m( '_dm_ai_level' ),
		'補充說明'      => $m( '_dm_note' ) ? $m( '_dm_note' ) : '—',
		'診斷分數'      => '' === $m( '_dm_score' ) ? '沒有做診斷' : $m( '_dm_score' ) . ' 分（各題：' . $m( '_dm_quiz' ) . '）',
	);
	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $label => $value ) {
		printf( '<tr><th style="width:150px">%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( (string) $value ) ) );
	}
	echo '</tbody></table>';
}

/**
 * 狀態與內部備註。
 */
function domingo_booking_status_box( $post ) {
	wp_nonce_field( 'dm_booking_save', 'dm_booking_nonce' );
	$st = get_post_meta( $post->ID, '_dm_status', true );
	echo '<p><select name="dm_status" style="width:100%">';
	foreach ( domingo_booking_statuses() as $k => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $st ? $st : 'new', $k, false ), esc_html( $label ) );
	}
	echo '</select></p>';
	echo '<p class="description">改成「已取消」，這個時段會重新開放給別人預約；有設定自動化時，也會通知 Make 刪除會議、寄取消信給客戶。</p>';
	printf(
		'<p><label for="dm-internal"><strong>內部備註</strong>（客戶看不到）</label><textarea id="dm-internal" name="dm_internal" rows="4" style="width:100%%">%s</textarea></p>',
		esc_textarea( get_post_meta( $post->ID, '_dm_internal', true ) )
	);
	echo '<p><button type="submit" class="button button-primary button-large" style="width:100%">儲存</button></p>';
	echo '<input type="hidden" name="post_status" value="publish">';
}

add_action(
	'save_post_dm_booking',
	function ( $post_id ) {
		if ( ! isset( $_POST['dm_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dm_booking_nonce'] ) ), 'dm_booking_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$st = sanitize_key( wp_unslash( $_POST['dm_status'] ?? 'new' ) );
		update_post_meta( $post_id, '_dm_status', array_key_exists( $st, domingo_booking_statuses() ) ? $st : 'new' );
		update_post_meta( $post_id, '_dm_internal', sanitize_textarea_field( wp_unslash( $_POST['dm_internal'] ?? '' ) ) );
	}
);

/* 選單上顯示「還沒處理的新預約」數量 */
add_action(
	'admin_menu',
	function () {
		global $menu;
		$q = new WP_Query(
			array(
				'post_type'      => 'dm_booking',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_dm_status', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'new', // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$n = (int) $q->found_posts;
		if ( ! $n ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( 'edit.php?post_type=dm_booking' === $item[2] ) {
				$menu[ $i ][0] .= sprintf( ' <span class="awaiting-mod"><span class="pending-count">%d</span></span>', $n ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				break;
			}
		}
	},
	99
);
