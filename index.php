<?php
/**
 * Leonardowin CMS - Frontend Router
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security_headers.php';
send_security_headers();

// Language from cookie/session (zoznam jazykov z DB)
start_session();
$default_lang = get_default_lang();
$available_langs = array_column(get_active_languages(), 'code');
if (isset($_GET['lang']) && in_array($_GET['lang'], $available_langs, true)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('rw_lang', $_GET['lang'], time() + 86400 * 365, '/');
}
$current_lang = $_SESSION['lang'] ?? $_COOKIE['rw_lang'] ?? $default_lang;
if (!in_array($current_lang, $available_langs, true)) $current_lang = $default_lang;
set_current_lang($current_lang);

$route = $_GET['route'] ?? null;
$slug_param = $_GET['slug'] ?? null;

// Blog post
if ($route === 'post' && $slug_param) {
    try {
        $stmt = db()->prepare('SELECT * FROM posts WHERE slug = ? AND status = "published"');
        $stmt->execute([$slug_param]);
        $post = $stmt->fetch();
    } catch (Exception $e) {
        $post = null;
    }
    if (!$post) {
        http_response_code(404);
        $page = ['title' => 'Článok nenájdený', 'content' => '<h1>404</h1><p>Článok neexistuje.</p>', 'meta_title' => '404', 'template_file' => 'page.php', 'featured_image' => null];
        $template_path = TEMPLATES_PATH . '/page.php';
    } else {
        $post = translate_row($post, 'post', $current_lang, ['title', 'excerpt', 'content']);
        $page = [
            'id' => $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'content' => $post['content'],
            'meta_title' => $post['title'] . ' – Blog',
            'meta_description' => $post['excerpt'],
            'featured_image' => $post['featured_image'],
            'template_file' => 'post.php',
            'is_post' => true,
            'published_at' => $post['published_at'],
            'excerpt' => $post['excerpt'],
            'updated_at' => $post['updated_at'] ?? null,
        ];
        $template_path = TEMPLATES_PATH . '/post.php';
    }
    $site_name = get_setting('site_name', 'Leonardowin');
    $site_tagline = get_setting('site_tagline');
    $primary_color = get_setting('primary_color', '#E30613');
    include $template_path;
    exit;
}

// Blog list
if ($route === 'blog') {
    $page = get_page_by_slug('blog') ?: [
        'title' => 'Blog',
        'content' => '',
        'meta_title' => 'Blog – Leonardowin',
        'meta_description' => 'Novinky a články',
        'template_file' => 'blog.php',
        'featured_image' => null,
        'slug' => 'blog',
        'id' => 0,
    ];
    $page = translate_row($page, 'page', $current_lang, ['title', 'content', 'meta_title', 'meta_description']);
    $page['template_file'] = 'blog.php';
    $template_path = TEMPLATES_PATH . '/blog.php';
    $site_name = get_setting('site_name', 'Leonardowin');
    $site_tagline = get_setting('site_tagline');
    $primary_color = get_setting('primary_color', '#E30613');
    include $template_path;
    exit;
}

// Standard page routing
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$slug = trim($request_uri, '/');
if ($slug === '' || $slug === 'index.php') $slug = 'home';

$page = get_page_by_slug($slug);
if ($page) {
    $page = translate_row($page, 'page', $current_lang, ['title', 'content', 'meta_title', 'meta_description']);
}
if (!$page) {
    http_response_code(404);
    $page = [
        'title' => 'Stránka nenájdená',
        'content' => '<h1>404 – Stránka nenájdená</h1><p>Požadovaná stránka neexistuje.</p><p><a href="/">Späť na úvod</a></p>',
        'meta_title' => '404 – Stránka nenájdená',
        'template_file' => 'page.php',
        'featured_image' => null,
        'slug' => '404',
        'id' => 0,
    ];
}

$template_file = $page['template_file'] ?? 'page.php';
$template_path = TEMPLATES_PATH . '/' . $template_file;
if (!file_exists($template_path)) $template_path = TEMPLATES_PATH . '/page.php';

$site_name = get_setting('site_name', 'Leonardowin');
$site_tagline = get_setting('site_tagline', 'Okná a dvere s technológiou Lignocoat®');
$primary_color = get_setting('primary_color', '#E30613');

include $template_path;
