<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

// Set default
if (isset($_GET['set_default']) && verify_csrf($_GET['token'] ?? '')) {
    db()->exec('UPDATE templates SET is_default = 0');
    db()->prepare('UPDATE templates SET is_default = 1 WHERE id = ?')->execute([(int)$_GET['set_default']]);
    set_flash('success', 'Predvolená šablóna nastavená.');
    redirect(ADMIN_URL . '/templates.php');
}

$templates = get_all_templates();
admin_header('Šablóny');
?>

<div class="card border-0 shadow-sm">
    <div class="card-header"><h2>Dostupné šablóny</h2></div>
    <div class="card-body table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Slug</th>
                    <th>Súbor</th>
                    <th>Popis</th>
                    <th>Predvolená</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td><strong><?= e($t['name']) ?></strong></td>
                    <td><code><?= e($t['slug']) ?></code></td>
                    <td><code>templates/<?= e($t['file_path']) ?></code></td>
                    <td><?= e($t['description'] ?? '') ?></td>
                    <td><?= $t['is_default'] ? '✓' : '' ?></td>
                    <td>
                        <?php if (!$t['is_default']): ?>
                        <a href="?set_default=<?= $t['id'] ?>&token=<?= generate_csrf() ?>" class="btn btn-sm btn-outline">Nastaviť predvolenú</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header"><h2>Ako fungujú šablóny</h2></div>
    <div class="card-body">
        <p>Šablóny určujú, ako sa stránka zobrazí na frontende. Súbory nájdete v priečinku <code>templates/</code>:</p>
        <ul style="margin:12px 0 12px 20px; color:var(--text-muted)">
            <li><code>home.php</code> – Domovská stránka s hero sekciou</li>
            <li><code>page.php</code> – Štandardná podstránka</li>
            <li><code>gallery.php</code> – Stránka s galériou obrázkov</li>
            <li><code>contact.php</code> – Kontakt s formulárom</li>
        </ul>
        <p>Pri vytváraní/úprave stránky vyberiete šablónu. Môžete pridať vlastné šablóny – stačí vytvoriť PHP súbor v <code>templates/</code> a pridať záznam do tabuľky <code>templates</code>.</p>
        <p style="margin-top:12px">V šablónach máte prístup k premenným <code>$page</code>, <code>$site_name</code> a funkciám ako <code>render_menu('slug')</code>.</p>
        <p style="margin-top:8px"><strong>Pridanie menu do šablóny:</strong></p>
        <pre style="background:#f4f5f7;padding:12px;border-radius:6px;margin-top:8px">&lt;?= render_menu('main-menu', 'nav-menu') ?&gt;
&lt;?= render_menu('moje-vlastne-menu') ?&gt;</pre>
    </div>
</div>

<?php admin_footer(); ?>
