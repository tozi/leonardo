/**
 * Zdieľanie na Facebook a Instagram.
 *  - Facebook: oficiálny sharer dialóg (popup).
 *  - Instagram: web nemá "share URL" ako Facebook. Na mobile sa otvorí systémový
 *    zdieľací panel (Web Share API), na desktope sa odkaz skopíruje a otvorí sa Instagram.
 */
(function () {
    'use strict';

    function toast(msg, isError) {
        var el = document.createElement('div');
        el.className = 'alert ' + (isError ? 'alert-danger' : 'alert-dark') + ' shadow position-fixed bottom-0 start-50 translate-middle-x mb-4';
        el.style.zIndex = 10000;
        el.style.maxWidth = '90vw';
        el.setAttribute('role', 'status');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 4500);
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy') ? resolve() : reject(new Error('copy')); }
            catch (e) { reject(e); }
            ta.remove();
        });
    }

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-share]');
        if (!btn) return;
        var box = btn.closest('.share-buttons');
        if (!box) return;
        var url = box.dataset.shareUrl, title = box.dataset.shareTitle;

        if (btn.dataset.share === 'facebook') {
            ev.preventDefault();
            var w = window.open(btn.href, 'fbshare', 'width=620,height=520,noopener,noreferrer');
            if (!w) window.location.href = btn.href; // popup blokovaný
            return;
        }

        if (btn.dataset.share === 'instagram') {
            ev.preventDefault();
            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function (e) {
                    if (e && e.name === 'AbortError') return;
                    fallback();
                });
            } else {
                fallback();
            }
        }

        function fallback() {
            copyText(url).then(function () {
                toast(box.dataset.msgCopied);
                window.open('https://www.instagram.com/', '_blank', 'noopener');
            }).catch(function () {
                toast(box.dataset.msgFailed + url, true);
            });
        }
    });
})();
