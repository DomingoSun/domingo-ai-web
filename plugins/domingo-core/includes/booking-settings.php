<?php
/**
 * 後台「預約時段」：每週固定時段、預約期限、休假日、表單選項。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

const DOMINGO_BOOKING_KEY = 'domingo_booking';

/**
 * 星期名稱（ISO：1 = 週一）。
 */
function domingo_weekday_names() {
	return array(
		1 => '週一',
		2 => '週二',
		3 => '週三',
		4 => '週四',
		5 => '週五',
		6 => '週六',
		7 => '週日',
	);
}

/**
 * 預設值。時段請到後台改成自己方便的時間。
 */
function domingo_default_booking() {
	return array(
		'duration'   => 30,
		'weekly'     => array(
			1 => '',
			2 => '19:30-21:30',
			3 => '',
			4 => '19:30-21:30',
			5 => '',
			6 => '10:00-12:00',
			7 => '',
		),
		'lead_hours' => 24,
		'max_days'   => 21,
		'blocked'    => '',
		'notify'     => 1,
		'notify_to'  => '',
		'sizes'      => "1–10 人\n11–30 人\n31–100 人\n100 人以上",
		'industries' => "製造／工程\n經銷／貿易\n醫療／診所\n教育／補習\n專業服務\n零售／電商\n不動產\n其他",
		'needs'      => "LINE／客服詢問回覆\n報價單／文件製作\n資料整理／報表\n內部 SOP／新人訓練\n行銷內容產出\n會議記錄／待辦整理\n還不確定，想先聊聊",
		'ai_levels'  => "還沒開始用\n有人自己偶爾用\n團隊已經在用",
	);
}

/**
 * 取得預約設定。
 */
function domingo_booking_settings() {
	$saved = get_option( DOMINGO_BOOKING_KEY, array() );
	$saved = is_array( $saved ) ? $saved : array();
	$s     = array_merge( domingo_default_booking(), $saved );
	$s['weekly'] = array_replace( domingo_default_booking()['weekly'], is_array( $s['weekly'] ) ? $s['weekly'] : array() );
	return $s;
}

/**
 * 把「19:30-21:30, 14:00-16:00」拆成 [[開始分鐘, 結束分鐘], ...]。格式不對的段落會略過。
 */
function domingo_parse_ranges( $text ) {
	$out = array();
	foreach ( preg_split( '/[,，、\s]+/u', (string) $text ) as $part ) {
		if ( ! preg_match( '/^(\d{1,2}):(\d{2})\s*[-–~～]\s*(\d{1,2}):(\d{2})$/u', trim( $part ), $m ) ) {
			continue;
		}
		$a = (int) $m[1] * 60 + (int) $m[2];
		$b = (int) $m[3] * 60 + (int) $m[4];
		if ( $m[2] < 60 && $m[4] < 60 && $a < $b && $b <= 24 * 60 ) {
			$out[] = array( $a, $b );
		}
	}
	usort( $out, fn( $x, $y ) => $x[0] <=> $y[0] );
	return $out;
}

/**
 * 時段文字整理成標準格式。
 */
function domingo_format_ranges( $ranges ) {
	return implode(
		', ',
		array_map(
			fn( $r ) => sprintf( '%02d:%02d-%02d:%02d', intdiv( $r[0], 60 ), $r[0] % 60, intdiv( $r[1], 60 ), $r[1] % 60 ),
			$ranges
		)
	);
}

/**
 * 休假日：每行一個日期，或「開始~結束」。回傳 Y-m-d 的集合。
 */
function domingo_blocked_dates( $text ) {
	$set = array();
	foreach ( domingo_lines( $text ) as $line ) {
		if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})(?:\s*[~～至]\s*(\d{4}-\d{2}-\d{2}))?$/u', $line, $m ) ) {
			continue;
		}
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d', $m[1], wp_timezone() );
		$end   = isset( $m[2] ) ? DateTimeImmutable::createFromFormat( '!Y-m-d', $m[2], wp_timezone() ) : $start;
		if ( ! $start || ! $end || $end < $start ) {
			continue;
		}
		for ( $d = $start, $i = 0; $d <= $end && $i < 366; $d = $d->modify( '+1 day' ), $i++ ) {
			$set[ $d->format( 'Y-m-d' ) ] = true;
		}
	}
	return $set;
}

