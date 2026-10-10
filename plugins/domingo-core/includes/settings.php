<?php
/**
 * 後台「多明哥網站設定」：診斷題目、結果說明、工具清單、產業標籤。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

const DOMINGO_SETTINGS_KEY = 'domingo_settings';

/**
 * 預設值（第一次安裝時使用）。
 */
function domingo_default_settings() {
	return array(
		'quiz'        => array(
			array(
				'axis'     => '重複度',
				'question' => '同事每天花多少時間在重複工作？',
				'options'  => array( array( '不到 1 小時', 3 ), array( '1–3 小時', 6 ), array( '3 小時以上', 9 ) ),
			),
			array(
				'axis'     => '規則清楚',
				'question' => '這些工作有固定的處理規則嗎？',
				'options'  => array( array( '大部分有 SOP', 9 ), array( '大約一半', 6 ), array( '多半靠經驗', 3 ) ),
			),
			array(
				'axis'     => '資料數位化',
				'question' => '工作需要的資料放在哪裡？',
				'options'  => array( array( '都在電腦或雲端', 9 ), array( '一半紙本', 6 ), array( '多在老員工腦中', 3 ) ),
			),
			array(
				'axis'     => '團隊意願',
				'question' => '能撥一位同事每週 2–3 小時學習嗎？',
				'options'  => array( array( '可以，人選想好了', 9 ), array( '可以試試', 6 ), array( '很難抽出人力', 3 ) ),
			),
			array(
				'axis'     => '決策速度',
				'question' => '導入預算由誰決定？',
				'options'  => array( array( '我自己就能決定', 9 ), array( '要和合夥人討論', 6 ), array( '要層層簽核', 4 ) ),
			),
		),
		'advice_high' => '你們有明顯的重複工作，規則和資料也清楚，很適合從一個小流程開始。',
		'advice_mid'  => '有適合自動化的工作，但規則或資料要先整理一部分。',
		'advice_low'  => '先整理 SOP 和資料，會比直接導入 AI 更有效。',
		'tools_label' => '串接你已經在用的工具',
		'tools'       => "LINE 官方帳號|#06C755|L\nGoogle 試算表|#0F9D58|G\nGmail|#EA4335|M\nGoogle 日曆|#4285F4|Ca\nMake|#6D00CC|Mk\nn8n|#EA4B71|n8\nChatGPT|#10A37F|AI\nClaude|#C96442|C\nNotion|#3a3a3a|N\nExcel|#217346|X",
		'ind_label'   => '這些產業的老闆，最常來預約評估',
		'ind_a'       => "★工程行\n★設備經銷\n★醫美診所\n★補習班\n婚禮顧問\n★房仲團隊\n室內設計\n貿易商",
		'ind_b'       => "牙醫診所\n健身教室\n汽車保養\n印刷廠\n電商小品牌\n物流車隊\n會計事務所\n餐飲連鎖",
	);
}

/**
 * 取得設定（與預設值合併）。
 */
function domingo_settings() {
	$saved = get_option( DOMINGO_SETTINGS_KEY, array() );
	$saved = is_array( $saved ) ? $saved : array();
	return array_merge( domingo_default_settings(), $saved );
}

/**
 * 把多行文字拆成清單（去掉空白行）。
 */
function domingo_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/**
 * 工具清單：每行「名稱|#色碼|縮寫」。
 */
function domingo_tools() {
	$out = array();
	foreach ( domingo_lines( domingo_settings()['tools'] ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		$color = sanitize_hex_color( $parts[1] ?? '' );
		$out[] = array(
			'name'  => $parts[0],
			'color' => $color ? $color : '#0d9488',
			'abbr'  => isset( $parts[2] ) && '' !== $parts[2] ? $parts[2] : mb_substr( $parts[0], 0, 1 ),
		);
	}
	return $out;
}

/**
 * 產業標籤：每行一個，開頭加 ★ 代表要強調。
 */
function domingo_industries( $key ) {
	$out = array();
	foreach ( domingo_lines( domingo_settings()[ $key ] ) as $line ) {
		$hot   = str_starts_with( $line, '★' );
		$out[] = array(
			'name' => $hot ? trim( mb_substr( $line, 1 ) ) : $line,
			'hot'  => $hot,
		);
	}
	return $out;
}

/* ---------- 後台設定頁 ---------- */

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'多明哥網站設定',
			'網站設定',
			'manage_options',
			'domingo-settings',
			'domingo_render_settings_page',
			'dashicons-chart-area',
			58
		);
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'domingo_settings_group',
			DOMINGO_SETTINGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'domingo_sanitize_settings',
				'default'           => array(),
			)
		);
	}
);

/**
 * 儲存前檢查與清理。
 */
function domingo_sanitize_settings( $input ) {
	$defaults = domingo_default_settings();
	$clean    = array();
	$input    = is_array( $input ) ? $input : array();

	$clean['quiz'] = array();
	for ( $i = 0; $i < 5; $i++ ) {
		$q    = $input['quiz'][ $i ] ?? array();
		$opts = array();
		for ( $j = 0; $j < 3; $j++ ) {
			$text  = sanitize_text_field( $q['options'][ $j ][0] ?? '' );
			$score = max( 0, min( 10, (int) ( $q['options'][ $j ][1] ?? 0 ) ) );
			$opts[] = array( '' !== $text ? $text : $defaults['quiz'][ $i ]['options'][ $j ][0], $score );
		}
		$axis     = sanitize_text_field( $q['axis'] ?? '' );
		$question = sanitize_text_field( $q['question'] ?? '' );
		$clean['quiz'][] = array(
			'axis'     => '' !== $axis ? $axis : $defaults['quiz'][ $i ]['axis'],
			'question' => '' !== $question ? $question : $defaults['quiz'][ $i ]['question'],
			'options'  => $opts,
		);
	}
	foreach ( array( 'advice_high', 'advice_mid', 'advice_low', 'tools_label', 'ind_label' ) as $k ) {
		$clean[ $k ] = sanitize_text_field( $input[ $k ] ?? $defaults[ $k ] );
	}
	foreach ( array( 'tools', 'ind_a', 'ind_b' ) as $k ) {
		$clean[ $k ] = sanitize_textarea_field( $input[ $k ] ?? $defaults[ $k ] );
	}
	return $clean;
}

