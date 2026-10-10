<?php
/**
 * 後台「評論」：新增一筆就會出現在首頁的評論跑馬燈。
 *
 * @package domingo-core
 */

defined( 'ABSPATH' ) || exit;

const DOMINGO_TESTIMONIAL_TAGS = array( '工作坊學員', '企業內訓', '陪跑導入' );

add_action(
	'init',
	function () {
		register_post_type(
			'dm_testimonial',
			array(
				'labels'              => array(
					'name'          => '評論',
					'singular_name' => '評論',
					'add_new'       => '新增評論',
					'add_new_item'  => '新增評論',
					'edit_item'     => '編輯評論',
					'all_items'     => '所有評論',
					'menu_name'     => '評論',
					'not_found'     => '還沒有評論',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-format-quote',
				'menu_position'       => 21,
				'supports'            => array( 'title', 'page-attributes' ),
				'enter_title_here'    => '填寫人的姓名',
			)
		);
	}
);

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'dm_testimonial' === $post->post_type ? '姓名（例如：王小明）' : $text;
	},
	10,
	2
);

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'dm_testimonial_fields', '評論內容', 'domingo_testimonial_box', 'dm_testimonial', 'normal', 'high' );
	}
);

/**
 * 評論欄位。
 */
function domingo_testimonial_box( $post ) {
	wp_nonce_field( 'dm_testimonial_save', 'dm_testimonial_nonce' );
	$quote = get_post_meta( $post->ID, '_dm_quote', true );
	$role  = get_post_meta( $post->ID, '_dm_role', true );
	$tag   = get_post_meta( $post->ID, '_dm_tag', true );
	?>
	<p><label for="dm-quote"><strong>回饋原文</strong>（請先取得本人同意）</label><br>
	<textarea id="dm-quote" name="dm_quote" rows="5" class="large-text"><?php echo esc_textarea( $quote ); ?></textarea></p>
	<p><label for="dm-role"><strong>職稱・產業</strong>（例如：業務經理・設備經銷）</label><br>
	<input id="dm-role" type="text" name="dm_role" class="regular-text" value="<?php echo esc_attr( $role ); ?>"></p>
	<p><label for="dm-tag"><strong>類別</strong></label><br>
	<select id="dm-tag" name="dm_tag">
		<?php foreach ( DOMINGO_TESTIMONIAL_TAGS as $t ) : ?>
			<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $tag, $t ); ?>><?php echo esc_html( $t ); ?></option>
		<?php endforeach; ?>
	</select></p>
	<p class="description">右側「屬性 → 順序」數字越小越前面。</p>
	<?php
}

add_action(
	'save_post_dm_testimonial',
	function ( $post_id ) {
		if ( ! isset( $_POST['dm_testimonial_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dm_testimonial_nonce'] ) ), 'dm_testimonial_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_dm_quote', sanitize_textarea_field( wp_unslash( $_POST['dm_quote'] ?? '' ) ) );
		update_post_meta( $post_id, '_dm_role', sanitize_text_field( wp_unslash( $_POST['dm_role'] ?? '' ) ) );
		$tag = sanitize_text_field( wp_unslash( $_POST['dm_tag'] ?? '' ) );
		update_post_meta( $post_id, '_dm_tag', in_array( $tag, DOMINGO_TESTIMONIAL_TAGS, true ) ? $tag : DOMINGO_TESTIMONIAL_TAGS[0] );
	}
);

/* 列表頁多顯示「類別」與「職稱」 */
add_filter(
	'manage_dm_testimonial_posts_columns',
	function ( $cols ) {
		return array(
			'cb'      => $cols['cb'],
			'title'   => '姓名',
			'dm_tag'  => '類別',
			'dm_role' => '職稱・產業',
			'date'    => $cols['date'],
		);
	}
);
add_action(
	'manage_dm_testimonial_posts_custom_column',
	function ( $col, $post_id ) {
		if ( 'dm_tag' === $col ) {
			echo esc_html( get_post_meta( $post_id, '_dm_tag', true ) );
		} elseif ( 'dm_role' === $col ) {
			echo esc_html( get_post_meta( $post_id, '_dm_role', true ) );
		}
	},
	10,
	2
);

/**
 * 取得要顯示的評論。
 */
function domingo_testimonials() {
	$q   = new WP_Query(
		array(
			'post_type'      => 'dm_testimonial',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'no_found_rows'  => true,
		)
	);
	$out = array();
	foreach ( $q->posts as $p ) {
		$quote = get_post_meta( $p->ID, '_dm_quote', true );
		if ( '' === trim( (string) $quote ) ) {
			continue;
		}
		$out[] = array(
			'name'  => get_the_title( $p ),
			'quote' => $quote,
			'role'  => get_post_meta( $p->ID, '_dm_role', true ),
			'tag'   => get_post_meta( $p->ID, '_dm_tag', true ),
		);
	}
	return $out;
}
