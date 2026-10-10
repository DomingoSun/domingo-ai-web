<?php
/**
 * Title: 首頁主視覺
 * Slug: domingo/hero
 * Categories: domingo
 * Description: 左邊問句標題與按鈕，右邊為動態診斷報告卡（第 3 階段換成互動區塊）。
 */
?>
<!-- wp:group {"tagName":"section","className":"dm-hero","layout":{"type":"constrained"}} -->
<section class="wp-block-group dm-hero"><!-- wp:columns {"align":"wide","className":"dm-hero-cols"} -->
<div class="wp-block-columns alignwide dm-hero-cols"><!-- wp:column {"width":"55%","className":"dm-hero-copy"} -->
<div class="wp-block-column dm-hero-copy" style="flex-basis:55%"><!-- wp:paragraph {"className":"dm-badge"} -->
<p class="dm-badge">AI 導入適性診斷</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"dm-h1"} -->
<h1 class="wp-block-heading dm-h1">你的公司，<br>準備好讓 AI <mark class="has-inline-color has-lime-color">上班</mark>了嗎？</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"dm-hero-sub"} -->
<p class="dm-hero-sub">回答 5 題，馬上看到「AI 導入適性」結果。<br>想要完整解讀，再預約 30 分鐘免費線上評估。</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"dm-hero-btns"} -->
<div class="wp-block-buttons dm-hero-btns"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#quiz">開始 1 分鐘診斷 →</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-ghost"} -->
<div class="wp-block-button is-style-ghost"><a class="wp-block-button__link wp-element-button" href="/booking/">直接預約評估</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"45%","className":"dm-hero-visual"} -->
<div class="wp-block-column dm-hero-visual" style="flex-basis:45%"><!-- wp:domingo/diagnosis-card /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->