/**
 * 設定頁畫面。
 */
function domingo_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s   = domingo_settings();
	$key = DOMINGO_SETTINGS_KEY;
	?>
	<div class="wrap">
		<h1>多明哥網站設定</h1>
		<?php domingo_render_setup_box(); ?>
		<p>這裡的內容會直接顯示在首頁。改完按最下面的「儲存設定」。</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'domingo_settings_group' ); ?>

			<h2>1 分鐘 AI 適性診斷（5 題）</h2>
			<p>每題 3 個選項，分數 0–10。5 題總分換算成 100 分：70 分以上為「高」，50–69 分為「中」，其餘為「待準備」。</p>
			<?php foreach ( $s['quiz'] as $i => $q ) : ?>
				<fieldset style="background:#fff;border:1px solid #dcdcde;padding:12px 16px;margin:0 0 12px;max-width:820px">
					<legend style="font-weight:600;padding:0 6px">第 <?php echo (int) $i + 1; ?> 題</legend>
					<p>
						<label>雷達圖軸名稱（短，2–5 字）<br>
						<input type="text" class="regular-text" name="<?php echo esc_attr( "{$key}[quiz][{$i}][axis]" ); ?>" value="<?php echo esc_attr( $q['axis'] ); ?>"></label>
					</p>
					<p>
						<label>題目<br>
						<input type="text" class="large-text" name="<?php echo esc_attr( "{$key}[quiz][{$i}][question]" ); ?>" value="<?php echo esc_attr( $q['question'] ); ?>"></label>
					</p>
					<?php foreach ( $q['options'] as $j => $o ) : ?>
						<p style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
							<label>選項 <?php echo (int) $j + 1; ?>
							<input type="text" class="regular-text" name="<?php echo esc_attr( "{$key}[quiz][{$i}][options][{$j}][0]" ); ?>" value="<?php echo esc_attr( $o[0] ); ?>"></label>
							<label>分數
							<input type="number" min="0" max="10" style="width:70px" name="<?php echo esc_attr( "{$key}[quiz][{$i}][options][{$j}][1]" ); ?>" value="<?php echo (int) $o[1]; ?>"></label>
						</p>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>

			<h2>診斷結果說明</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="dm-ah">高（70 分以上）</label></th><td><input id="dm-ah" type="text" class="large-text" name="<?php echo esc_attr( "{$key}[advice_high]" ); ?>" value="<?php echo esc_attr( $s['advice_high'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="dm-am">中（50–69 分）</label></th><td><input id="dm-am" type="text" class="large-text" name="<?php echo esc_attr( "{$key}[advice_mid]" ); ?>" value="<?php echo esc_attr( $s['advice_mid'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="dm-al">待準備（49 分以下）</label></th><td><input id="dm-al" type="text" class="large-text" name="<?php echo esc_attr( "{$key}[advice_low]" ); ?>" value="<?php echo esc_attr( $s['advice_low'] ); ?>"></td></tr>
			</table>

			<h2>工具跑馬燈</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="dm-tl">標題</label></th><td><input id="dm-tl" type="text" class="regular-text" name="<?php echo esc_attr( "{$key}[tools_label]" ); ?>" value="<?php echo esc_attr( $s['tools_label'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="dm-tools">工具清單</label></th><td>
					<textarea id="dm-tools" rows="10" class="large-text code" name="<?php echo esc_attr( "{$key}[tools]" ); ?>"><?php echo esc_textarea( $s['tools'] ); ?></textarea>
					<p class="description">一行一個，格式：<code>名稱|#色碼|縮寫</code>，例如 <code>LINE 官方帳號|#06C755|L</code>。色碼和縮寫可省略。</p>
				</td></tr>
			</table>

			<h2>產業標籤跑馬燈</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="dm-il">標題</label></th><td><input id="dm-il" type="text" class="regular-text" name="<?php echo esc_attr( "{$key}[ind_label]" ); ?>" value="<?php echo esc_attr( $s['ind_label'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="dm-ia">第一排（往左跑）</label></th><td>
					<textarea id="dm-ia" rows="8" class="regular-text" name="<?php echo esc_attr( "{$key}[ind_a]" ); ?>"><?php echo esc_textarea( $s['ind_a'] ); ?></textarea>
				</td></tr>
				<tr><th scope="row"><label for="dm-ib">第二排（往右跑）</label></th><td>
					<textarea id="dm-ib" rows="8" class="regular-text" name="<?php echo esc_attr( "{$key}[ind_b]" ); ?>"><?php echo esc_textarea( $s['ind_b'] ); ?></textarea>
					<p class="description">一行一個產業。開頭加 <code>★</code> 會用萊姆綠強調。</p>
				</td></tr>
			</table>

			<?php submit_button( '儲存設定' ); ?>
		</form>
	</div>
	<?php
}
