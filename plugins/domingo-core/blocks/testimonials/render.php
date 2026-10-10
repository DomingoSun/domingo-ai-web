<?php
/**
 * 評論跑馬燈：讀取後台「評論」。沒有評論時前台不顯示任何東西。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

$domingo_items = domingo_testimonials();
$domingo_rest  = defined( 'REST_REQUEST' ) && REST_REQUEST;

if ( ! $domingo_items ) {
	if ( $domingo_rest ) {
		echo '<div class="dm-empty">還沒有評論。到後台左側「評論 → 新增評論」加入第一筆，這裡就會自動出現。</div>';
	}
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'dm-testi' ) ); // phpcs:ignore ?>>
	<h2 class="dm-testi-h">上過課、<wbr>導入過的人<wbr>怎麼說</h2>
	<div class="dm-mq is-light">
		<div class="dm-mq-track is-left is-slow">
			<?php foreach ( array( 0, 1 ) as $copy ) : foreach ( $domingo_items as $t ) : ?>
				<figure class="dm-testi-card"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>>
					<?php if ( $t['tag'] ) : ?><span class="dm-testi-tag"><?php echo esc_html( $t['tag'] ); ?></span><?php endif; ?>
					<blockquote><?php echo esc_html( $t['quote'] ); ?></blockquote>
					<figcaption><b><?php echo esc_html( $t['name'] ); ?></b><?php if ( $t['role'] ) : ?><span><?php echo esc_html( $t['role'] ); ?></span><?php endif; ?></figcaption>
				</figure>
			<?php endforeach; endforeach; ?>
		</div>
	</div>
</section>
