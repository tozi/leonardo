-- Leonardowin CMS - Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS leonardowin_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE leonardowin_cms;

-- Users (admin)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: admin / admin123
INSERT INTO users (username, password, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@leonardowin.sk');

-- Templates
CREATE TABLE templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    description TEXT,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO templates (name, slug, file_path, description, is_default) VALUES
('Domovská stránka', 'home', 'home.php', 'Hlavná stránka s hero sekciou', 1),
('Štandardná stránka', 'page', 'page.php', 'Bežná podstránka s obsahom', 0),
('Galéria', 'gallery', 'gallery.php', 'Stránka s galériou obrázkov', 0),
('Kontakt', 'contact', 'contact.php', 'Kontaktná stránka s formulárom', 0),
('Na celú šírku', 'fullwidth', 'fullwidth.php', 'Obsah cez celú šírku obrazovky', 0),
('So sidebarom', 'sidebar', 'sidebar.php', 'Stránka s bočným panelom a menu', 0);

-- Pages / Subpages
CREATE TABLE pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    template_id INT UNSIGNED DEFAULT 2,
    status ENUM('published', 'draft') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    featured_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES pages(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO pages (title, slug, content, meta_title, template_id, status, sort_order) VALUES
('Domov', 'home', '<h1>Unikátne remeselné okná pre váš domov</h1><p>Spoznajte kvalitu a nadčasovosť našej remeselnej ručnej výroby okien a dverí s vyše 25 rokmi skúsenosti.</p>', 'Leonardowin - Okná a dvere s technológiou Lignocoat®', 1, 'published', 0),
('O nás', 'o-nas', '<h1>O nás</h1><p>Sme profesionáli s 25 rokmi skúseností vo výrobe drevených a drevohliníkových okien a dverí.</p>', 'O nás - Leonardowin', 2, 'published', 1),
('Produkty', 'produkty', '<h1>Naše produkty</h1><p>Ponúkame širokú škálu okien a dverí s technológiou Lignocoat®.</p>', 'Produkty - Leonardowin', 2, 'published', 2),
('Lignocoat', 'lignocoat', '<h1>Spoznajte Lignocoat®</h1><p>Inovatívne okná pripravené na budúcnosť. Vyvinuli sme novú technológiu spájania jednotlivých častí okna.</p>', 'Lignocoat® - Leonardowin', 2, 'published', 3),
('Realizácie', 'realizacie', '<h1>Realizácie</h1><p>Pozrite si naše referencie a hotové projekty.</p>', 'Realizácie - Leonardowin', 3, 'published', 4),
('FAQ', 'faq', '<h1>Často kladené otázky</h1><p>Odpovede na najčastejšie otázky.</p>', 'FAQ - Leonardowin', 2, 'published', 5),
('Kontakt', 'kontakt', '<h1>Kontaktujte nás</h1><p>Chceli by ste sa o našej remeselnej výrobe dozvedieť viac?</p>', 'Kontakt - Leonardowin', 4, 'published', 6);

-- Menus
CREATE TABLE menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    location VARCHAR(50) DEFAULT 'header',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO menus (name, slug, location) VALUES
('Hlavné menu', 'main-menu', 'header'),
('Pätička menu', 'footer-menu', 'footer');

-- Menu items
CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    url VARCHAR(255) DEFAULT NULL,
    page_id INT UNSIGNED DEFAULT NULL,
    target ENUM('_self', '_blank') DEFAULT '_self',
    css_class VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO menu_items (menu_id, title, page_id, sort_order) VALUES
(1, 'O nás', 2, 1),
(1, 'Produkty', 3, 2),
(1, 'Lignocoat', 4, 3),
(1, 'Realizácie', 5, 4),
(1, 'FAQ', 6, 5),
(1, 'Kontakt', 7, 6);

INSERT INTO menu_items (menu_id, title, page_id, sort_order) VALUES
(2, 'O nás', 2, 1),
(2, 'Produkty', 3, 2),
(2, 'Kontakt', 7, 3);

-- Galleries
CREATE TABLE galleries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    page_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Gallery images
CREATE TABLE gallery_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gallery_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Media library
CREATE TABLE media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size INT UNSIGNED NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Settings
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Leonardowin'),
('site_tagline', 'Okná a dvere s technológiou Lignocoat®'),
('site_email', 'info@leonardowin.sk'),
('site_phone', '0911 415 303'),
('site_address', 'Ordzovany 3, 053 06 Ordzovany'),
('primary_color', '#E30613'),
('footer_text', '© 2026 Leonardowin. Všetky práva vyhradené.');

-- Page blocks (for flexible content)
CREATE TABLE page_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    block_type ENUM('text', 'image', 'gallery', 'html', 'cta') DEFAULT 'text',
    title VARCHAR(255) DEFAULT NULL,
    content LONGTEXT,
    settings JSON DEFAULT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Blog posts
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(255) DEFAULT NULL,
    status ENUM('published', 'draft') DEFAULT 'draft',
    author_id INT UNSIGNED DEFAULT 1,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO posts (title, slug, excerpt, content, status, published_at) VALUES
('Vitajte na našom blogu', 'vitajte-na-nasom-blogu', 'Prvý článok o technológii Lignocoat® a výrobe okien.', '<p>Vitajte na oficiálnom blogu Leonardowin. Tu budeme zdieľať novinky, tipy a informácie o našich produktoch.</p>', 'published', NOW());

-- Languages / translations (simple i18n)
CREATE TABLE IF NOT EXISTS languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO languages (code, name, is_default, is_active) VALUES
('sk', 'Slovenčina', 1, 1),
('en', 'English', 0, 1);

CREATE TABLE IF NOT EXISTS translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lang_code VARCHAR(5) NOT NULL,
    entity_type ENUM('page', 'post', 'menu_item', 'setting') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    field_value LONGTEXT,
    UNIQUE KEY uq_trans (lang_code, entity_type, entity_id, field_name)
) ENGINE=InnoDB;

-- Cookie consent settings
INSERT INTO settings (setting_key, setting_value) VALUES
('cookie_enabled', '1'),
('cookie_text_sk', 'Súbory cookie používame na zlepšenie vášho zážitku z prehliadania. Kliknutím na „Prijať všetko" súhlasíte s ich používaním.'),
('cookie_text_en', 'We use cookies to improve your browsing experience. By clicking "Accept all" you agree to their use.'),
('ai_description', 'Leonardowin vyrába remeselné drevené a drevohliníkové okná a dvere s technológiou Lignocoat®. Sídlo: Ordzovany, Slovensko. 25+ rokov skúseností.'),
('site_lang_default', 'sk');

-- Blog template
INSERT INTO templates (name, slug, file_path, description, is_default) VALUES
('Blog zoznam', 'blog', 'blog.php', 'Zoznam blogových článkov', 0),
('Blog článok', 'post', 'post.php', 'Jednotlivý blogový článok', 0);

-- Blog page
INSERT INTO pages (title, slug, content, meta_title, template_id, status, sort_order)
SELECT 'Blog', 'blog', '<h1>Blog</h1><p>Novinky a články z Leonardowin.</p>', 'Blog - Leonardowin', id, 'published', 7
FROM templates WHERE slug = 'blog' LIMIT 1;

-- Blog categories
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (name, slug, description, sort_order) VALUES
('Novinky', 'novinky', 'Firemné novinky a oznámenia', 1),
('Technológia', 'technologia', 'Lignocoat® a technické články', 2),
('Tipy', 'tipy', 'Praktické tipy pre zákazníkov', 3);

-- Many-to-many: posts <-> categories
CREATE TABLE IF NOT EXISTS post_categories (
    post_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Login attempts (brute-force protection)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(50) DEFAULT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('login_max_attempts', '5'),
('login_lockout_minutes', '15');

-- Images attached to blog posts (besides featured_image)
CREATE TABLE IF NOT EXISTS post_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post (post_id)
) ENGINE=InnoDB;
