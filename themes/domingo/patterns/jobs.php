<?php
/**
 * Title: 數位員工可以接手的工作
 * Slug: domingo/jobs
 * Categories: domingo
 */
$domingo_jobs = array(
	array( 'LINE 詢問分流', '常見問題先回，需要真人才轉給同事' ),
	array( '報價單自動產生', '依產品表與規則算好，業務確認後寄出' ),
	array( '內部知識小幫手', '新人問 SOP，直接在 LINE 得到答案' ),
	array( '會議紀錄與待辦', '會後整理重點，分派負責人與期限' ),
	array( '報表自動彙整', '各處資料每天自動整理成一張表' ),
);
?>
<!-- wp:group {"tagName":"section","anchor":"jobs","className":"dm-section dm-light","layout":{"type":"constrained"}} -->
<section id="jobs" class="wp-block-group dm-section dm-light"><!-- wp:paragraph {"align":"center","className":"dm-eyebrow"} -->
<p class="has-text-align-center dm-eyebrow">DIGITAL EMPLOYEES</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","className":"dm-sec-h"} -->
<h2 class="wp-block-heading has-text-align-center dm-sec-h">數位員工可以接手的工作</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"dm-sec-sub"} -->
<p class="has-text-align-center dm-sec-sub">需要人判斷的時候，才會叫你。</p>
<!-- /wp:paragraph -->

<!-- wp:group {"align":"wide","className":"dm-jobs","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group alignwide dm-jobs"><?php foreach ( $domingo_jobs as $j ) : ?><!-- wp:group {"className":"dm-job","layout":{"type":"default"}} -->
<div class="wp-block-group dm-job"><!-- wp:heading {"level":3,"textAlign":"center","className":"dm-job-h"} -->
<h3 class="wp-block-heading has-text-align-center dm-job-h"><?php echo esc_html( $j[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"dm-job-p"} -->
<p class="has-text-align-center dm-job-p"><?php echo esc_html( $j[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
