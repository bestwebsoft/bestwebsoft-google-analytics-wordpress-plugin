<?php
/**
 * Contains the extending functionality
 * @since 1.7.4
 */
if ( ! function_exists( 'gglnltcs_get_client' ) ) {
	function gglnltcs_get_client() {
		global $gglnltcs_options;

		require_once plugin_dir_path( __FILE__ ) .  '../google-api/autoload.php';
		$client = new Google_Client();
		$client->setApplicationName( 'Google Analytics by BestWebSoft' );
		$client->setClientId( '714548546682-ai821bsdfn2th170q8ofprgfmh5ch7cn.apps.googleusercontent.com' );
		$client->setClientSecret( 'pyBXulcOqPhQGzKiW4kehZZB' );
		$client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
		$client->setDeveloperKey( 'AIzaSyDA7L2CZgY4ud4vv6rw0Yu4GUDyfbRw0f0' );
		$client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
		if ( ! empty( $gglnltcs_options['token'] ) ) {
			$client->setAccessToken( $gglnltcs_options['token'] );
		}

		return $client;
	}
}

if ( ! function_exists( 'gglnltcs_get_analytics' ) ) {
	function gglnltcs_get_analytics() {
		global $gglnltcs_options;

		if ( ! isset( $gglnltcs_options['token'] ) ) {
			return;
		}

		$client = gglnltcs_get_client();

		/* Create Analytics Object */
		$analytics = new Google_Service_Analytics( $client );
		return $analytics;
	}
}


