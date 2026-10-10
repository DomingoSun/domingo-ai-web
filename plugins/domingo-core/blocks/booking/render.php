<?php
/**
 * 預約表單：4 步驟。選項由「網站設定 → 預約時段」管理，時段由 booking.js 向 API 即時讀取。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

$domingo_b       = domingo_booking_settings();
$domingo_privacy = get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' );
$domingo_steps   = array( '公司', '需求', '聯絡', '時間' );

$domingo_picks = function ( $name, $key, $type = 'radio' ) use ( $domingo_b ) {
	foreach ( domingo_lines( $domingo_b[ $key ] ) as $opt ) {
		printf(
			'<label class="dm-pick"><input type="%1$s" name="%2$s" value="%3$s"><span>%4$s</span></label>',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $opt ),
			esc_html( $opt )
		);
	}
};
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'dm-book' ) ); // phpcs:ignore ?> data-booking-form data-api="<?php echo esc_url( rest_url( 'domingo/v1/' ) ); ?>">

	<div class="dm-book-score" data-score hidden>
		<p class="dm-book-score-num"><b data-score-num></b><small>分</small></p>
		<div>
			<p class="dm-book-score-title">你的 AI 導入適性：<b data-score-level></b></p>
			<p class="dm-book-score-sub">診斷結果會附在預約資料裡，會前我會先看。</p>
		</div>
	</div>

	<ol class="dm-steps" aria-label="預約步驟">
		<?php foreach ( $domingo_steps as $i => $label ) : ?>
			<li data-dot="<?php echo (int) $i + 1; ?>"<?php echo 0 === $i ? ' aria-current="step"' : ''; ?>><span class="dm-step-n"><?php echo (int) $i + 1; ?></span><span class="dm-step-t"><?php echo esc_html( $label ); ?></span></li>
		<?php endforeach; ?>
	</ol>

	<form class="dm-book-form" data-form novalidate>
		<div class="dm-hp" aria-hidden="true"><label>網站<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

		<section class="dm-step" data-step="1" aria-labelledby="dm-s1">
			<h2 class="dm-step-h" id="dm-s1" tabindex="-1">先認識一下你的公司</h2>
			<fieldset class="dm-field" data-field="size">
				<legend>公司規模</legend>
				<div class="dm-picks"><?php $domingo_picks( 'size', 'sizes' ); ?></div>
			</fieldset>
			<fieldset class="dm-field" data-field="industry">
				<legend>產業</legend>
				<div class="dm-picks"><?php $domingo_picks( 'industry', 'industries' ); ?></div>
			</fieldset>
		</section>

		<section class="dm-step" data-step="2" aria-labelledby="dm-s2" hidden>
			<h2 class="dm-step-h" id="dm-s2" tabindex="-1">你最想先解決哪些事？</h2>
			<fieldset class="dm-field" data-field="needs">
				<legend>想解決的問題<small>可複選</small></legend>
				<div class="dm-picks"><?php $domingo_picks( 'needs', 'needs', 'checkbox' ); ?></div>
			</fieldset>
			<fieldset class="dm-field" data-field="ai_level">
				<legend>團隊現在用 AI 的程度<small>選填</small></legend>
				<div class="dm-picks"><?php $domingo_picks( 'ai_level', 'ai_levels' ); ?></div>
			</fieldset>
			<div class="dm-field">
				<label class="dm-label" for="dm-note">補充說明<small>選填</small></label>
				<textarea id="dm-note" name="note" rows="3" maxlength="1000" placeholder="例如：每天要回 50 則 LINE 詢價，報價要翻三個 Excel。"></textarea>
			</div>
		</section>

		<section class="dm-step" data-step="3" aria-labelledby="dm-s3" hidden>
			<h2 class="dm-step-h" id="dm-s3" tabindex="-1">怎麼聯絡你？</h2>
			<div class="dm-inputs">
				<div class="dm-field" data-field="name">
					<label class="dm-label" for="dm-name">姓名</label>
					<input id="dm-name" name="name" type="text" autocomplete="name" maxlength="60" required>
				</div>
				<div class="dm-field" data-field="company">
					<label class="dm-label" for="dm-company">公司名稱</label>
					<input id="dm-company" name="company" type="text" autocomplete="organization" maxlength="80" required>
				</div>
				<div class="dm-field" data-field="email">
					<label class="dm-label" for="dm-email">Email<small>會議連結會寄到這裡</small></label>
					<input id="dm-email" name="email" type="email" autocomplete="email" inputmode="email" required>
				</div>
				<div class="dm-field" data-field="phone">
					<label class="dm-label" for="dm-phone">電話<small>選填</small></label>
					<input id="dm-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="30">
				</div>
			</div>
			<label class="dm-consent" data-field="consent">
				<input type="checkbox" name="consent" value="1">
				<span>我同意多明哥 AI 筆記用以上資料聯絡我、安排這次評估。（<a href="<?php echo esc_url( $domingo_privacy ); ?>" target="_blank" rel="noopener">隱私權政策</a>）</span>
			</label>
		</section>

		<section class="dm-step" data-step="4" aria-labelledby="dm-s4" hidden>
			<h2 class="dm-step-h" id="dm-s4" tabindex="-1">選一個方便的時間</h2>
			<p class="dm-book-tz">每場 <?php echo (int) $domingo_b['duration']; ?> 分鐘・台灣時間・Google Meet 線上進行</p>
			<fieldset class="dm-field" data-field="slot">
				<legend class="screen-reader-text">日期</legend>
				<div class="dm-dates" data-dates></div>
			</fieldset>
			<fieldset class="dm-field" data-times-wrap hidden>
				<legend data-times-label>時間</legend>
				<div class="dm-picks dm-times" data-times></div>
			</fieldset>
			<p class="dm-book-msg" data-slots-msg>正在讀取可預約的時段…</p>
		</section>

		<p class="dm-err" data-err role="alert"></p>

		<div class="dm-book-nav">
			<button type="button" class="dm-btn-ghost" data-prev hidden>← 上一步</button>
			<button type="button" class="dm-btn is-lime" data-next>下一步 →</button>
			<button type="submit" class="dm-btn is-lime" data-submit hidden>確認預約</button>
		</div>
	</form>

	<div class="dm-book-done" data-done hidden tabindex="-1">
		<svg class="dm-done-icon" viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="22"/><path d="M14 24.5l7 7 13-14"/></svg>
		<h2 class="dm-step-h">預約完成</h2>
		<p class="dm-done-slot" data-done-slot></p>
		<p>會議連結會寄到 <b data-done-email></b>。<br>需要改時間，直接回信告訴我就好。</p>
		<p class="dm-done-links"><a class="dm-btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">回首頁</a><a class="dm-btn-ghost" href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">先看看 AI 筆記</a></p>
	</div>

	<noscript><p class="dm-book-msg">這個表單需要開啟瀏覽器的 JavaScript 才能使用。</p></noscript>
</div>
