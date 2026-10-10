<?php
/**
 * Title: 常見問題
 * Slug: domingo/faq
 * Categories: domingo
 */
$domingo_faqs = array(
	array( '評估真的免費嗎？會被推銷嗎？', '不收費。結束時若你想繼續，我會說明方案；不需要就到此為止，不會追著聯絡。' ),
	array( '我不懂技術，可以預約嗎？', '可以。評估只談你們每天在做什麼、資料放在哪、哪裡最卡。' ),
	array( '公司資料會外流嗎？', '評估階段不需要任何內部檔案。正式導入時會先說明資料放哪、誰看得到，再由你決定。' ),
	array( '為什麼要同事自己操作？', '系統最後是你們在用、在改。同事親手做過一次，之後調整或複製到下個部門都不必再請人。' ),
);
?>
<!-- wp:group {"tagName":"section","anchor":"faq","className":"dm-section dm-cream","layout":{"type":"constrained"}} -->
<section id="faq" class="wp-block-group dm-section dm-cream"><!-- wp:columns {"align":"wide","className":"dm-faq"} -->
<div class="wp-block-columns alignwide dm-faq"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"dm-sec-h"} -->
<h2 class="wp-block-heading dm-sec-h">預約前，<br>你可能想問</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"dm-sec-sub"} -->
<p class="dm-sec-sub">沒找到你的問題？寫在預約表單的備註，會談時優先回答。</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/booking/">預約免費評估 →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><?php foreach ( $domingo_faqs as $q ) : ?><!-- wp:details {"className":"dm-faq-item"} -->
<details class="wp-block-details dm-faq-item"><summary><?php echo esc_html( $q[0] ); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html( $q[1] ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<?php endforeach; ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->
