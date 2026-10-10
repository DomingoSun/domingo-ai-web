<?php
/**
 * Title: 三種合作方式
 * Slug: domingo/plans
 * Categories: domingo
 */
$domingo_plans = array(
	array(
		'label'    => '先看清楚',
		'name'     => 'AI 導入適性評估',
		'price'    => 'NT$0',
		'unit'     => '',
		'sub'      => '線上 30 分鐘，不需準備資料',
		'feats'    => array( '盤點最花時間的 3 件事', '排出最該先做的流程', '工具與月費級距建議', '不適合會直接告訴你' ),
		'cta'      => '預約免費評估',
		'url'      => '/booking/',
		'featured' => false,
	),
	array(
		'label'    => '第一位數位員工',
		'name'     => '14 天陪跑上線',
		'price'    => 'NT$24,800',
		'unit'     => '起 / 一個流程',
		'sub'      => '依實際導入的工作流程報價',
		'feats'    => array( '兩次工作會議，14 天上線', '同事親手做，我在旁邊帶', '交付流程說明與維護方式', '上線後 [天數] 天問題排除' ),
		'cta'      => '先預約評估',
		'url'      => '/booking/',
		'featured' => true,
	),
	array(
		'label'    => '全員上手',
		'name'     => '企業內訓與工作坊',
		'price'    => '專案報價',
		'unit'     => '',
		'sub'      => '半天或全天，依部門需求規劃',
		'feats'    => array( '依部門情境客製案例', '實作為主，課後就能用', '可延伸為種子講師培訓', '課後提供操作講義' ),
		'cta'      => '詢問內訓',
		'url'      => '/booking/',
		'featured' => false,
	),
);
?>
<!-- wp:group {"tagName":"section","anchor":"plans","className":"dm-section dm-glow","layout":{"type":"constrained"}} -->
<section id="plans" class="wp-block-group dm-section dm-glow"><!-- wp:paragraph {"align":"center","className":"dm-eyebrow is-lime"} -->
<p class="has-text-align-center dm-eyebrow is-lime">THREE WAYS</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","className":"dm-sec-h"} -->
<h2 class="wp-block-heading has-text-align-center dm-sec-h">三種合作方式，<wbr>從你需要的開始</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"dm-sec-sub"} -->
<p class="has-text-align-center dm-sec-sub">不用照順序走完。可以只做免費評估，也可以直接找我陪跑或內訓。</p>
<!-- /wp:paragraph -->

<!-- wp:group {"align":"wide","className":"dm-plans","layout":{"type":"grid","minimumColumnWidth":"17rem"}} -->
<div class="wp-block-group alignwide dm-plans"><?php foreach ( $domingo_plans as $p ) : $c = $p['featured'] ? 'dm-plan is-featured' : 'dm-plan'; ?><!-- wp:group {"className":"<?php echo esc_attr( $c ); ?>","layout":{"type":"default"}} -->
<div class="wp-block-group <?php echo esc_attr( $c ); ?>"><?php if ( $p['featured'] ) : ?><!-- wp:paragraph {"className":"dm-plan-badge"} -->
<p class="dm-plan-badge">最多人選擇</p>
<!-- /wp:paragraph -->

<?php endif; ?><!-- wp:paragraph {"className":"dm-plan-label"} -->
<p class="dm-plan-label"><?php echo esc_html( $p['label'] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"className":"dm-plan-name"} -->
<h3 class="wp-block-heading dm-plan-name"><?php echo esc_html( $p['name'] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"dm-plan-price"} -->
<p class="dm-plan-price"><?php echo esc_html( $p['price'] ); ?><?php if ( $p['unit'] ) : ?><small><?php echo esc_html( $p['unit'] ); ?></small><?php endif; ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"dm-plan-sub"} -->
<p class="dm-plan-sub"><?php echo esc_html( $p['sub'] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"dm-plan-feats"} -->
<ul class="wp-block-list dm-plan-feats"><?php foreach ( $p['feats'] as $f ) : ?><!-- wp:list-item -->
<li><?php echo esc_html( $f ); ?></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->

<!-- wp:buttons {"className":"dm-plan-cta"} -->
<div class="wp-block-buttons dm-plan-cta"><?php if ( $p['featured'] ) : ?><!-- wp:button -->
<div class="wp-block-button"><?php else : ?><!-- wp:button {"className":"is-style-ghost"} -->
<div class="wp-block-button is-style-ghost"><?php endif; ?><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['cta'] ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group -->

<!-- wp:paragraph {"align":"center","className":"dm-fineprint"} -->
<p class="has-text-align-center dm-fineprint">方括號為待確認內容，上線前替換。價格未含第三方工具月費。</p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->
