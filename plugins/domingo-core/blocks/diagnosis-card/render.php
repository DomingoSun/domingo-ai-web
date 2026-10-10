<?php
/**
 * 首頁 Hero 右側的 AI 適性診斷報告卡（範例畫面）。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

$domingo_bars = array(
	array( '流程重複度', 8 ),
	array( '規則清楚度', 6 ),
	array( '資料可取得', 7 ),
);

/* 走勢線：與 front.js 同一個公式，伺服器先畫好第一格，畫面一打開就完整 */
$domingo_w      = 300;
$domingo_h      = 70;
$domingo_series = array();
for ( $k = 0; $k < 24; $k++ ) {
	$domingo_series[] = 4 + round( 3 * sin( $k / 2.3 ) + 2 * sin( $k / 1.1 ) + ( ( $k * 7 ) % 5 ) / 2 );
}
$domingo_pts = array();
foreach ( $domingo_series as $i => $v ) {
	$domingo_pts[] = array( round( $i / 23 * $domingo_w, 1 ), round( $domingo_h - ( $v / 12 ) * ( $domingo_h - 6 ) - 3, 1 ) );
}
$domingo_line = '';
foreach ( $domingo_pts as $i => $p ) {
	$domingo_line .= ( $i ? 'L' : 'M' ) . $p[0] . ',' . $p[1] . ' ';
}
$domingo_last = end( $domingo_pts );
$domingo_log  = array(
	array( '09:21', '回覆 LINE 詢價' ),
	array( '09:18', '產生報價單草稿' ),
	array( '09:15', '轉給業務：客製需求' ),
	array( '09:12', '回答營業時間' ),
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'dm-dash' ) ); // phpcs:ignore ?>>
	<div class="dm-dash-head"><span>AI 適性診斷報告</span><span class="dm-tag">範例</span></div>
	<div class="dm-dash-grid">
		<div class="dm-dash-cell"><?php echo domingo_gauge_svg( 78, '導入適性分數' ); // phpcs:ignore ?></div>
		<div class="dm-dash-cell">
			<div class="dm-bars">
				<?php foreach ( $domingo_bars as $b ) : ?>
					<div class="dm-bar">
						<div class="dm-bar-row"><span><?php echo esc_html( $b[0] ); ?></span><span class="dm-num"><?php echo (int) $b[1]; ?> / 10</span></div>
						<div class="dm-bar-track"><i style="width:<?php echo (int) $b[1] * 10; ?>%" data-w="<?php echo (int) $b[1] * 10; ?>"></i></div>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="dm-dash-rec">建議先做：<b>LINE 詢價分流</b></p>
		</div>
	</div>
	<div class="dm-feed" data-feed>
		<div class="dm-feed-head"><span class="dm-live-dot" aria-hidden="true"></span>數位員工處理中<span class="dm-tag">示意畫面</span></div>
		<svg class="dm-spark" viewBox="0 0 <?php echo (int) $domingo_w; ?> <?php echo (int) $domingo_h; ?>" preserveAspectRatio="none" role="img" aria-label="每分鐘處理件數示意走勢">
			<path class="dm-spark-area" d="<?php echo esc_attr( trim( $domingo_line ) . " L{$domingo_w},{$domingo_h} L0,{$domingo_h} Z" ); ?>"/>
			<path class="dm-spark-line" d="<?php echo esc_attr( trim( $domingo_line ) ); ?>"/>
			<circle class="dm-spark-end" cx="<?php echo esc_attr( $domingo_last[0] - 3 ); ?>" cy="<?php echo esc_attr( $domingo_last[1] ); ?>" r="4"/>
		</svg>
		<ul class="dm-feed-log">
			<?php foreach ( $domingo_log as $i => $l ) : ?>
				<li<?php echo 0 === $i ? ' class="is-fresh"' : ''; ?>><span class="dm-num"><?php echo esc_html( $l[0] ); ?></span><?php echo esc_html( $l[1] ); ?><span class="dm-ok">完成</span></li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
