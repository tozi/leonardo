<?php
/**
 * AI-friendly site index (JSON)
 * URL: /ai.json.php  or rewrite to /ai.json
 */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('X-Robots-Tag: all');

$site = [
    'name' => get_setting('site_name', 'Leonardowin'),
    'tagline' => get_setting('site_tagline'),
    'description' => get_setting('ai_description'),
    'url' => rtrim(SITE_URL, '/'),
    'language' => get_setting('site_lang_default', 'sk'),
    'languages' => array_column(db()->query('SELECT code, name FROM languages WHERE is_active=1')->fetchAll(), 'name', 'code'),
    'contact' => [
        'email' => get_setting('site_email'),
        'phone' => get_setting('site_phone'),
        'address' => get_setting('site_address'),
    ],
    'generated_at' => date('c'),
];

$pages = db()->query('SELECT id, title, slug, meta_title, meta_description, status, updated_at FROM pages WHERE status="published" ORDER BY sort_order, title')->fetchAll();
$site['pages'] = array_map(function($p) {
    return [
        'title' => $p['title'],
        'url' => rtrim(SITE_URL, '/') . ($p['slug'] === 'home' ? '/' : '/' . $p['slug']),
        'slug' => $p['slug'],
        'meta_title' => $p['meta_title'],
        'meta_description' => $p['meta_description'],
        'updated_at' => $p['updated_at'],
    ];
}, $pages);

$posts = [];
try {
    $posts = db()->query('SELECT title, slug, excerpt, published_at, updated_at FROM posts WHERE status="published" ORDER BY published_at DESC')->fetchAll();
} catch (Exception $e) {}
$site['blog_posts'] = array_map(function($p) {
    return [
        'title' => $p['title'],
        'url' => rtrim(SITE_URL, '/') . '/blog/' . $p['slug'],
        'excerpt' => $p['excerpt'],
        'published_at' => $p['published_at'],
    ];
}, $posts);

$menus = db()->query('SELECT id, name, slug, location FROM menus')->fetchAll();
$site['menus'] = [];
foreach ($menus as $m) {
    $items = get_menu_items($m['id']);
    $flat = [];
    $flatten = function($items) use (&$flatten, &$flat) {
        foreach ($items as $it) {
            $url = $it['url'] ?: ($it['page_slug'] ? '/' . $it['page_slug'] : null);
            if ($it['page_slug'] === 'home') $url = '/';
            $flat[] = ['title' => $it['title'], 'url' => $url];
            if (!empty($it['children'])) $flatten($it['children']);
        }
    };
    $flatten($items);
    $site['menus'][] = ['name' => $m['name'], 'slug' => $m['slug'], 'location' => $m['location'], 'items' => $flat];
}

echo json_encode($site, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
