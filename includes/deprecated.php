<?php
/**
 * Contains the list of the deprecated functions
 * @deprecated since 1.7.0
 * @todo remove after 01.06.2017
 */

/*
 * clearing 'uninstall_plugins' option array after old plugin version on multisite as these keys were never used
 */
if ( ! function_exists( 'gglnltcs_clear_uninstall_option' ) ) {
	function gglnltcs_clear_uninstall_option() {
		if ( is_multisite() ) {
			global $wpdb;
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				$uninstallable_plugins = (array) get_option('uninstall_plugins');
				unset( $uninstallable_plugins[ plugin_basename( __FILE__ ) ] );
				update_option('uninstall_plugins', $uninstallable_plugins);
			}
			switch_to_blog( $old_blog );
		}
	}
}