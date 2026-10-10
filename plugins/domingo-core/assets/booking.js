/* 多明哥 AI 筆記：4 步驟預約表單。時段向 /wp-json/domingo/v1/slots 即時讀取，送出到 /bookings。 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-booking-form]' );
	if ( ! root ) {
		return;
	}

	var api = root.getAttribute( 'data-api' );
	var form = root.querySelector( '[data-form]' );
	var steps = Array.prototype.slice.call( root.querySelectorAll( '[data-step]' ) );
	var dots = Array.prototype.slice.call( root.querySelectorAll( '[data-dot]' ) );
	var prevBtn = root.querySelector( '[data-prev]' );
	var nextBtn = root.querySelector( '[data-next]' );
	var submitBtn = root.querySelector( '[data-submit]' );
	var errEl = root.querySelector( '[data-err]' );
	var datesEl = root.querySelector( '[data-dates]' );
	var timesWrap = root.querySelector( '[data-times-wrap]' );
	var timesEl = root.querySelector( '[data-times]' );
	var timesLabel = root.querySelector( '[data-times-label]' );
	var slotsMsg = root.querySelector( '[data-slots-msg]' );
	var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var WEEK = [ '日', '一', '二', '三', '四', '五', '六' ];
	var FIELD_STEP = { size: 1, industry: 1, needs: 2, ai_level: 2, name: 3, company: 3, email: 3, phone: 3, consent: 3, slot: 4 };

	var step = 1;
	var token = '';
	var days = null;
	var loading = null;

	/* ---------- 從診斷帶來的分數 ---------- */
	var params = new URLSearchParams( window.location.search );
	var quiz = params.get( 'quiz' ) || '';
	var score = params.get( 'score' );
	if ( /^\d{1,2}(-\d{1,2}){1,9}$/.test( quiz ) && score !== null && /^\d{1,3}$/.test( score ) ) {
		score = Math.min( 100, parseInt( score, 10 ) );
		var box = root.querySelector( '[data-score]' );
		box.querySelector( '[data-score-num]' ).textContent = score;
		box.querySelector( '[data-score-level]' ).textContent = score >= 70 ? '高' : score >= 50 ? '中' : '待準備';
		box.hidden = false;
	} else {
		quiz = '';
		score = '';
	}

	/* ---------- 時段 ---------- */
	function loadSlots() {
		if ( loading ) {
			return loading;
		}
		slotsMsg.hidden = false;
		slotsMsg.textContent = '正在讀取可預約的時段…';
		loading = fetch( api + 'slots?_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( r.status );
				}
				return r.json();
			} )
			.then( function ( data ) {
				token = data.token;
				days = data.days || [];
				renderDates();
			} )
			.catch( function () {
				days = null;
				slotsMsg.hidden = false;
				slotsMsg.textContent = '時段讀取失敗，請重新整理頁面再試一次。';
			} )
			.then( function () {
				loading = null;
			} );
		return loading;
	}

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) {
			n.className = cls;
		}
		if ( text ) {
			n.textContent = text;
		}
		return n;
	}

	function renderDates() {
		var picked = form.querySelector( 'input[name="date"]:checked' );
		var keep = picked ? picked.value : '';
		datesEl.textContent = '';
		timesEl.textContent = '';
		timesWrap.hidden = true;
		if ( ! days.length ) {
			slotsMsg.hidden = false;
			slotsMsg.textContent = '近期的時段都滿了。可以直接寫信給我，我們另外約時間。';
			return;
		}
		slotsMsg.hidden = true;
		days.forEach( function ( d ) {
			var p = d.date.split( '-' );
			var dt = new Date( +p[ 0 ], +p[ 1 ] - 1, +p[ 2 ] );
			var label = el( 'label', 'dm-date' );
			var input = el( 'input' );
			input.type = 'radio';
			input.name = 'date';
			input.value = d.date;
			var span = el( 'span' );
			span.appendChild( el( 'small', '', '週' + WEEK[ dt.getDay() ] ) );
			span.appendChild( el( 'b', 'dm-num', ( dt.getMonth() + 1 ) + '/' + dt.getDate() ) );
			span.appendChild( el( 'em', '', d.times.length + ' 個時段' ) );
			label.appendChild( input );
			label.appendChild( span );
			input.addEventListener( 'change', function () {
				renderTimes( d );
			} );
			datesEl.appendChild( label );
			if ( d.date === keep ) {
				input.checked = true;
				renderTimes( d );
			}
		} );
	}

	function renderTimes( d ) {
		timesEl.textContent = '';
		timesLabel.textContent = d.label + ' 可以的時間';
		d.times.forEach( function ( t ) {
			var label = el( 'label', 'dm-pick' );
			var input = el( 'input' );
			input.type = 'radio';
			input.name = 'slot';
			input.value = d.date + ' ' + t;
			label.appendChild( input );
			label.appendChild( el( 'span', 'dm-num', t ) );
			timesEl.appendChild( label );
		} );
		timesWrap.hidden = false;
		clearError();
	}

	/* ---------- 檢查 ---------- */
	function val( name ) {
		var f = form.elements[ name ];
		return f ? String( f.value || '' ).trim() : '';
	}
	function checked( name ) {
		return Array.prototype.slice.call( form.querySelectorAll( 'input[name="' + name + '"]:checked' ) ).map( function ( i ) {
			return i.value;
		} );
	}

	function validate( s ) {
		if ( s === 1 ) {
			if ( ! checked( 'size' ).length ) {
				return [ 'size', '請選公司規模。' ];
			}
			if ( ! checked( 'industry' ).length ) {
				return [ 'industry', '請選產業。' ];
			}
		}
		if ( s === 2 && ! checked( 'needs' ).length ) {
			return [ 'needs', '請至少選一個想解決的問題。' ];
		}
		if ( s === 3 ) {
			if ( ! val( 'name' ) ) {
				return [ 'name', '請填姓名。' ];
			}
			if ( ! val( 'company' ) ) {
				return [ 'company', '請填公司名稱。' ];
			}
			if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( val( 'email' ) ) ) {
				return [ 'email', 'Email 格式好像不對，請再檢查一次。' ];
			}
			if ( ! form.elements.consent.checked ) {
				return [ 'consent', '請勾選同意，我才能用這些資料跟你聯絡。' ];
			}
		}
		if ( s === 4 && ! checked( 'slot' ).length ) {
			return [ 'slot', checked( 'date' ).length ? '請選一個時間。' : '請先選日期，再選時間。' ];
		}
		return null;
	}

	function clearError() {
		errEl.textContent = '';
		root.querySelectorAll( '[data-invalid]' ).forEach( function ( n ) {
			n.removeAttribute( 'data-invalid' );
		} );
		root.querySelectorAll( '[aria-invalid]' ).forEach( function ( n ) {
			n.removeAttribute( 'aria-invalid' );
		} );
	}

	function showError( field, msg ) {
		clearError();
		errEl.textContent = msg;
		var wrap = root.querySelector( '[data-field="' + field + '"]' );
		if ( ! wrap ) {
			return;
		}
		wrap.setAttribute( 'data-invalid', '' );
		var input = wrap.querySelector( 'input:not([type="hidden"]), textarea' );
		if ( input ) {
			if ( input.type !== 'radio' && input.type !== 'checkbox' ) {
				input.setAttribute( 'aria-invalid', 'true' );
			}
			input.focus( { preventScroll: false } );
		}
	}

	/* ---------- 換步驟 ---------- */
	function go( n ) {
		step = n;
		steps.forEach( function ( s ) {
			s.hidden = +s.getAttribute( 'data-step' ) !== n;
		} );
		dots.forEach( function ( d ) {
			var k = +d.getAttribute( 'data-dot' );
			d.classList.toggle( 'is-done', k < n );
			if ( k === n ) {
				d.setAttribute( 'aria-current', 'step' );
			} else {
				d.removeAttribute( 'aria-current' );
			}
		} );
		prevBtn.hidden = n === 1;
		nextBtn.hidden = n === steps.length;
		submitBtn.hidden = n !== steps.length;
		clearError();
		if ( n === 4 && days === null ) {
			loadSlots();
		}
		var top = root.getBoundingClientRect().top;
		if ( top < 0 ) {
			root.scrollIntoView( { behavior: reduced ? 'auto' : 'smooth', block: 'start' } );
		}
		var h = steps[ n - 1 ].querySelector( '.dm-step-h' );
		if ( h ) {
			h.focus( { preventScroll: true } );
		}
	}

	function next() {
		var e = validate( step );
		if ( e ) {
			showError( e[ 0 ], e[ 1 ] );
			return;
		}
		go( step + 1 );
	}

	nextBtn.addEventListener( 'click', next );
	prevBtn.addEventListener( 'click', function () {
		go( step - 1 );
	} );

	/* 選了就清掉錯誤訊息 */
	form.addEventListener( 'change', function ( e ) {
		var wrap = e.target.closest( '[data-invalid]' );
		if ( wrap ) {
			clearError();
		}
	} );

	/* ---------- 送出 ---------- */
	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		if ( step < steps.length ) {
			next();
			return;
		}
		for ( var s = 1; s <= steps.length; s++ ) {
			var err = validate( s );
			if ( err ) {
				if ( s !== step ) {
					go( s );
				}
				showError( err[ 0 ], err[ 1 ] );
				return;
			}
		}

		var payload = {
			size: checked( 'size' )[ 0 ],
			industry: checked( 'industry' )[ 0 ],
			needs: checked( 'needs' ),
			ai_level: checked( 'ai_level' )[ 0 ] || '',
			note: val( 'note' ),
			name: val( 'name' ),
			company: val( 'company' ),
			email: val( 'email' ),
			phone: val( 'phone' ),
			consent: form.elements.consent.checked,
			slot: checked( 'slot' )[ 0 ],
			website: val( 'website' ),
			token: token,
			quiz: quiz,
			score: score,
		};

		submitBtn.disabled = true;
		submitBtn.textContent = '送出中…';

		fetch( api + 'bookings', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( payload ),
		} )
			.then( function ( r ) {
				return r.json().catch( function () {
					return { ok: false, message: '系統暫時沒有回應，請稍後再試。' };
				} );
			} )
			.then( function ( res ) {
				if ( res && res.ok ) {
					done( res );
					return;
				}
				res = res || {};
				if ( res.code === 'taken' || res.code === 'busy' || res.code === 'token' ) {
					days = null;
					loadSlots().then( function () {
						showError( 'slot', res.code === 'token' ? '頁面停留比較久，時段已經更新，請再選一次時間後送出。' : res.message );
					} );
					return;
				}
				if ( res.field && FIELD_STEP[ res.field ] ) {
					go( FIELD_STEP[ res.field ] );
					showError( res.field, res.message );
					return;
				}
				errEl.textContent = res.message || '送出失敗，請稍後再試。';
			} )
			.catch( function () {
				errEl.textContent = '網路好像斷了，請確認連線後再按一次。';
			} )
			.then( function () {
				submitBtn.disabled = false;
				submitBtn.textContent = '確認預約';
			} );
	} );

	function done( res ) {
		form.hidden = true;
		root.querySelector( '.dm-steps' ).hidden = true;
		root.querySelector( '[data-score]' ).hidden = true;
		var box = root.querySelector( '[data-done]' );
		box.querySelector( '[data-done-slot]' ).textContent = res.slot || '';
		box.querySelector( '[data-done-email]' ).textContent = res.email || '';
		box.hidden = false;
		box.focus();
		root.scrollIntoView( { behavior: reduced ? 'auto' : 'smooth', block: 'start' } );
		root.dispatchEvent( new CustomEvent( 'domingo:booked', { bubbles: true, detail: { slot: res.slot } } ) );
		if ( window.dataLayer && window.dataLayer.push ) {
			window.dataLayer.push( { event: 'booking_submitted' } );
		}
	}

	/* 一進頁面就先讀時段，到第 4 步時不用等 */
	loadSlots();
} )();
