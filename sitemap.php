<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$url = rtrim(SITE_URL, '/');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
$pages = db()->query('SELECT slug, updated_at FROM pages WHERE status="published"')->fetchAll();
foreach ($pages as $p):
    $loc = $p['slug'] === 'home' ? $url . '/' : $url . '/' . $p['slug'];
?>
  <url>
    <loc><?= htmlspecialchars($loc) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
  </url>
<?php endforeach;
try {
    $posts = db()->query('SELECT slug, updated_at FROM posts WHERE status="published"')->fetchAll();
    foreach ($posts as $p):
?>
  <url>
    <loc><?= htmlspecialchars($url . '/blog/' . $p['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
  </url>
<?php endforeach;
} catch (Exception $e) {}
?>
</urlset>