/* ---------- 後台頁面 ---------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'domingo-settings', '多明哥網站設定', '首頁內容', 'manage_options', 'domingo-settings' );
		add_submenu_page( 'domingo-settings', '預約時段設定', '預約時段', 'manage_options', 'domingo-booking', 'domingo_render_booking_page' );
	},
	20
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'domingo_booking_group',
			DOMINGO_BOOKING_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'domingo_sanitize_booking',
				'default'           => array(),
			)
		);
	}
);

/**
 * 儲存前檢查。
 */
function domingo_sanitize_booking( $input ) {
	$d     = domingo_default_booking();
	$input = is_array( $input ) ? $input : array();
	$clean = array();

	$dur               = (int) ( $input['duration'] ?? 30 );
	$clean['duration'] = in_array( $dur, array( 15, 20, 30, 45, 60 ), true ) ? $dur : 30;

	$clean['weekly'] = array();
	foreach ( array_keys( domingo_weekday_names() ) as $n ) {
		$raw                   = sanitize_text_field( $input['weekly'][ $n ] ?? '' );
		$ranges                = domingo_parse_ranges( $raw );
		$clean['weekly'][ $n ] = domingo_format_ranges( $ranges );
		if ( '' !== trim( $raw ) && ! $ranges ) {
			add_settings_error( DOMINGO_BOOKING_KEY, 'dm-range-' . $n, domingo_weekday_names()[ $n ] . '的時段格式看不懂，已清空。請寫成 19:30-21:30。' );
		}
	}

	$clean['lead_hours'] = max( 0, min( 168, (int) ( $input['lead_hours'] ?? $d['lead_hours'] ) ) );
	$clean['max_days']   = max( 1, min( 90, (int) ( $input['max_days'] ?? $d['max_days'] ) ) );
	$clean['blocked']    = sanitize_textarea_field( $input['blocked'] ?? '' );
	$clean['notify']     = empty( $input['notify'] ) ? 0 : 1;
	$to                  = sanitize_email( $input['notify_to'] ?? '' );
	$clean['notify_to']  = is_email( $to ) ? $to : '';

	foreach ( array( 'sizes', 'industries', 'needs', 'ai_levels' ) as $k ) {
		$val         = sanitize_textarea_field( $input[ $k ] ?? '' );
		$clean[ $k ] = domingo_lines( $val ) ? $val : $d[ $k ];
	}
	return $clean;
}

/**
 * 設定頁。
 */
