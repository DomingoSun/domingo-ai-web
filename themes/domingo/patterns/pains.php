<?php
/**
 * Title: 痛點卡片
 * Slug: domingo/pains
 * Categories: domingo
 */
$domingo_pains = array(
	array( 'LINE 詢問回不完', '同樣的價格、規格、營業時間，一天回幾十次。晚上的訊息隔天才回，客人早就問別家。' ),
	array( '報價單一直重做', '產品表在 Excel、條件在業務腦中，每張報價都要人工對一次，一忙就出錯。' ),
	array( '新人什麼都要問', 'SOP 散在群組和雲端資料夾，老員工一天被打斷十幾次，新人還是學很慢。' ),
	array( '會議開完沒下文', '討論很熱烈，會後沒人整理重點和待辦，下週又從頭講一遍。' ),
	array( '上過 AI 課，回公司沒用上', '課堂上很厲害，回到公司不知道套在哪個流程，最後又回到老方法。' ),
);
?>
<!-- wp:group {"tagName":"section","className":"dm-section dm-light","layout":{"type":"constrained"}} -->
<section class="wp-block-group dm-section dm-light"><!-- wp:heading {"textAlign":"center","className":"dm-sec-h"} -->
<h2 class="wp-block-heading has-text-align-center dm-sec-h">你是不是也卡在這幾件事？</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"dm-sec-sub"} -->
<p class="has-text-align-center dm-sec-sub">多數老闆不是不想用 AI，而是不知道第一步該放在哪。</p>
<!-- /wp:paragraph -->

<!-- wp:group {"align":"wide","className":"dm-cards","layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
<div class="wp-block-group alignwide dm-cards"><?php foreach ( $domingo_pains as $p ) : ?><!-- wp:group {"className":"dm-card","layout":{"type":"default"}} -->
<div class="wp-block-group dm-card"><!-- wp:heading {"level":3,"className":"dm-card-h"} -->
<h3 class="wp-block-heading dm-card-h"><?php echo esc_html( $p[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"dm-card-p"} -->
<p class="dm-card-p"><?php echo esc_html( $p[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<?php endforeach; ?><!-- wp:group {"className":"dm-card is-lime","layout":{"type":"default"}} -->
<div class="wp-block-group dm-card is-lime"><!-- wp:heading {"level":3,"className":"dm-card-h"} -->
<h3 class="wp-block-heading dm-card-h">中了兩項以上？</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"dm-card-p"} -->
<p class="dm-card-p">代表你的公司已經有適合自動化的流程。先花 30 分鐘，看清楚從哪一個開始。</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-dark"} -->
<div class="wp-block-button is-style-dark"><a class="wp-block-button__link wp-element-button" href="/booking/">預約免費評估 →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
