/* 預約頁：進來一律停在最上方，而且第一個畫面就是頂部（不從底部滑上來）。
   有些瀏覽器或 App 會沿用上一頁的捲動位置；這段在頁面畫出來之前就執行，
   前 1.5 秒內若被捲離頂部就立刻（不加動畫）拉回。客戶自己滑動或點擊後就不再干涉。 */
(function () {
	if (location.hash) { return; }
	try { history.scrollRestoration = 'manual'; } catch (e) {}
	var d = document.documentElement, t0 = Date.now(), user = false;
	['touchstart', 'wheel', 'keydown', 'mousedown'].forEach(function (n) {
		addEventListener(n, function () { user = true; }, { passive: true, once: true });
	});
	function snap() {
		if (user) { return; }
		if (window.scrollY !== 0) {
			var b = d.style.scrollBehavior;
			d.style.scrollBehavior = 'auto';
			window.scrollTo(0, 0);
			d.style.scrollBehavior = b;
		}
		if (Date.now() - t0 < 1500) { requestAnimationFrame(snap); }
	}
	snap();
})();