function domingo_render_booking_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s   = domingo_booking_settings();
	$key = DOMINGO_BOOKING_KEY;
	settings_errors( DOMINGO_BOOKING_KEY );
	?>
	<div class="wrap">
		<h1>預約時段設定</h1>
		<p>預約頁只會顯示這裡開放、而且還沒被預約的時段。時間都以網站時區（<?php echo esc_html( wp_timezone_string() ); ?>）計算。</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'domingo_booking_group' ); ?>

			<h2>每週固定時段</h2>
			<table class="form-table" role="presentation">
				<?php foreach ( domingo_weekday_names() as $n => $label ) : ?>
					<tr>
						<th scope="row"><label for="dm-wk-<?php echo (int) $n; ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input id="dm-wk-<?php echo (int) $n; ?>" type="text" class="regular-text" placeholder="不開放" name="<?php echo esc_attr( "{$key}[weekly][{$n}]" ); ?>" value="<?php echo esc_attr( $s['weekly'][ $n ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<p class="description">寫成 <code>19:30-21:30</code>，一天有好幾段就用逗號隔開，例如 <code>10:00-12:00, 14:00-16:00</code>。留空代表那天不開放。</p>

			<h2>預約規則</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="dm-dur">每場長度</label></th><td>
					<select id="dm-dur" name="<?php echo esc_attr( "{$key}[duration]" ); ?>">
						<?php foreach ( array( 15, 20, 30, 45, 60 ) as $m ) : ?>
							<option value="<?php echo (int) $m; ?>" <?php selected( (int) $s['duration'], $m ); ?>><?php echo (int) $m; ?> 分鐘</option>
						<?php endforeach; ?>
					</select>
				</td></tr>
				<tr><th scope="row"><label for="dm-lead">最快可以預約</label></th><td>
					<input id="dm-lead" type="number" min="0" max="168" style="width:80px" name="<?php echo esc_attr( "{$key}[lead_hours]" ); ?>" value="<?php echo (int) $s['lead_hours']; ?>"> 小時後
					<p class="description">例如填 24，客戶就不能預約明天這個時間以前的時段。</p>
				</td></tr>
				<tr><th scope="row"><label for="dm-max">最遠可以預約</label></th><td>
					<input id="dm-max" type="number" min="1" max="90" style="width:80px" name="<?php echo esc_attr( "{$key}[max_days]" ); ?>" value="<?php echo (int) $s['max_days']; ?>"> 天內
				</td></tr>
				<tr><th scope="row"><label for="dm-blk">不開放的日期</label></th><td>
					<textarea id="dm-blk" rows="5" class="regular-text code" name="<?php echo esc_attr( "{$key}[blocked]" ); ?>" placeholder="2026-12-25&#10;2027-01-29~2027-02-03"><?php echo esc_textarea( $s['blocked'] ); ?></textarea>
					<p class="description">一行一個日期；連續好幾天寫成 <code>開始~結束</code>。休假、連假、臨時有事都寫在這裡。</p>
				</td></tr>
			</table>

			<h2>新預約通知</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">寄信通知我</th><td>
					<label><input type="checkbox" name="<?php echo esc_attr( "{$key}[notify]" ); ?>" value="1" <?php checked( (int) $s['notify'], 1 ); ?>> 有新預約時寄一封信給我</label>
				</td></tr>
				<tr><th scope="row"><label for="dm-to">收件信箱</label></th><td>
					<input id="dm-to" type="email" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" name="<?php echo esc_attr( "{$key}[notify_to]" ); ?>" value="<?php echo esc_attr( $s['notify_to'] ); ?>">
					<p class="description">留空就寄到網站管理員信箱。</p>
				</td></tr>
			</table>

			<h2>表單選項</h2>
			<p>一行一個選項。</p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="dm-sz">公司規模</label></th><td><textarea id="dm-sz" rows="5" class="regular-text" name="<?php echo esc_attr( "{$key}[sizes]" ); ?>"><?php echo esc_textarea( $s['sizes'] ); ?></textarea></td></tr>
				<tr><th scope="row"><label for="dm-ind">產業</label></th><td><textarea id="dm-ind" rows="8" class="regular-text" name="<?php echo esc_attr( "{$key}[industries]" ); ?>"><?php echo esc_textarea( $s['industries'] ); ?></textarea></td></tr>
				<tr><th scope="row"><label for="dm-nd">想解決的問題（可複選）</label></th><td><textarea id="dm-nd" rows="8" class="regular-text" name="<?php echo esc_attr( "{$key}[needs]" ); ?>"><?php echo esc_textarea( $s['needs'] ); ?></textarea></td></tr>
				<tr><th scope="row"><label for="dm-ai">團隊用 AI 的程度</label></th><td><textarea id="dm-ai" rows="4" class="regular-text" name="<?php echo esc_attr( "{$key}[ai_levels]" ); ?>"><?php echo esc_textarea( $s['ai_levels'] ); ?></textarea></td></tr>
			</table>

			<?php submit_button( '儲存設定' ); ?>
		</form>

		<h2>接下來會開放的時段（預覽）</h2>
		<?php
		$days = array_slice( domingo_available_slots(), 0, 7 );
		if ( ! $days ) {
			echo '<p>目前沒有可預約的時段。請檢查每週時段與不開放的日期。</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:720px"><tbody>';
			foreach ( $days as $day ) {
				printf( '<tr><th style="width:140px">%s</th><td>%s</td></tr>', esc_html( $day['label'] ), esc_html( implode( '、', $day['times'] ) ) );
			}
			echo '</tbody></table>';
		}
		?>
	</div>
	<?php
}
