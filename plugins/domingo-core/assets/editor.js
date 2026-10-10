/* 讓區塊在編輯器裡顯示伺服器畫好的預覽。內容在「網站設定」與「評論」修改。 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks ) {
		return;
	}
	var el = wp.element.createElement;
	var SSR = wp.serverSideRender;
	var useBlockProps = wp.blockEditor.useBlockProps;

	[ 'diagnosis-card', 'quiz', 'marquees', 'testimonials', 'booking' ].forEach( function ( name ) {
		wp.blocks.registerBlockType( 'domingo/' + name, {
			edit: function ( props ) {
				return el(
					'div',
					useBlockProps( { className: 'dm-editor-preview' } ),
					el( SSR, { block: 'domingo/' + name, attributes: props.attributes } )
				);
			},
			save: function () {
				return null;
			},
		} );
	} );
} )( window.wp );
