/* 多明哥 AI 筆記：手機底部固定預約鈕。首屏按鈕滑出畫面後出現，到頁尾或預約頁就隱藏。 */
( function () {
	'use strict';
	var bar = document.querySelector( '.dm-mcta' );
	if ( ! bar ) {
		return;
	}
	if ( document.querySelector( '.dm-booking' ) ) {
		bar.parentNode.removeChild( bar );
		return;
	}
	var target = document.querySelector( '.dm-hero-btns' ) || document.querySelector( 'main h1' );
	var footer = document.querySelector( '.dm-footer' );
	var quiz = document.querySelector( '[data-quiz]' );
	var passed = ! target;
	var atFooter = false;
	var inQuiz = false;
	var links = bar.querySelectorAll( 'a' );

	function render() {
		var on = passed && ! atFooter && ! inQuiz;
		bar.classList.toggle( 'is-on', on );
		bar.setAttribute( 'aria-hidden', on ? 'false' : 'true' );
		for ( var i = 0; i < links.length; i++ ) {
			links[ i ].tabIndex = on ? 0 : -1;
		}
	}

	if ( 'IntersectionObserver' in window ) {
		if ( target ) {
			new IntersectionObserver( function ( e ) {
				/* 只在按鈕已經滑到畫面上方時才算「經過」 */
				passed = ! e[ 0 ].isIntersecting && e[ 0 ].boundingClientRect.top < 0;
				render();
			} ).observe( target );
		}
		if ( footer ) {
			new IntersectionObserver( function ( e ) {
				atFooter = e[ 0 ].isIntersecting;
				render();
			} ).observe( footer );
		}
		/* 做診斷時先收起來，讓客戶專心答題（診斷結果有自己的預約按鈕） */
		if ( quiz ) {
			new IntersectionObserver( function ( e ) {
				inQuiz = e[ 0 ].isIntersecting;
				render();
			}, { rootMargin: '-30% 0px -30% 0px' } ).observe( quiz );
		}
	} else {
		passed = true;
	}
	render();
} )();
