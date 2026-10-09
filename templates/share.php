<?php
/**
 * Tlačidlá na zdieľanie (Facebook + Instagram).
 * Použitie v šablóne: <?php include __DIR__ . '/share.php'; ?>
 * Potrebuje $page (title, slug, is_post) – URL sa skladá zo SITE_URL.
 */
if (empty($page) || ($page['slug'] ?? '') === '404') return;
$share_url   = page_full_url($page, $current_lang ?? null);
$share_title = $page['title'] ?? '';
?>
<div class="share-buttons d-flex align-items-center flex-wrap gap-2 mt-4"
     data-share-url="<?= e($share_url) ?>"
     data-share-title="<?= e($share_title) ?>"
     data-msg-copied="<?= e(t('Odkaz bol skopírovaný. Vložte ho do Instagramu (príbeh, správa alebo bio).', 'Link copied. Paste it into Instagram (story, message or bio).')) ?>"
     data-msg-failed="<?= e(t('Odkaz sa nepodarilo skopírovať: ', 'Could not copy the link: ')) ?>">
    <span class="small text-muted me-1"><?= e(t('Zdieľať:', 'Share:')) ?></span>
    <a class="btn btn-sm btn-outline-secondary share-btn" data-share="facebook"
       href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($share_url) ?>"
       target="_blank" rel="noopener noreferrer">
        <i class="bi bi-facebook"></i> Facebook
    </a>
    <button type="button" class="btn btn-sm btn-outline-secondary share-btn" data-share="instagram">
        <i class="bi bi-instagram"></i> Instagram
    </button>
</div>
