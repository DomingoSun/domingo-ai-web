<?php
/**
 * 1 分鐘 AI 適性診斷：題目由伺服器輸出，雷達圖與分數由 front.js 即時計算。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

$domingo_s     = domingo_settings();
$domingo_quiz  = $domingo_s['quiz'];
$domingo_n     = count( $domingo_quiz );
$domingo_r     = 104;
$domingo_ghost = array( 7, 6, 5, 6, 8 );
$domingo_book  = home_url( '/booking/' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'dm-quiz' ) ); // phpcs:ignore ?>
	data-quiz
	data-booking="<?php echo esc_url( $domingo_book ); ?>"
	data-advice-high="<?php echo esc_attr( $domingo_s['advice_high'] ); ?>"
	data-advice-mid="<?php echo esc_attr( $domingo_s['advice_mid'] ); ?>"
	data-advice-low="<?php echo esc_attr( $domingo_s['advice_low'] ); ?>">
	<div class="dm-quiz-qs">
		<?php foreach ( $domingo_quiz as $i => $q ) : ?>
			<fieldset class="dm-quiz-q" data-q="<?php echo (int) $i; ?>">
				<legend><span class="dm-qn"><?php echo (int) $i + 1; ?></span><?php echo esc_html( $q['question'] ); ?></legend>
				<div class="dm-chips">
					<?php foreach ( $q['options'] as $o ) : ?>
						<button type="button" class="dm-chip" aria-pressed="false" data-score="<?php echo (int) $o[1]; ?>"><?php echo esc_html( $o[0] ); ?></button>
					<?php endforeach; ?>
				</div>
			</fieldset>
		<?php endforeach; ?>
	</div>
	<div class="dm-quiz-viz">
		<div class="dm-viz-card">
			<div class="dm-viz-top"><span>你的 AI 導入輪廓</span><span class="dm-num" data-progress>0 / <?php echo (int) $domingo_n; ?></span></div>
			<svg class="dm-radar" viewBox="-175 -140 350 275" role="img" aria-label="AI 導入輪廓雷達圖">
				<?php foreach ( array( 2.5, 5, 7.5, 10 ) as $ring ) : ?>
					<polygon class="dm-radar-ring" points="<?php echo esc_attr( domingo_radar_points( array_fill( 0, $domingo_n, $ring ), $domingo_r ) ); ?>"/>
				<?php endforeach; ?>
				<?php for ( $i = 0; $i < $domingo_n; $i++ ) : $p = domingo_radar_point( $i, $domingo_n, $domingo_r ); ?>
					<line class="dm-radar-axis" x1="0" y1="0" x2="<?php echo esc_attr( $p[0] ); ?>" y2="<?php echo esc_attr( $p[1] ); ?>"/>
				<?php endfor; ?>
				<polygon class="dm-radar-ghost" points="<?php echo esc_attr( domingo_radar_points( array_slice( array_pad( $domingo_ghost, $domingo_n, 6 ), 0, $domingo_n ), $domingo_r ) ); ?>"/>
				<polygon class="dm-radar-shape" data-shape points="<?php echo esc_attr( domingo_radar_points( array_fill( 0, $domingo_n, 0 ), $domingo_r ) ); ?>"/>
				<g data-dots></g>
				<?php foreach ( $domingo_quiz as $i => $q ) : $p = domingo_radar_point( $i, $domingo_n, $domingo_r + 20 ); $anchor = abs( $p[0] ) < 8 ? 'middle' : ( $p[0] > 0 ? 'start' : 'end' ); ?>
					<text class="dm-radar-label" x="<?php echo esc_attr( $p[0] ); ?>" y="<?php echo esc_attr( $p[1] + 4 ); ?>" text-anchor="<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $q['axis'] ); ?></text>
				<?php endforeach; ?>
			</svg>
			<div class="dm-legend"><span><i class="is-lime"></i>你的回答</span><span><i class="is-dash"></i>範例輪廓</span></div>
			<div class="dm-quiz-wait" data-wait><p>還差 <span data-left><?php echo (int) $domingo_n; ?></span> 題就能看到分數。</p></div>
			<div class="dm-quiz-result" data-result hidden aria-live="polite">
				<?php echo domingo_gauge_svg( 0, '導入適性分數' ); // phpcs:ignore ?>
				<p class="dm-quiz-level">適性：<b data-level></b></p>
				<p class="dm-quiz-advice" data-advice></p>
				<div class="dm-quiz-btns">
					<a class="dm-btn" data-book href="<?php echo esc_url( $domingo_book ); ?>">帶著結果預約評估 →</a>
					<button type="button" class="dm-btn-ghost" data-reset>重測</button>
				</div>
			</div>
		</div>
	</div>
</div>
