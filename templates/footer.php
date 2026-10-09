<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="logo mb-2"><?= e($site_name) ?> <span>.</span></div>
                <p class="mb-2"><?= e(get_setting('site_tagline')) ?></p>
                <p class="small">
                    <?= e(get_setting('site_address')) ?><br>
                    <a href="tel:<?= e(get_setting('site_phone')) ?>"><?= e(get_setting('site_phone')) ?></a><br>
                    <a href="mailto:<?= e(get_setting('site_email')) ?>"><?= e(get_setting('site_email')) ?></a>
                </p>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Navigácia</h5>
                <?= render_menu('footer-menu', 'list-unstyled') ?>
            </div>
            <div class="col-6 col-lg-3">
                <h5>Produkty</h5>
                <ul class="list-unstyled">
                    <li class="mb-1"><a href="/produkty">Drevené okná</a></li>
                    <li class="mb-1"><a href="/produkty">Drevohliníkové okná</a></li>
                    <li class="mb-1"><a href="/produkty">Posuvné systémy</a></li>
                    <li class="mb-1"><a href="/blog">Blog</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h5>Kontakt</h5>
                <ul class="list-unstyled">
                    <li class="mb-1"><a href="/kontakt">Napíšte nám</a></li>
                    <li class="mb-1"><a href="tel:<?= e(get_setting('site_phone')) ?>"><?= e(get_setting('site_phone')) ?></a></li>
                    <li class="mb-1"><a href="/ai.json" class="small">AI JSON index</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-sm-row justify-content-between">
            <span><?= e(get_setting('footer_text', '© 2026 Leonardowin. Všetky práva vyhradené.')) ?></span>
            <span>CMS powered by Leonardowin CMS</span>
        </div>
    </div>
</footer>

<?php if (get_setting('cookie_enabled', '1') === '1'): ?>
<div id="cookieBanner" class="position-fixed bottom-0 start-0 end-0 p-3" style="z-index:9999;display:none">
    <div class="container">
        <div class="card shadow border-0">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <p class="mb-0 small" id="cookieText">
                    <?= e(get_setting('cookie_text_' . ($current_lang ?? 'sk')) ?: get_setting('cookie_text_' . get_default_lang())) ?>
                </p>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="cookieReject">Odmietnuť</button>
                    <button type="button" class="btn btn-primary btn-sm" id="cookieAccept">Prijať všetko</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    const key = 'rw_cookie_consent';
    const banner = document.getElementById('cookieBanner');
    if (!localStorage.getItem(key) && banner) banner.style.display = 'block';
    document.getElementById('cookieAccept')?.addEventListener('click', () => {
        localStorage.setItem(key, 'accepted');
        banner.style.display = 'none';
    });
    document.getElementById('cookieReject')?.addEventListener('click', () => {
        localStorage.setItem(key, 'rejected');
        banner.style.display = 'none';
    });
})();
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/share.js" defer></script>
<script>
window.addEventListener('scroll', () => {
    document.getElementById('siteHeader').classList.toggle('scrolled', window.scrollY > 20);
});
</script>
</body>
</html>
