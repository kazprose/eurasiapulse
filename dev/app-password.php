<?php
/** Create (or rotate) an application password for the admin user and print it. Run: php dev/app-password.php */
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
require __DIR__ . '/../.local/wordpress/wp-load.php';
$user = get_user_by( 'login', 'admin' );
foreach ( WP_Application_Passwords::get_user_application_passwords( $user->ID ) as $existing ) {
	if ( 'rest-test' === $existing['name'] ) {
		WP_Application_Passwords::delete_application_password( $user->ID, $existing['uuid'] );
	}
}
$created = WP_Application_Passwords::create_new_application_password( $user->ID, array( 'name' => 'rest-test' ) );
if ( is_wp_error( $created ) ) {
	fwrite( STDERR, $created->get_error_message() . "\n" );
	exit( 1 );
}
echo $created[0];
