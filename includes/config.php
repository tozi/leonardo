<?php
/**
 * Leonardowin CMS - Configuration
 */

// Database
define('DB_HOST', 'db.r5.websupport.sk');
define('DB_NAME', 'KiIHjBEo');
define('DB_USER', '0cOniOEl');
define('DB_PASS', 'd=J=Kxm(0ht)#<=]Kzt&');
define('DB_CHARSET', 'utf8mb4');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('UPLOADS_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOADS_URL', '/assets/uploads');

// Site
define('SITE_URL', 'http://test.drevohlinik.sk');
define('ADMIN_URL', SITE_URL . '/admin');

// Security
define('SESSION_NAME', 'leonardowin_cms_session');
define('CSRF_TOKEN_NAME', 'csrf_token');

// Uploads
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Timezone
date_default_timezone_set('Europe/Bratislava');

// Error reporting (v produkcii vypnúť)
error_reporting(E_ALL);
ini_set('display_errors', 1);
