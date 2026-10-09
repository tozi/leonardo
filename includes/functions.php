<?php
/**
 * Helper functions
 */

require_once __DIR__ . '/db.php';

// Start session
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// CSRF
function generate_csrf() {
    start_session();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf($token) {
    start_session();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrf_field() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generate_csrf() . '">';
}

// Auth
function is_logged_in() {
    start_session();
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function current_user() {
    if (!is_logged_in()) return null;
    $stmt = db()->prepare('SELECT id, username, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Settings
function get_setting($key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    $cache[$key] = $row ? $row['setting_value'] : $default;
    return $cache[$key];
}

function set_setting($key, $value) {
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}

// Slug
function create_slug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $map = [
        'á'=>'a','ä'=>'a','č'=>'c','ď'=>'d','é'=>'e','í'=>'i','ĺ'=>'l','ľ'=>'l',
        'ň'=>'n','ó'=>'o','ô'=>'o','ŕ'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ý'=>'y','ž'=>'z',
        'Á'=>'a','Ä'=>'a','Č'=>'c','Ď'=>'d','É'=>'e','Í'=>'i','Ĺ'=>'l','Ľ'=>'l',
        'Ň'=>'n','Ó'=>'o','Ô'=>'o','Ŕ'=>'r','Š'=>'s','Ť'=>'t','Ú'=>'u','Ý'=>'y','Ž'=>'z'
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Escape
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Flash messages
function set_flash($type, $message) {
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    start_session();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Pages
function get_page_by_slug($slug) {
    $stmt = db()->prepare('SELECT p.*, t.file_path as template_file, t.slug as template_slug
        FROM pages p
        LEFT JOIN templates t ON p.template_id = t.id
        WHERE p.slug = ? AND p.status = "published"');
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function get_all_pages($status = null) {
    $sql = 'SELECT p.*, t.name as template_name FROM pages p
            LEFT JOIN templates t ON p.template_id = t.id';
    if ($status) {
        $sql .= ' WHERE p.status = ? ORDER BY p.sort_order, p.title';
        $stmt = db()->prepare($sql);
        $stmt->execute([$status]);
    } else {
        $sql .= ' ORDER BY p.sort_order, p.title';
        $stmt = db()->query($sql);
    }
    return $stmt->fetchAll();
}

function get_page($id) {
    $stmt = db()->prepare('SELECT * FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Menus
function get_menu_by_slug($slug) {
    $stmt = db()->prepare('SELECT * FROM menus WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function get_menu_items($menu_id) {
    $stmt = db()->prepare('SELECT mi.*, p.slug as page_slug
        FROM menu_items mi
        LEFT JOIN pages p ON mi.page_id = p.id
        WHERE mi.menu_id = ? AND mi.is_active = 1
        ORDER BY mi.sort_order, mi.title');
    $stmt->execute([$menu_id]);
    $items = $stmt->fetchAll();

    // Build tree
    $tree = [];
    $lookup = [];
    foreach ($items as &$item) {
        $item['children'] = [];
        $lookup[$item['id']] = &$item;
    }
    foreach ($items as &$item) {
        if ($item['parent_id'] && isset($lookup[$item['parent_id']])) {
            $lookup[$item['parent_id']]['children'][] = &$item;
        } else {
            $tree[] = &$item;
        }
    }
    return $tree;
}

function render_menu($slug, $ul_class = 'nav-menu', $li_class = '') {
    $menu = get_menu_by_slug($slug);
    if (!$menu) return '';
    $items = get_menu_items($menu['id']);
    return render_menu_items($items, $ul_class, $li_class);
}

function render_menu_items($items, $ul_class = '', $li_class = '') {
    if (empty($items)) return '';
    $is_nav = strpos($ul_class, 'navbar-nav') !== false || strpos($ul_class, 'nav ') !== false || $ul_class === 'nav';
    $html = '<ul class="' . e($ul_class) . '">';
    $lang = current_lang();
    $title_tr = ($lang !== get_default_lang()) ? get_translation_map('menu_item', $lang, 'title') : [];
    foreach ($items as $item) {
        $url = $item['url'] ?: ($item['page_slug'] ? '/' . $item['page_slug'] : '#');
        if ($item['page_slug'] === 'home') $url = '/';
        $target = $item['target'] === '_blank' ? ' target="_blank"' : '';
        $li_cls = $item['css_class'] ?: $li_class;
        if ($is_nav) $li_cls = trim(($li_cls ?: '') . ' nav-item');
        $a_cls = $is_nav ? ' class="nav-link"' : '';
        $html .= '<li class="' . e(trim($li_cls)) . '"><a href="' . e($url) . '"' . $a_cls . $target . '>' . e(!empty($title_tr[(int)$item['id']]) ? $title_tr[(int)$item['id']] : $item['title']) . '</a>';
        if (!empty($item['children'])) {
            $html .= render_menu_items($item['children'], $is_nav ? 'dropdown-menu' : 'submenu');
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

// Galleries
function get_gallery($id) {
    $stmt = db()->prepare('SELECT * FROM galleries WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_gallery_images($gallery_id) {
    $stmt = db()->prepare('SELECT * FROM gallery_images WHERE gallery_id = ? ORDER BY sort_order, id');
    $stmt->execute([$gallery_id]);
    return $stmt->fetchAll();
}

function get_all_galleries() {
    return db()->query('SELECT g.*, COUNT(gi.id) as image_count
        FROM galleries g
        LEFT JOIN gallery_images gi ON g.id = gi.gallery_id
        GROUP BY g.id
        ORDER BY g.title')->fetchAll();
}

// Templates
function get_all_templates() {
    return db()->query('SELECT * FROM templates ORDER BY name')->fetchAll();
}

function get_template($id) {
    $stmt = db()->prepare('SELECT * FROM templates WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Media upload
function upload_image($file, $subdir = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Chyba pri nahrávaní súboru.'];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'Súbor je príliš veľký (max 5 MB).'];
    }
    // Verify real MIME via finfo, not client-provided type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        return ['success' => false, 'error' => 'Nepovolený typ súboru.'];
    }
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $ext = $ext_map[$mime] ?? 'bin';
    // Reject double extensions / path tricks in original name
    $orig = basename($file['name']);
    $orig = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $orig);

    $filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $dir = UPLOADS_PATH . ($subdir ? '/' . preg_replace('/[^a-z0-9_\-]/', '', $subdir) : '');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => false, 'error' => 'Nepodarilo sa uložiť súbor.'];
    }
    // Prevent script execution in uploads
    @chmod($path, 0644);

    $stmt = db()->prepare('INSERT INTO media (filename, original_name, mime_type, size) VALUES (?, ?, ?, ?)');
    $stmt->execute([$filename, $orig, $mime, (int)$file['size']]);

    return [
        'success' => true,
        'filename' => $filename,
        'url' => UPLOADS_URL . ($subdir ? '/' . $subdir : '') . '/' . $filename,
        'id' => db()->lastInsertId()
    ];
}

// Redirect
function redirect($url) {
    header('Location: ' . $url);
    exit;
}


// ========== Security helpers ==========

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_login_locked_out() {
    $max = (int) get_setting('login_max_attempts', '5');
    $mins = (int) get_setting('login_lockout_minutes', '15');
    if ($max < 1) return false;
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $stmt->execute([client_ip(), $mins]);
        return (int)$stmt->fetchColumn() >= $max;
    } catch (Exception $e) {
        return false;
    }
}

function record_login_attempt($username = null) {
    try {
        db()->prepare('INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)')->execute([client_ip(), $username]);
    } catch (Exception $e) {}
}

function clear_login_attempts() {
    try {
        db()->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([client_ip()]);
    } catch (Exception $e) {}
}

/** Sanitize HTML content from editor – allow safe tags only */
function sanitize_html($html) {
    if ($html === null || $html === '') return '';
    $allowed = '<p><br><br/><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tr><th><td><blockquote><pre><code><hr><span><div><figure><figcaption><iframe><s>';
    $html = strip_tags($html, $allowed);
    // Remove on* event handlers and javascript: URLs
    $html = preg_replace('/\s on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/iu', '$1=$2#$2', $html);
    return $html;
}

/** Validate redirect is relative (open redirect protection) */
function safe_redirect($url) {
    if (preg_match('#^https?://#i', $url) || strpos($url, '//') === 0) {
        $url = ADMIN_URL . '/index.php';
    }
    redirect($url);
}

/** Rate-limit simple form submissions by session */
function check_form_rate_limit($form_key, $seconds = 10) {
    start_session();
    $key = 'rate_' . $form_key;
    $now = time();
    if (!empty($_SESSION[$key]) && ($now - $_SESSION[$key]) < $seconds) {
        return false;
    }
    $_SESSION[$key] = $now;
    return true;
}

/** Honeypot field – bots fill this, humans should leave empty */
function honeypot_field() {
    return '<div style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden" aria-hidden="true">'
        . '<label>Website</label><input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">'
        . '</div>';
}

function honeypot_failed() {
    return !empty($_POST['website_url']);
}

// Categories helpers
function get_all_categories() {
    try {
        return db()->query('SELECT c.*, COUNT(pc.post_id) as post_count FROM categories c LEFT JOIN post_categories pc ON c.id = pc.category_id GROUP BY c.id ORDER BY c.sort_order, c.name')->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function get_category($id) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function get_post_category_ids($post_id) {
    try {
        $stmt = db()->prepare('SELECT category_id FROM post_categories WHERE post_id = ?');
        $stmt->execute([(int)$post_id]);
        return array_column($stmt->fetchAll(), 'category_id');
    } catch (Exception $e) {
        return [];
    }
}

function set_post_categories($post_id, $category_ids) {
    db()->prepare('DELETE FROM post_categories WHERE post_id = ?')->execute([(int)$post_id]);
    $stmt = db()->prepare('INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)');
    foreach ($category_ids as $cid) {
        $cid = (int)$cid;
        if ($cid > 0) $stmt->execute([(int)$post_id, $cid]);
    }
}

function get_categories_for_post($post_id) {
    try {
        $stmt = db()->prepare('SELECT c.* FROM categories c INNER JOIN post_categories pc ON c.id = pc.category_id WHERE pc.post_id = ? ORDER BY c.sort_order, c.name');
        $stmt->execute([(int)$post_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}



// ========== i18n (jazyky + preklady) ==========

/** Aktívne jazyky z DB (predvolený prvý). Fallback sk/en, ak tabuľka chýba. */
function get_active_languages() {
    static $langs = null;
    if ($langs !== null) return $langs;
    try {
        $langs = db()->query('SELECT code, name, is_default FROM languages WHERE is_active = 1 ORDER BY is_default DESC, name')->fetchAll();
    } catch (Exception $e) {
        $langs = [];
    }
    if (!$langs) {
        $langs = [
            ['code' => 'sk', 'name' => 'Slovenčina', 'is_default' => 1],
            ['code' => 'en', 'name' => 'English', 'is_default' => 0],
        ];
    }
    return $langs;
}

function get_default_lang() {
    foreach (get_active_languages() as $l) {
        if (!empty($l['is_default'])) return $l['code'];
    }
    return get_setting('site_lang_default', 'sk');
}

/** Aktívne jazyky okrem predvoleného (tie sa prekladajú). */
function get_translation_langs() {
    $def = get_default_lang();
    return array_values(array_filter(get_active_languages(), function ($l) use ($def) {
        return $l['code'] !== $def;
    }));
}

/** Aktuálny jazyk frontendu (nastavuje index.php). */
function set_current_lang($code = null) {
    static $lang = null;
    if ($code !== null) $lang = $code;
    return $lang ?? get_default_lang();
}
function current_lang() {
    return set_current_lang();
}

/**
 * Preklady jednej entity.
 * s $lang: [field => value]; bez $lang: [lang => [field => value]]
 */
function get_translations($type, $id, $lang = null) {
    $out = [];
    try {
        $sql = 'SELECT lang_code, field_name, field_value FROM translations WHERE entity_type = ? AND entity_id = ?';
        $params = [$type, (int)$id];
        if ($lang !== null) {
            $sql .= ' AND lang_code = ?';
            $params[] = $lang;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            if ($lang !== null) $out[$r['field_name']] = $r['field_value'];
            else $out[$r['lang_code']][$r['field_name']] = $r['field_value'];
        }
    } catch (Exception $e) {}
    return $out;
}

/** Uloží preklady polí; prázdna hodnota = zmazať preklad poľa (použije sa pôvodný text). */
function save_translations($type, $id, $lang, array $fields) {
    try {
        $ins = db()->prepare('INSERT INTO translations (lang_code, entity_type, entity_id, field_name, field_value) VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)');
        $del = db()->prepare('DELETE FROM translations WHERE lang_code = ? AND entity_type = ? AND entity_id = ? AND field_name = ?');
        foreach ($fields as $name => $value) {
            if ($value === null || trim((string)$value) === '') {
                $del->execute([$lang, $type, (int)$id, $name]);
            } else {
                $ins->execute([$lang, $type, (int)$id, $name, $value]);
            }
        }
    } catch (Exception $e) {}
}

/** Prepíše polia riadku prekladom (ak existuje a je neprázdny). */
function translate_row(array $row, $type, $lang, array $fields) {
    if (empty($row['id']) || $lang === null || $lang === get_default_lang()) return $row;
    $tr = get_translations($type, $row['id'], $lang);
    foreach ($fields as $f) {
        if (isset($tr[$f]) && trim($tr[$f]) !== '') $row[$f] = $tr[$f];
    }
    return $row;
}

/** [entity_id => value] pre jedno pole a jazyk (jeden dopyt, pre menu). */
function get_translation_map($type, $lang, $field) {
    static $cache = [];
    $key = "$type|$lang|$field";
    if (isset($cache[$key])) return $cache[$key];
    $map = [];
    try {
        $stmt = db()->prepare('SELECT entity_id, field_value FROM translations WHERE entity_type = ? AND lang_code = ? AND field_name = ?');
        $stmt->execute([$type, $lang, $field]);
        foreach ($stmt->fetchAll() as $r) $map[(int)$r['entity_id']] = $r['field_value'];
    } catch (Exception $e) {}
    return $cache[$key] = $map;
}

function lang_name($code) {
    foreach (get_active_languages() as $l) {
        if ($l['code'] === $code) return $l['name'];
    }
    return strtoupper($code);
}

/** sk -> sk_SK, en -> en_US, pt-BR -> pt_BR … (pre og:locale) */
function og_locale($code) {
    static $map = ['sk' => 'sk_SK', 'en' => 'en_US', 'cs' => 'cs_CZ', 'de' => 'de_DE', 'hu' => 'hu_HU', 'pl' => 'pl_PL',
                   'uk' => 'uk_UA', 'fr' => 'fr_FR', 'es' => 'es_ES', 'it' => 'it_IT', 'ru' => 'ru_RU', 'nl' => 'nl_NL'];
    if (strpos($code, '-') !== false) return str_replace('-', '_', $code);
    return $map[$code] ?? ($code . '_' . strtoupper($code));
}

/** Malý slovník pre pevné texty vo frontende (sk / ostatné = en). */
function t($sk, $en) {
    return current_lang() === 'sk' ? $sk : $en;
}

/** UI dictionary: generický slovník týchto textov pre frontend a admin */
function ui_dict($key, $lang = null, $fallback = null) {
    $lang = $lang ?: current_lang();
    $key = trim((string)$key);
    if ($key === '') return $fallback ?? '';
    try {
        $stmt = db()->prepare('SELECT d.default_text, t.translated_text
            FROM ui_dictionary d
            LEFT JOIN ui_dictionary_translations t
                ON t.dict_id = d.id AND t.lang_code = ?
            WHERE d.dict_key = ?
            LIMIT 1');
        $stmt->execute([$lang, $key]);
        $row = $stmt->fetch();
        if ($row) {
            if (trim((string)$row['translated_text']) !== '') {
                return $row['translated_text'];
            }
            if (trim((string)$row['default_text']) !== '') {
                return $row['default_text'];
            }
        }
    } catch (Exception $e) {}
    return $fallback ?? $key;
}

function ui_dict_exists($key) {
    try {
        $stmt = db()->prepare('SELECT 1 FROM ui_dictionary WHERE dict_key = ? LIMIT 1');
        $stmt->execute([$key]);
        return (bool) $stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function save_ui_dict_translation($key, $lang, $value) {
    $key = trim((string)$key);
    $lang = trim((string)$lang);
    if ($key === '' || $lang === '') return false;
    try {
        $stmt = db()->prepare('SELECT id FROM ui_dictionary WHERE dict_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) {
            db()->prepare('INSERT INTO ui_dictionary (dict_key, category, default_text, description, sort_order)
                VALUES (?, ?, ?, ?, 999)')->execute([$key, 'custom', $value ?: $key, 'Custom UI key']);
            $row = ['id' => db()->lastInsertId()];
        }
        $value = trim((string)$value);
        if ($value === '') {
            db()->prepare('DELETE FROM ui_dictionary_translations WHERE dict_id = ? AND lang_code = ?')->execute([(int)$row['id'], $lang]);
            return true;
        }
        $ins = db()->prepare('INSERT INTO ui_dictionary_translations (dict_id, lang_code, translated_text)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE translated_text = VALUES(translated_text)');
        $ins->execute([(int)$row['id'], $lang, $value]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ========== SEO / Open Graph / zdieľanie ==========

function site_base_url() {
    return rtrim(SITE_URL, '/');
}

function absolute_url($path) {
    if (preg_match('#^https?://#i', $path)) return $path;
    return site_base_url() . '/' . ltrim($path, '/');
}

/** Cesta stránky/článku bez domény (/, /o-nas, /blog/slug). */
function page_path($page) {
    if (!empty($page['is_post'])) return '/blog/' . ($page['slug'] ?? '');
    $slug = $page['slug'] ?? '';
    return ($slug === '' || $slug === 'home') ? '/' : '/' . $slug;
}

/** Plná URL; pre nepredvolený jazyk s ?lang=xx, aby odkaz otvoril správnu jazykovú verziu. */
function page_full_url($page, $lang = null) {
    $url = site_base_url() . page_path($page);
    if ($lang !== null && $lang !== get_default_lang()) $url .= '?lang=' . rawurlencode($lang);
    return $url;
}

/** Popis pre meta/OG: meta description → perex → začiatok obsahu → slogan. */
function page_description($page) {
    $d = trim($page['meta_description'] ?? '');
    if ($d === '') $d = trim($page['excerpt'] ?? '');
    if ($d === '') {
        $txt = preg_replace('/<[^>]+>/', ' ', $page['content'] ?? '');
        $txt = trim(preg_replace('/\s+/u', ' ', html_entity_decode($txt, ENT_QUOTES, 'UTF-8')));
        $d = mb_strimwidth($txt, 0, 200, '…', 'UTF-8');
    }
    if ($d === '') $d = get_setting('site_tagline');
    return $d;
}

/** Absolútna URL obrázka pre OG: hlavný obrázok → prvý <img> v obsahu → predvolený obrázok z nastavení. */
function page_og_image($page) {
    if (!empty($page['featured_image'])) {
        return absolute_url(UPLOADS_URL . '/' . $page['featured_image']);
    }
    if (!empty($page['content']) && preg_match('/<img[^>]+src\s*=\s*["\']([^"\']+)["\']/i', $page['content'], $m)
        && !preg_match('/^data:/i', $m[1])) {
        return absolute_url($m[1]);
    }
    $def = get_setting('og_default_image');
    return $def ? absolute_url(UPLOADS_URL . '/' . $def) : '';
}

/** Rozmery a MIME lokálneho obrázka (Facebook ho zobrazí hneď pri prvom zdieľaní). */
function og_image_info($url) {
    $base = site_base_url() . UPLOADS_URL . '/';
    if (strpos($url, $base) !== 0) return null;
    $file = UPLOADS_PATH . '/' . basename(substr($url, strlen($base)));
    if (!is_file($file)) return null;
    $i = @getimagesize($file);
    return $i ? ['width' => $i[0], 'height' => $i[1], 'type' => $i['mime']] : null;
}
