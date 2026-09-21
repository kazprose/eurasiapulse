<?php
/** CLI installer for the local WordPress (SQLite). Run: php dev/wp-install.php */
define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
require __DIR__ . '/../.local/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( is_blog_installed() ) { echo "Already installed\n"; exit; }
$result = wp_install( 'EurasiaPulse', 'admin', 'admin@example.com', true, '', 'admin-local-password', 'en_US' );
echo "Installed. User ID: {$result['user_id']}\n";
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'timezone_string', 'Asia/Almaty' );
update_option( 'blogdescription', 'Politics and analysis across Central Asia and Eurasia' );
update_option( 'default_comment_status', 'closed' );
update_option( 'default_ping_status', 'closed' );
update_option( 'show_on_front', 'posts' );
echo "Options set\n";
