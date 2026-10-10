<?php
/**
 * 工具跑馬燈＋產業標籤雙向跑馬燈。內容在後台「網站設定」修改。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

$domingo_s     = domingo_settings();
$domingo_tools = domingo_tools();
$domingo_a     = domingo_industries( 'ind_a' );
$domingo_b     = domingo_industries( 'ind_b' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'dm-marquees' ) ); // phpcs:ignore ?>>
	<?php if ( $domingo_tools ) : ?>
		<section class="dm-logos" aria-label="<?php echo esc_attr( $domingo_s['tools_label'] ); ?>">
			<p class="dm-mq-label"><?php echo esc_html( $domingo_s['tools_label'] ); ?></p>
			<div class="dm-mq">
				<div class="dm-mq-track is-left">
					<?php foreach ( array( 0, 1 ) as $copy ) : foreach ( $domingo_tools as $t ) : ?>
						<span class="dm-logo-pill"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>><span class="dm-logo-icon" style="background:<?php echo esc_attr( $t['color'] ); ?>"><?php echo esc_html( $t['abbr'] ); ?></span><?php echo esc_html( $t['name'] ); ?></span>
					<?php endforeach; endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php if ( $domingo_a || $domingo_b ) : ?>
		<section class="dm-tagband" aria-label="<?php echo esc_attr( $domingo_s['ind_label'] ); ?>">
			<p class="dm-mq-label"><?php echo esc_html( $domingo_s['ind_label'] ); ?></p>
			<?php foreach ( array( array( $domingo_a, 'is-left' ), array( $domingo_b, 'is-right' ) ) as $row ) : if ( ! $row[0] ) { continue; } ?>
				<div class="dm-mq">
					<div class="dm-mq-track <?php echo esc_attr( $row[1] ); ?>">
						<?php foreach ( array( 0, 1 ) as $copy ) : foreach ( $row[0] as $t ) : ?>
							<span class="dm-tagpill<?php echo $t['hot'] ? ' is-hot' : ''; ?>"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html( $t['name'] ); ?></span>
						<?php endforeach; endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>
</div>
