<?php
/**
* Includes deprecated functions
*/

/**
 * Save converted options
 * @deprecated since 1.7.3
 * @todo remove after 01.01.2018
 */
if ( ! function_exists( 'gglnltcs_check_options' ) ) {
	function gglnltcs_check_options() {
		global $gglnltcs_options, $wpdb;

		if ( isset( $gglnltcs_options['settings'] ) ) {
			if ( isset( $gglnltcs_options['settings']['gglnltcs-ga-visits'] ) ) {
				unset( $gglnltcs_options['settings']['gglnltcs-ga-visits'] );
				$gglnltcs_options['settings']['gglnltcs-ga-users'] = 'ga:users';
			}
			if ( isset( $gglnltcs_options['settings']['gglnltcs-ga-new-visits'] ) ) {
				unset( $gglnltcs_options['settings']['gglnltcs-ga-new-visits'] );
				$gglnltcs_options['settings']['gglnltcs-ga-new-users'] = 'ga:newUsers';
			}
			if ( isset( $gglnltcs_options['settings']['gglnltcs-ga-visitors'] ) ) {
				unset( $gglnltcs_options['settings']['gglnltcs-ga-visitors'] );
				$gglnltcs_options['settings']['gglnltcs-ga-sessions'] = 'ga:sessions';
			}
			if ( isset( $gglnltcs_options['settings']['gglnltcs-ga-visit-bounce-rate'] ) ) {
				unset( $gglnltcs_options['settings']['gglnltcs-ga-visit-bounce-rate'] );
				$gglnltcs_options['settings']['glnltcs-ga-bounce-rate'] = 'ga:bounceRate';
			}
			if ( isset( $gglnltcs_options['settings']['gglnltcs-ga-avg-time-on-site'] ) ) {
				unset( $gglnltcs_options['settings']['gglnltcs-ga-avg-time-on-site'] );
				$gglnltcs_options['settings']['gglnltcs-ga-avg-session-duration'] = 'ga:avgSessionDuration';
			}
			if ( isset( $gglnltcs_options['settings']['gglnltcs-ga-pageviews-per-visit'] ) ) {
				unset( $gglnltcs_options['settings']['gglnltcs-ga-pageviews-per-visit'] );
				$gglnltcs_options['settings']['gglnltcs-ga-pageviews-per-session'] = 'ga:pageviewsPerSession';
			}
		}
	}
}