<?php
/**
 * Secure image upload endpoint (JSON)
 * Used by the Quill editor and post image gallery
 */
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Neplatný CSRF token']);
    exit;
}

$file = $_FILES['file'] ?? $_FILES['image'] ?? null;
if (!$file || empty($file['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Žiadny súbor']);
    exit;
}

$up = upload_image($file);
if (!$up['success']) {
    http_response_code(400);
    echo json_encode(['error' => $up['error']]);
    exit;
}

// Optionally attach to post
$post_id = (int)($_POST['post_id'] ?? 0);
$title = mb_substr(trim($_POST['title'] ?? ''), 0, 255);
if ($post_id > 0) {
    try {
        $check = db()->prepare('SELECT id FROM posts WHERE id = ?');
        $check->execute([$post_id]);
        if ($check->fetch()) {
            $max = db()->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM post_images WHERE post_id = ?');
            $max->execute([$post_id]);
            $order = (int)$max->fetchColumn();
            db()->prepare('INSERT INTO post_images (post_id, filename, title, sort_order) VALUES (?,?,?,?)')
               ->execute([$post_id, $up['filename'], $title ?: null, $order]);
            $up['image_id'] = (int)db()->lastInsertId();
        }
    } catch (Exception $e) {
        // table may not exist yet – still return upload success
    }
}

// Quill editor reads `location` (alias `url`)
echo json_encode([
    'location' => $up['url'],
    'url' => $up['url'],
    'filename' => $up['filename'],
    'id' => $up['id'] ?? null,
    'image_id' => $up['image_id'] ?? null,
]);
