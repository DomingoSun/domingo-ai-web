/* 多明哥 AI 筆記：互動區塊的前台動畫與診斷計算。內容已由伺服器畫好，這裡只負責「動起來」。 */
( function () {
	'use strict';

	var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var NS = 'http://www.w3.org/2000/svg';

	function onVisible( el, fn ) {
		if ( reduced || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		var io = new IntersectionObserver( function ( entries ) {
			if ( entries.some( function ( e ) { return e.isIntersecting; } ) ) {
				io.disconnect();
				fn();
			}
		}, { threshold: 0.2 } );
		io.observe( el );
	}

	function tween( from, to, dur, step ) {
		var t0 = performance.now();
		function f( t ) {
			var p = Math.min( 1, ( t - t0 ) / dur );
			var e = 1 - Math.pow( 1 - p, 3 );
			step( from + ( to - from ) * e );
			if ( p < 1 ) {
				requestAnimationFrame( f );
			}
		}
		requestAnimationFrame( f );
	}

	/* 儀表：從 0 長到目標值 */
	function setGauge( svg, value, animate ) {
		var fill = svg.querySelector( '.dm-gauge-fill' );
		var num = svg.querySelector( '.dm-gauge-num' );
		var label = svg.getAttribute( 'aria-label' ) || '';
		svg.setAttribute( 'aria-label', label.replace( /\d+ 分$/, value + ' 分' ) );
		if ( ! animate || reduced ) {
			fill.style.strokeDashoffset = 100 - value;
			num.textContent = value;
			return;
		}
		tween( 0, value, 1200, function ( v ) {
			fill.style.strokeDashoffset = 100 - v;
			num.textContent = Math.round( v );
		} );
	}

	/* ---------- 診斷報告卡 ---------- */
	document.querySelectorAll( '.dm-dash' ).forEach( function ( card ) {
		var gauge = card.querySelector( '.dm-gauge' );
		var bars = card.querySelectorAll( '.dm-bar-track i' );
		onVisible( card, function () {
			var target = parseInt( gauge.querySelector( '.dm-gauge-fill' ).getAttribute( 'data-value' ), 10 ) || 0;
			setGauge( gauge, target, true );
			bars.forEach( function ( b ) {
				var w = parseInt( b.getAttribute( 'data-w' ), 10 ) || 0;
				b.style.width = '0%';
				requestAnimationFrame( function () {
					b.style.transition = 'width 1s cubic-bezier(.2,.7,.2,1)';
					b.style.width = w + '%';
				} );
			} );
		} );

		var feed = card.querySelector( '[data-feed]' );
		if ( ! feed || reduced ) {
			return;
		}
		var line = feed.querySelector( '.dm-spark-line' );
		var area = feed.querySelector( '.dm-spark-area' );
		var end = feed.querySelector( '.dm-spark-end' );
		var log = feed.querySelector( '.dm-feed-log' );
		var EVENTS = [ '回覆 LINE 詢價', '產生報價單草稿', '轉給業務：客製需求', '回答營業時間', '寫入客戶名單', '整理會議待辦', '回答新人 SOP 問題' ];
		var W = 300, H = 70, tick = 0;
		var minutes = 9 * 60 + 21;

		function draw() {
			var d = '';
			var last;
			for ( var i = 0; i < 24; i++ ) {
				var k = i + tick;
				var v = 4 + Math.round( 3 * Math.sin( k / 2.3 ) + 2 * Math.sin( k / 1.1 ) + ( ( k * 7 ) % 5 ) / 2 );
				var x = ( i / 23 ) * W;
				var y = H - ( v / 12 ) * ( H - 6 ) - 3;
				d += ( i ? 'L' : 'M' ) + x.toFixed( 1 ) + ',' + y.toFixed( 1 ) + ' ';
				last = [ x, y ];
			}
			line.setAttribute( 'd', d.trim() );
			area.setAttribute( 'd', d.trim() + ' L' + W + ',' + H + ' L0,' + H + ' Z' );
			end.setAttribute( 'cx', last[ 0 ] - 3 );
			end.setAttribute( 'cy', last[ 1 ] );
		}

		function pushLog() {
			minutes += 3;
			var hh = String( Math.floor( minutes / 60 ) % 24 ).padStart( 2, '0' );
			var mm = String( minutes % 60 ).padStart( 2, '0' );
			var li = document.createElement( 'li' );
			li.className = 'is-fresh';
			var t = document.createElement( 'span' );
			t.className = 'dm-num';
			t.textContent = hh + ':' + mm;
			var ok = document.createElement( 'span' );
			ok.className = 'dm-ok';
			ok.textContent = '完成';
			li.appendChild( t );
			li.appendChild( document.createTextNode( EVENTS[ ( tick + 3 ) % EVENTS.length ] ) );
			li.appendChild( ok );
			log.querySelectorAll( '.is-fresh' ).forEach( function ( n ) { n.classList.remove( 'is-fresh' ); } );
			log.insertBefore( li, log.firstChild );
			while ( log.children.length > 4 ) {
				log.removeChild( log.lastChild );
			}
		}

		var timer = null;
		function start() {
			if ( timer ) {
				return;
			}
			timer = setInterval( function () {
				if ( document.hidden ) {
					return;
				}
				tick++;
				draw();
				pushLog();
			}, 1600 );
		}
		onVisible( feed, start );
	} );

	/* ---------- 1 分鐘 AI 適性診斷 ---------- */
	document.querySelectorAll( '[data-quiz]' ).forEach( function ( root ) {
		var qs = Array.prototype.slice.call( root.querySelectorAll( '.dm-quiz-q' ) );
		var n = qs.length;
		var R = 104;
		var answers = new Array( n ).fill( -1 );
		var shape = root.querySelector( '[data-shape]' );
		var dots = root.querySelector( '[data-dots]' );
		var progress = root.querySelector( '[data-progress]' );
		var left = root.querySelector( '[data-left]' );
		var wait = root.querySelector( '[data-wait]' );
		var result = root.querySelector( '[data-result]' );
		var levelEl = root.querySelector( '[data-level]' );
		var adviceEl = root.querySelector( '[data-advice]' );
		var book = root.querySelector( '[data-book]' );
		var bookBase = root.getAttribute( 'data-booking' ) || '/booking/';
		var current = new Array( n ).fill( 0 );

		function point( i, r ) {
			var a = ( -90 + ( 360 / n ) * i ) * Math.PI / 180;
			return [ Math.cos( a ) * r, Math.sin( a ) * r ];
		}

		function render( vals ) {
			shape.setAttribute( 'points', vals.map( function ( v, i ) {
				var p = point( i, ( v / 10 ) * R );
				return p[ 0 ].toFixed( 2 ) + ',' + p[ 1 ].toFixed( 2 );
			} ).join( ' ' ) );
			while ( dots.firstChild ) {
				dots.removeChild( dots.firstChild );
			}
			vals.forEach( function ( v, i ) {
				if ( v <= 0.05 ) {
					return;
				}
				var p = point( i, ( v / 10 ) * R );
				var c = document.createElementNS( NS, 'circle' );
				c.setAttribute( 'cx', p[ 0 ].toFixed( 2 ) );
				c.setAttribute( 'cy', p[ 1 ].toFixed( 2 ) );
				c.setAttribute( 'r', '4.5' );
				c.setAttribute( 'class', 'dm-radar-dot' );
				dots.appendChild( c );
			} );
		}

		function animateTo( target ) {
			if ( reduced ) {
				current = target.slice();
				render( current );
				return;
			}
			var from = current.slice();
			tween( 0, 1, 500, function ( p ) {
				current = from.map( function ( f, i ) { return f + ( target[ i ] - f ) * p; } );
				render( current );
			} );
		}

		function update() {
			var scores = answers.map( function ( a, i ) {
				if ( a < 0 ) {
					return 0;
				}
				return parseInt( qs[ i ].querySelectorAll( '.dm-chip' )[ a ].getAttribute( 'data-score' ), 10 ) || 0;
			} );
			var done = answers.filter( function ( a ) { return a >= 0; } ).length;
			progress.textContent = done + ' / ' + n;
			left.textContent = n - done;
			animateTo( scores );

			if ( done === n ) {
				var total = Math.round( scores.reduce( function ( x, y ) { return x + y; }, 0 ) / ( n * 10 ) * 100 );
				var level = total >= 70 ? '高' : total >= 50 ? '中' : '待準備';
				var adv = total >= 70 ? 'data-advice-high' : total >= 50 ? 'data-advice-mid' : 'data-advice-low';
				levelEl.textContent = level;
				adviceEl.textContent = root.getAttribute( adv ) || '';
				var url = new URL( bookBase, window.location.href );
				url.searchParams.set( 'quiz', scores.join( '-' ) );
				url.searchParams.set( 'score', total );
				book.setAttribute( 'href', url.toString() );
				wait.hidden = true;
				var wasHidden = result.hidden;
				result.hidden = false;
				setGauge( result.querySelector( '.dm-gauge' ), total, wasHidden );
			} else {
				wait.hidden = false;
				result.hidden = true;
			}
		}

		qs.forEach( function ( fs, qi ) {
			fs.querySelectorAll( '.dm-chip' ).forEach( function ( btn, oi ) {
				btn.addEventListener( 'click', function () {
					answers[ qi ] = oi;
					fs.querySelectorAll( '.dm-chip' ).forEach( function ( b, j ) {
						b.setAttribute( 'aria-pressed', j === oi ? 'true' : 'false' );
					} );
					update();
				} );
			} );
		} );

		root.querySelector( '[data-reset]' ).addEventListener( 'click', function () {
			answers = new Array( n ).fill( -1 );
			root.querySelectorAll( '.dm-chip' ).forEach( function ( b ) { b.setAttribute( 'aria-pressed', 'false' ); } );
			update();
			qs[ 0 ].querySelector( '.dm-chip' ).focus();
		} );
	} );
} )();
