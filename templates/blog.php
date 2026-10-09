<?php
include __DIR__ . '/header.php';
$cat_slug = $_GET['category'] ?? '';
$posts = [];
$categories = get_all_categories();
$active_cat = null;
try {
    if ($cat_slug) {
        $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ?');
        $stmt->execute([$cat_slug]);
        $active_cat = $stmt->fetch();
        if ($active_cat) {
            $stmt = db()->prepare('SELECT p.* FROM posts p INNER JOIN post_categories pc ON p.id = pc.post_id WHERE pc.category_id = ? AND p.status="published" ORDER BY p.published_at DESC');
            $stmt->execute([$active_cat['id']]);
            $posts = $stmt->fetchAll();
        }
    } else {
        $posts = db()->query('SELECT * FROM posts WHERE status="published" ORDER BY published_at DESC')->fetchAll();
    }
} catch (Exception $e) {}
?>
<main class="page-content">
    <div class="container">
        <h1 class="mb-2"><?= e($page['title'] ?? 'Blog') ?><?= $active_cat ? ' – ' . e($active_cat['name']) : '' ?></h1>
        <?= $page['content'] ?? '' ?>

        <?php if ($categories): ?>
        <div class="d-flex flex-wrap gap-2 mb-4 mt-3">
            <a href="/blog" class="btn btn-sm <?= !$active_cat ? 'btn-primary' : 'btn-outline-secondary' ?>">Všetky</a>
            <?php foreach ($categories as $c): if ((int)$c['post_count'] === 0 && !$active_cat) continue; ?>
            <a href="/blog?category=<?= e($c['slug']) ?>" class="btn btn-sm <?= ($active_cat['id'] ?? 0) == $c['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= e($c['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="row g-4 mt-1">
            <?php if (empty($posts)): ?>
                <div class="col-12"><p class="text-muted">Žiadne články v tejto kategórii.</p></div>
            <?php else: foreach ($posts as $post):
                $post = translate_row($post, 'post', $current_lang ?? null, ['title', 'excerpt']);
                $pcats = get_categories_for_post($post['id']);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <?php if ($post['featured_image']): ?>
                    <img src="<?= e(UPLOADS_URL . '/' . $post['featured_image']) ?>" class="card-img-top" style="height:180px;object-fit:cover" alt="">
                    <?php endif; ?>
                    <div class="card-body">
                        <p class="text-muted small mb-1"><?= $post['published_at'] ? date('d.m.Y', strtotime($post['published_at'])) : '' ?></p>
                        <?php if ($pcats): ?>
                        <div class="mb-1">
                            <?php foreach ($pcats as $pc): ?>
                            <a href="/blog?category=<?= e($pc['slug']) ?>" class="badge text-bg-light text-decoration-none me-1"><?= e($pc['name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <h5 class="card-title"><a href="/blog/<?= e($post['slug']) ?>" class="text-decoration-none text-dark"><?= e($post['title']) ?></a></h5>
                        <p class="card-text text-muted small"><?= e($post['excerpt'] ?? '') ?></p>
                        <a href="/blog/<?= e($post['slug']) ?>" class="btn btn-sm btn-outline-primary">Čítať viac</a>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
