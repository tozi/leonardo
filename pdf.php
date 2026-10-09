<?php
/**
 * Simple HTML-to-print PDF export for a page
 * Opens print-friendly view; user can Save as PDF from browser
 * Or use wkhtmltopdf if available on server
 */
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$page = get_page_by_slug($slug);
if (!$page) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

// Jazyk z cookie (rovnako ako na webe) – PDF v aktuálnom jazyku
$pdf_lang = $_COOKIE['rw_lang'] ?? get_default_lang();
if (!in_array($pdf_lang, array_column(get_active_languages(), 'code'), true)) $pdf_lang = get_default_lang();
$page = translate_row($page, 'page', $pdf_lang, ['title', 'content']);

$site_name = get_setting('site_name');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($pdf_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?> – PDF</title>
    <style>
        body { font-family: Georgia, serif; max-width: 800px; margin: 40px auto; padding: 0 20px; color: #222; line-height: 1.6; }
        h1 { font-size: 1.8rem; border-bottom: 2px solid #E30613; padding-bottom: 8px; }
        img { max-width: 100%; }
        .meta { color: #888; font-size: 0.85rem; margin-bottom: 24px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:20px">
        <button onclick="window.print()" style="padding:10px 20px;background:#E30613;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:1rem">
            Tlačiť / Uložiť ako PDF
        </button>
        <a href="/<?= htmlspecialchars($slug === 'home' ? '' : $slug) ?>" style="margin-left:12px">← Späť na stránku</a>
    </div>
    <p class="meta"><?= htmlspecialchars($site_name) ?> · <?= date('d.m.Y') ?></p>
    <h1><?= htmlspecialchars($page['title']) ?></h1>
    <?= $page['content'] ?>
    <script>/* auto-open print dialog optional: window.onload=()=>window.print(); */</script>
</body>
</html>
