<?php
/**
 * llms.txt – standard for AI crawlers
 * URL: /llms.txt.php or rewrite /llms.txt
 */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: all');

$name = get_setting('site_name', 'Leonardowin');
$tagline = get_setting('site_tagline');
$desc = get_setting('ai_description');
$url = rtrim(SITE_URL, '/');

echo "# {$name}\n";
echo "> {$tagline}\n\n";
echo "{$desc}\n\n";
echo "## Contact\n";
echo "- Email: " . get_setting('site_email') . "\n";
echo "- Phone: " . get_setting('site_phone') . "\n";
echo "- Address: " . get_setting('site_address') . "\n\n";
echo "## Pages\n";
$pages = db()->query('SELECT title, slug, meta_description FROM pages WHERE status="published" ORDER BY sort_order')->fetchAll();
foreach ($pages as $p) {
    $path = $p['slug'] === 'home' ? '/' : '/' . $p['slug'];
    echo "- [{$p['title']}]({$url}{$path}): " . ($p['meta_description'] ?: $p['title']) . "\n";
}
echo "\n## Blog\n";
try {
    $posts = db()->query('SELECT title, slug, excerpt FROM posts WHERE status="published" ORDER BY published_at DESC LIMIT 20')->fetchAll();
    foreach ($posts as $p) {
        echo "- [{$p['title']}]({$url}/blog/{$p['slug']}): " . ($p['excerpt'] ?: '') . "\n";
    }
} catch (Exception $e) {
    echo "- (no posts yet)\n";
}
echo "\n## Machine-readable index\n";
echo "- JSON: {$url}/ai.json\n";
echo "- This file: {$url}/llms.txt\n";
