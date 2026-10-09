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

<!-- Image Modal -->
<div class="image-modal" id="imageModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="image-modal-backdrop" data-close-modal="true"></div>
    <div class="image-modal-dialog">
        <button type="button" class="image-modal-close" aria-label="Zavrieť obrázok">×</button>
        <img id="imageModalImage" src="" alt="">
        <div id="imageModalCaption" class="image-modal-caption"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/share.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('imageModalImage');
    const modalCaption = document.getElementById('imageModalCaption');
    const modalClose = modal?.querySelector('.image-modal-close');

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const openModal = (src, title) => {
        if (!modal || !modalImage) return;
        modalImage.src = src;
        modalImage.alt = title || '';
        modalCaption.textContent = title || '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    document.querySelectorAll('[data-gallery-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', function () {
            openModal(this.dataset.src, this.dataset.title || '');
        });
    });

    modalClose?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (event) {
        if (event.target === modal || event.target.hasAttribute('data-close-modal')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
});

window.addEventListener('scroll', () => {
    document.getElementById('siteHeader').classList.toggle('scrolled', window.scrollY > 20);
});
</script>
</body>
</html>
