<!DOCTYPE html>
<html lang="<?= e($current_lang ?? 'sk') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page['meta_title'] ?? ($page['title'] . ' - ' . $site_name)) ?></title>
    <?php
    // ---- SEO + Open Graph (Facebook / Instagram / X / LinkedIn …) ----
    $lang_now      = $current_lang ?? get_default_lang();
    $is_404        = ($page['slug'] ?? '') === '404';
    $is_post_page  = !empty($page['is_post']);
    $seo_desc      = page_description($page);
    $canonical_url = page_full_url($page, $lang_now);
    $og_title      = $is_post_page ? $page['title'] : (($page['meta_title'] ?? '') ?: $page['title']);
    $og_image      = page_og_image($page);
    $og_img_info   = $og_image ? og_image_info($og_image) : null;
    ?>
    <?php if ($seo_desc !== ''): ?>
    <meta name="description" content="<?= e($seo_desc) ?>">
    <?php endif; ?>
    <?php if ($is_404): ?>
    <meta name="robots" content="noindex">
    <?php else: ?>
    <link rel="canonical" href="<?= e($canonical_url) ?>">
    <?php
    // hreflang len pre jazyky, v ktorých existuje preklad tejto stránky/článku
    if (!empty($page['id'])) {
        $tr_all = get_translations($is_post_page ? 'post' : 'page', $page['id']);
        $alt_langs = [];
        foreach ($tr_all as $code => $fields) {
            if (in_array($code, array_column(get_active_languages(), 'code'), true)
                && (trim($fields['title'] ?? '') !== '' || trim($fields['content'] ?? '') !== '')) {
                $alt_langs[] = $code;
            }
        }
        if ($alt_langs):
            $def_l = get_default_lang();
            ?>
    <link rel="alternate" hreflang="<?= e($def_l) ?>" href="<?= e(page_full_url($page, $def_l)) ?>">
    <?php foreach ($alt_langs as $al): ?>
    <link rel="alternate" hreflang="<?= e($al) ?>" href="<?= e(page_full_url($page, $al)) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e(page_full_url($page, $def_l)) ?>">
    <?php endif; } ?>

    <!-- Open Graph -->
    <meta property="og:type" content="<?= $is_post_page ? 'article' : 'website' ?>">
    <meta property="og:site_name" content="<?= e($site_name) ?>">
    <meta property="og:title" content="<?= e($og_title) ?>">
    <?php if ($seo_desc !== ''): ?><meta property="og:description" content="<?= e($seo_desc) ?>"><?php endif; ?>

    <meta property="og:url" content="<?= e($canonical_url) ?>">
    <meta property="og:locale" content="<?= e(og_locale($lang_now)) ?>">
    <?php foreach (get_active_languages() as $al): if ($al['code'] !== $lang_now): ?>
    <meta property="og:locale:alternate" content="<?= e(og_locale($al['code'])) ?>">
    <?php endif; endforeach; ?>
    <?php if ($og_image): ?>
    <meta property="og:image" content="<?= e($og_image) ?>">
    <?php if (strpos($og_image, 'https://') === 0): ?><meta property="og:image:secure_url" content="<?= e($og_image) ?>"><?php endif; ?>

    <?php if ($og_img_info): ?>
    <meta property="og:image:width" content="<?= (int)$og_img_info['width'] ?>">
    <meta property="og:image:height" content="<?= (int)$og_img_info['height'] ?>">
    <meta property="og:image:type" content="<?= e($og_img_info['type']) ?>">
    <?php endif; ?>
    <meta property="og:image:alt" content="<?= e($page['title']) ?>">
    <?php endif; ?>
    <?php if ($is_post_page): ?>
    <?php if (!empty($page['published_at'])): ?><meta property="article:published_time" content="<?= e(date('c', strtotime($page['published_at']))) ?>"><?php endif; ?>

    <?php if (!empty($page['updated_at'])): ?><meta property="article:modified_time" content="<?= e(date('c', strtotime($page['updated_at']))) ?>"><?php endif; ?>

    <?php endif; ?>
    <?php if (get_setting('fb_app_id')): ?><meta property="fb:app_id" content="<?= e(get_setting('fb_app_id')) ?>"><?php endif; ?>


    <!-- Twitter / X card -->
    <meta name="twitter:card" content="<?= $og_image ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= e($og_title) ?>">
    <?php if ($seo_desc !== ''): ?><meta name="twitter:description" content="<?= e($seo_desc) ?>"><?php endif; ?>

    <?php if ($og_image): ?><meta name="twitter:image" content="<?= e($og_image) ?>"><?php endif; ?>
    <?php endif; ?>
    <link rel="alternate" type="application/json" href="/ai.json" title="AI site index">
    <link rel="alternate" type="text/plain" href="/llms.txt" title="LLMs.txt">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>:root { --rw-primary: <?= e($primary_color) ?>; --rw-primary-dark: <?= e($primary_color) ?>dd; }</style>
    <?php
    // JSON-LD structured data for AI / Google
    $jsonld = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                'name' => $site_name,
                'url' => rtrim(SITE_URL, '/'),
                'description' => get_setting('ai_description'),
                'email' => get_setting('site_email'),
                'telephone' => get_setting('site_phone'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => get_setting('site_address'),
                    'addressCountry' => 'SK',
                ],
            ],
            [
                '@type' => 'WebSite',
                'name' => $site_name,
                'url' => rtrim(SITE_URL, '/'),
                'description' => get_setting('site_tagline'),
                'inLanguage' => array_column(get_active_languages(), 'code'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => rtrim(SITE_URL, '/') . '/?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => !empty($page['is_post']) ? 'BlogPosting' : 'WebPage',
                'name' => $page['title'] ?? '',
                'description' => $seo_desc,
                'url' => page_full_url($page),
                'inLanguage' => $lang_now,
            ] + ($og_image ? ['image' => $og_image] : [])
              + (!empty($page['is_post']) && !empty($page['published_at']) ? ['datePublished' => date('c', strtotime($page['published_at']))] : [])
              + (!empty($page['is_post']) && !empty($page['updated_at']) ? ['dateModified' => date('c', strtotime($page['updated_at']))] : []),
        ],
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<header class="site-header" id="siteHeader">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/"><?= e($site_name) ?> <span>.</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <?= render_menu('main-menu', 'navbar-nav mx-auto') ?>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-3 py-2 py-lg-0">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <?= strtoupper($current_lang ?? 'sk') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php
                            $lang_q = $_GET; unset($lang_q['route'], $lang_q['slug'], $lang_q['lang']);
                            $lang_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
                            foreach (get_active_languages() as $lg):
                                $lg_href = $lang_path . '?' . http_build_query($lang_q + ['lang' => $lg['code']]);
                            ?>
                            <li><a class="dropdown-item<?= $lg['code'] === ($current_lang ?? '') ? ' active' : '' ?>" href="<?= e($lg_href) ?>"><?= e(strtoupper($lg['code'])) ?> – <?= e($lg['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="header-cta">
                        <a href="/kontakt" class="btn btn-primary btn-sm"><?= e(ui_dict('btn_inquiry')) ?></a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
