<?php
/** Set sample theme mods for local testing. Usage: php dev/set-mods.php <wp-dir> <port> */
$dir  = $argv[1] ?? '.local/wordpress';
$port = $argv[2] ?? '8080';
$_SERVER['HTTP_HOST'] = '127.0.0.1:' . $port;
require __DIR__ . '/../' . $dir . '/wp-load.php';
set_theme_mod( 'language_links', "RU|http://127.0.0.1:$port/\nKK|http://127.0.0.1:$port/kk/\nUZ|http://127.0.0.1:$port/uz/\nKY|http://127.0.0.1:$port/ky/\nZH|http://127.0.0.1:$port/zh/" );
set_theme_mod( 'social_x', 'https://x.com/example' );
set_theme_mod( 'social_telegram', 'https://t.me/example' );
set_theme_mod( 'social_instagram', 'https://instagram.com/example' );
set_theme_mod( 'social_facebook', 'https://facebook.com/example' );
echo "mods set for $dir\n";
