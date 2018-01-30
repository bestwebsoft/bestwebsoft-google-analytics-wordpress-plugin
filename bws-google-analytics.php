<?php
/*
Plugin Name: Google Analytics by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/bws-google-analytics/
Description: Add Google Analytics code to WordPress website and track basic stats.
Author: BestWebSoft
Text Domain: bws-google-analytics
Domain Path: /languages
Version: 1.7.3
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2017  BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once( dirname( __FILE__ ) . '/includes/deprecated.php' );

if ( ! function_exists( 'gglnltcs_admin_menu' ) ) {
	function gglnltcs_admin_menu() {
		bws_general_menu();
		$settings = add_submenu_page( 'bws_panel', 'Google Analytics', 'Google Analytics', 'manage_options', 'bws-google-analytics.php', 'gglnltcs_settings_page' );
		add_action( 'load-' . $settings, 'gglnltcs_add_tabs' );
	}
}

if ( ! function_exists( 'gglnltcs_plugins_loaded' ) ) {
	function gglnltcs_plugins_loaded() {
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'bws-google-analytics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'gglnltcs_init' ) ) {
	function gglnltcs_init() {
		global $gglnltcs_plugin_info;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $gglnltcs_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$gglnltcs_plugin_info = get_plugin_data( __FILE__ );
		}
		/* Check if plugin is compatible with current WP version.*/
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $gglnltcs_plugin_info, '3.9' );

		/* Load options only on the frontend or on the plugin page. */
		if ( ! is_admin() || ( isset( $_REQUEST['page'] ) && "bws-google-analytics.php" == $_REQUEST['page'] ) ) {
			gglnltcs_settings();
		}
	}
}

if ( ! function_exists( 'gglnltcs_admin_init' ) ) {
	function gglnltcs_admin_init() {
		global $bws_plugin_info, $gglnltcs_plugin_info;
		/* Add variable for bws_menu */
		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array( 'id' => '125', 'version' => $gglnltcs_plugin_info['Version'] );
		}
	}
}

/*
* Function to set up default options.
*/
if ( ! function_exists( 'gglnltcs_default_options' ) ) {
	function gglnltcs_default_options() {
		global $gglnltcs_options, $gglnltcs_plugin_info;

		$gglnltcs_default_options = array(
			'plugin_option_version'		=> $gglnltcs_plugin_info["Version"],
			'tracking_id'				=> '',
			'add_tracking_code'			=> 1,
			'display_settings_notice'	=> 1,
			'first_install'				=> strtotime( "now" ),
			'suggest_feature_banner'	=> 1,
			'hide_premium_options'		=> array()
		);
		return $gglnltcs_default_options;
	}
}

if ( ! function_exists( 'gglnltcs_settings' ) ) {
	function gglnltcs_settings() {
		global $gglnltcs_options, $gglnltcs_plugin_info;
		$gglnltcs_default_options = gglnltcs_default_options();
		if ( ! get_option( 'gglnltcs_options' ) ) {
			add_option( 'gglnltcs_options', $gglnltcs_default_options );
		}
		/* get options from DB if exist */
		$gglnltcs_options = get_option( 'gglnltcs_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $gglnltcs_options['plugin_option_version'] ) || ( isset( $gglnltcs_options['plugin_option_version'] ) && $gglnltcs_options['plugin_option_version'] != $gglnltcs_plugin_info['Version'] ) ) {
			/**
			 * @deprecated since 1.7.3
			 * @todo remove after 01.01.2018
			 */
			if ( isset( $gglnltcs_options['plugin_option_version'] ) && version_compare( str_replace( 'pro-', '', $gglnltcs_options['plugin_option_version'] ), '1.7.3', '<' ) ) {
				if ( function_exists( 'gglnltcs_check_options' ) ) {
					gglnltcs_check_options();
				}
			}
			/* end deprecated */
			$gglnltcs_options = array_merge( $gglnltcs_default_options , $gglnltcs_options );
			$gglnltcs_options['plugin_option_version'] = $gglnltcs_plugin_info['Version'];
			$gglnltcs_options['hide_premium_options'] = array();
			gglnltcs_plugin_activate();
			update_option( 'gglnltcs_options', $gglnltcs_options );
		}
	}
}

/**
 * Activation plugin function
 */
if ( ! function_exists( 'gglnltcs_plugin_activate' ) ) {
	function gglnltcs_plugin_activate() {
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			register_uninstall_hook( __FILE__, 'gglnltcs_delete_options' );
			restore_current_blog();
		} else {
			register_uninstall_hook( __FILE__, 'gglnltcs_delete_options' );
		}
	}
}

if ( ! function_exists( 'gglnltcs_get_analytics' ) ) {
	function gglnltcs_get_analytics() {
		global $gglnltcs_options;
		if ( ! isset( $gglnltcs_options['token'] ) ) {
			return;
		}
		require_once 'google-api/autoload.php';
		$client = new Google_Client();
		$client->setApplicationName( 'Google Analytics by BestWebSoft' );
		$client->setClientId( '714548546682-ai821bsdfn2th170q8ofprgfmh5ch7cn.apps.googleusercontent.com' );
		$client->setClientSecret( 'pyBXulcOqPhQGzKiW4kehZZB' );
		$client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
		$client->setDeveloperKey( 'AIzaSyDA7L2CZgY4ud4vv6rw0Yu4GUDyfbRw0f0' );
		$client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
		$client->setAccessToken( $gglnltcs_options['token'] );
		/* Create Analytics Object */
		$analytics = new Google_Service_Analytics( $client );
		return $analytics;
	}
}

/* Displays Google Analytics Settings Page In The Admin Area. */
if ( ! function_exists( 'gglnltcs_settings_page' ) ) {
	function gglnltcs_settings_page() {
		global $gglnltcs_options, $gglnltcs_plugin_info;
		$gglnltcs_default_options = gglnltcs_default_options();
		$curl_enabled = function_exists( 'curl_init' );
		$message = $error = '';
		static $this_plugin;
		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}
		/**
		 * @since 1.6.7
		 * hide ad block
		 */
		if ( isset( $_POST['bws_hide_premium_options'] ) && check_admin_referer( $this_plugin, 'gglnltcs_nonce_name' ) ) {
			$hide_result      = bws_hide_premium_options( $gglnltcs_options );
			$gglnltcs_options = $hide_result['options'];
			if ( ! empty( $hide_result['message'] ) ) {
				$message = '&nbsp;' . $hide_result['message'];
			}
			update_option( 'gglnltcs_options', $gglnltcs_options );
		}
		$hide_pro_block = bws_hide_premium_options_check( $gglnltcs_options );
		if ( isset( $_POST['gglnltcs_form_submit'] ) && check_admin_referer( $this_plugin, 'gglnltcs_nonce_name' ) ) {

			$gglnltcs_options_submit['add_tracking_code'] = isset( $_POST['gglnltcs_add_tracking_code'] ) ? 1 : 0;
			$gglnltcs_options_submit['tracking_id'] = isset( $_POST['gglnltcs_tracking_id'] ) ? stripslashes( esc_html( $_POST['gglnltcs_tracking_id'] ) ) : '';
			if ( 1 == $gglnltcs_options_submit['add_tracking_code'] && '' == $gglnltcs_options_submit['tracking_id'] ) {
				$error = __( 'Tracking code is empty. You must enter a tracking code to add it to your blog.', 'bws-google-analytics' );
			}
			if ( empty( $error ) ) {
				$gglnltcs_options = array_merge( $gglnltcs_options, $gglnltcs_options_submit );
				update_option( 'gglnltcs_options', $gglnltcs_options );
				$message = __( "Settings saved.", 'bws-google-analytics' );
			} else {
				$error .= '&nbsp;' . __( 'Settings are not saved.', 'bws-google-analytics' );
			}
		}
		if ( isset( $_POST['gglnltcs_log_out'] ) && check_admin_referer( $this_plugin, 'gglnltcs_nonce_name' ) ) {
			unset( $gglnltcs_options['token'] );
			unset( $gglnltcs_options['settings'] );
			update_option( 'gglnltcs_options', $gglnltcs_options );
		}
		/* Add restore function */
		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( $this_plugin, 'bws_settings_nonce_name' ) ) {
			$gglnltcs_options = $gglnltcs_default_options;
			update_option( 'gglnltcs_options', $gglnltcs_options );
			$message = __( 'All plugin settings were restored.', 'bws-google-analytics' );
		}
		if ( $curl_enabled ) {
			$analytics = gglnltcs_get_analytics();
		} elseif ( ! isset( $_GET['action'] ) ) {
			$error = __( 'It is impossible to get any information from Google Analytics due to the fact that cURL has been disabled. Please contact with the administrator of your server', 'bws-google-analytics' ) . '.';
		}
		/* Print Tab Navigation */ ?>
		<div class="wrap">
			<h1 id="gglnltcs-main-header"><?php _e( 'Google Analytics Settings', 'bws-google-analytics' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=bws-google-analytics.php"><?php _e( 'Statistics', 'bws-google-analytics' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'settings' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=bws-google-analytics.php&amp;action=settings"><?php _e( 'Settings', 'bws-google-analytics' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=bws-google-analytics.php&amp;action=go_pro"><?php _e( 'Go PRO', 'bws-google-analytics' ); ?></a>
			</h2>
			<div id="gglnltcs-settings-message" class="updated fade below-h2" <?php if ( empty( $message ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div id="gglnltcs-settings-error" class="error below-h2" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( $this_plugin, 'bws_settings_nonce_name' ) ) {
				bws_form_restore_default_confirm( $this_plugin );
			} else {
				bws_show_settings_notice(); ?>
				<div id="gglnltcs-main-content">
					<?php if ( empty( $gglnltcs_options['tracking_id'] ) ) { ?>
						<div class="error below-h2 gglnltcs-empty-code">
							<p>
							<?php if ( isset( $_GET['action'] ) && 'settings' == $_GET['action'] ) { ?>
								<?php _e( 'To enable tracking and collect statistic from your site enter Tracking ID' , 'bws-google-analytics' );
							} else {
								printf(
								'%1$s <a href="admin.php?page=bws-google-analytics.php&action=settings">%2$s</a>',
								__( 'To enable tracking and collect statistic from your site enter Tracking ID in the', 'bws-google-analytics' ),
								__( 'Google Analytics settings', 'bws-google-analytics' )
								);
							} ?>.</p>
						</div>
					<?php }
					if ( ! isset( $_GET['action'] ) && $curl_enabled ) {
						if ( isset( $gglnltcs_options['token'] ) && empty( $_POST['gglnltcs_log_out'] ) ) {
							gglnltcs_statistics_tab( $analytics, $hide_pro_block );
						} else {
							gglnltcs_authenticate( $curl_enabled );
						}
					} elseif ( isset( $_GET['action'] ) ) {
						switch( $_GET['action'] ) {
							case 'settings':
								gglnltcs_settings_tab( $analytics );
								break;
							case 'go_pro':
								gglnltcs_go_pro_tab( $hide_pro_block );
								break;
							default:
								break;
						}
					}
					bws_plugin_reviews_block( $gglnltcs_plugin_info['Name'], 'bws-google-analytics' ); ?>
				</div>
			<?php } ?>
		</div>
	<?php } /* close gglnltcs_settings_page function.*/
}

if ( ! function_exists( 'gglnltcs_authenticate' ) ) {
	function gglnltcs_authenticate( $curl_enabled ) {
		global $gglnltcs_options;
		if ( $curl_enabled ) {
			require_once 'google-api/autoload.php';
			$client = new Google_Client();
			$client->setApplicationName( 'Google Analytics by BestWebSoft' );
			$client->setClientId( '714548546682-ai821bsdfn2th170q8ofprgfmh5ch7cn.apps.googleusercontent.com' );
			$client->setClientSecret( 'pyBXulcOqPhQGzKiW4kehZZB' );
			$client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
			$client->setDeveloperKey( 'AIzaSyDA7L2CZgY4ud4vv6rw0Yu4GUDyfbRw0f0' );
			$client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
			/* If getAccessToken() was successful */
			/* We have an authorized user and can display his website stats.*/
			$analytics = new Google_Service_Analytics( $client );
			/* This will be executed if user get on the page in the first time. */
			if ( isset( $_POST['code'] ) ) {
				if( empty( $_POST['code'] ) ) {
					$redirect = false;
				} else {
					/* This will be executed after user has submitted the form. The post['code'] is set.*/
					try {
						/* We got here from the redirect from a successful authorization grant,
						 * try to fetch the access token. */
						$client->authenticate( stripslashes( esc_html( $_POST['code'] ) ) );
						$redirect = true;
					} catch ( Google_Auth_Exception $e ) {
						/* If user passes invalid Google Authentication Code. */
						$redirect = false;
					}
					/* Save Access Token to the database and reload the page. */
					if ( $redirect && check_admin_referer( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ) ) {
						$gglnltcs_options['token'] = $client->getAccessToken();
						update_option( 'gglnltcs_options', $gglnltcs_options );
						$hide_pro_block = bws_hide_premium_options_check( $gglnltcs_options );
						gglnltcs_statistics_tab( $analytics, $hide_pro_block );
					}
				}
			}
		}
		/* Enter your Google Authentication Code in this box. */
		if ( ! isset( $_POST['code'] ) || ( isset( $_POST['code'] ) && false === $redirect ) ) {
			/*The post['code'] has not been passed yet, so let us offer the user to enter the Google Authentication Code.
			 * First we need to redirect user to the Google Authorization page.
			 * For this reason we create an URL to obtain user authorization. */
			if ( $curl_enabled ) {
				$authUrl  = $client->createAuthUrl();
				$disabled = '';
			} else {
				$authUrl  = '#';
				$disabled = ' disabled="disabled"';
			}
			if ( isset( $_POST['code'] ) && false === $redirect ) { ?>
				<div class="error below-h2"><p><?php _e( 'Invalid code. Please, try again', 'bws-google-analytics' ); ?>.</p></div>
			<?php } ?>
			<div class="gglnltcs-text-information">
				<p><?php _e( "In order to use Google Analytics by BestWebSoft plugin, you must be signed in with a registered Google Account email address and password. If you don't have Google Account you can create it", 'bws-google-analytics' ); ?> <a href="https://www.google.com/accounts/NewAccount" target="_blank"><?php _e( 'here', 'bws-google-analytics' ); ?>.</a></p>
				<input id="gglnltcs-google-sign-in" type="button" class="button-primary" onclick="window.open( '<?php echo $authUrl; ?>', 'activate','width=640, height=480, menubar=0, status=0, location=0, toolbar=0' )" value="<?php _e( 'Authenticate with your Google Account', 'bws-google-analytics' ); ?>"<?php echo $disabled; ?>>
				<noscript>
					<div class="button-primary gglnltcs-google-sign-in">
						<a href="<?php echo $authUrl; ?>" target="_blanket"><?php _e( 'Or Click Here If You Have Disabled Javascript', 'bws-google-analytics' ); ?></a>
					</div>
				</noscript>
				<p class="gglnltcs-authentication-instructions"><?php _e( 'When you finish authorization process you will get Google Authentication Code. You must enter this code in the field below and press "Start Plugin" button. This code will be used to get an Authentication Token so you can access your website stats.', 'bws-google-analytics' ); ?></p>
				<form id="gglnltcs-authentication-form" method="post" action="admin.php?page=bws-google-analytics.php">
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
					<p><input id="gglnltcs-authentication-code-input" type="text" name="code" <?php echo $disabled; ?>><input type="submit" class="button-primary" value="<?php _e( 'Start Plugin', 'bws-google-analytics' ); ?>"<?php echo $disabled; ?>></p>
				</form>
			</div>
		<?php }
	}
}

/* Function that sets tracking code into the site header. */
if ( ! function_exists( 'gglnltcs_past_tracking_code' ) ) {
	function gglnltcs_past_tracking_code() {
		global $gglnltcs_options;
		if ( isset( $gglnltcs_options['tracking_id'] ) && '' != $gglnltcs_options['tracking_id'] && isset( $gglnltcs_options['add_tracking_code'] ) && 1 == $gglnltcs_options['add_tracking_code'] ) {
			$tracking_id = json_encode( $gglnltcs_options['tracking_id'] );
			/* Google tracking code */ ?>
			<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gglnltcs_options['tracking_id']; ?>"></script>
			<script id="gglnltcs-tracking-script">
				window.dataLayer = window.dataLayer || [];
				function gtag(){ dataLayer.push( arguments ); }
				gtag( 'js', new Date() );
				gtag( 'config', '<?php echo $gglnltcs_options['tracking_id']; ?>' );
			</script>
			<?php
		}
	}
}

/* Load Plugin Scripts For Settings Page */
if ( ! function_exists( 'gglnltcs_scripts' ) ) {
	function gglnltcs_scripts() {
		/* Load plugin styles and scripts only on the plugin settings page */
		if ( isset( $_REQUEST['page'] ) && "bws-google-analytics.php" == $_REQUEST['page'] ) {
			global $gglnltcs_plugin_info;
			/* This function is called from the inside of the function "gglnltcs_admin_menu" */
			wp_enqueue_script( 'gglnltcs_google_js_api', 'https://www.gstatic.com/charts/loader.js' ); /* Load Google object. It will be used for chart visualization.*/
			wp_enqueue_style( 'gglnltcs_stylesheet', plugins_url( 'css/style.css', __FILE__ ), array(), $gglnltcs_plugin_info['Version'] );
			wp_enqueue_style( 'gglnltcs_jquery_ui_stylesheet', plugins_url( 'css/jquery-ui.css', __FILE__ ), array(), $gglnltcs_plugin_info['Version'] );
			wp_enqueue_script( 'gglnltcs_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery-ui-datepicker', 'gglnltcs_google_js_api' ), $gglnltcs_plugin_info['Version'] ); /* Load main plugin script. It is important to load google object first.*/
			/* Script Localization */
			wp_localize_script( 'gglnltcs_script', 'gglnltcsLocalize', array(
				'matchPattern'			=> sprintf( __( 'Date values must match the pattern %s.', 'bws-google-analytics' ), 'YYYY-MM-DD' ),
				'metricsValidation'		=> __( 'Any request must supply at least one metric.', 'bws-google-analytics' ),
				'invalidDateRange'		=> __( 'Invalid Date Range.', 'bws-google-analytics' ),
				'chartUsers'			=> __( 'Unique Visitors', 'bws-google-analytics' ),
				'chartNewUsers'			=> __( 'New Visits', 'bws-google-analytics' ),
				'chartSessions'			=> __( 'Visits', 'bws-google-analytics' ),
				'chartBounceRate'		=> __( 'Bounce Rate', 'bws-google-analytics' ),
				'chartAvgSession'		=> __( 'Average Visit Duration', 'bws-google-analytics' ),
				'chartPageviews'		=> __( 'Pageviews', 'bws-google-analytics' ),
				'chartPerSession'		=> __( 'Pages / Visit', 'bws-google-analytics' ),
				'ajaxApiError'			=> __( 'Failed to process the received data correctly', 'bws-google-analytics' ),
				'gglnltcs_ajax_nonce'	=> wp_create_nonce( 'gglnltcs_ajax_nonce_value' )
			) );
			bws_enqueue_settings_scripts();
		}
	}
}

/* Add notices when JavaScript disable, adding banner */
if ( ! function_exists( 'gglnltcs_show_notices' ) ) {
	function gglnltcs_show_notices() {
		global $hook_suffix, $gglnltcs_plugin_info;
		if ( 'plugins.php' == $hook_suffix || ( isset( $_REQUEST['page'] ) && 'bws-google-analytics.php' == $_REQUEST['page'] ) ) { ?>
			<noscript>
				<div class="error below-h2">
					<p><?php _e( 'If you want Google Analytics by BestWebSoft plugin to work correctly, please enable JavaScript in your browser.', 'bws-google-analytics' ); ?></p>
				</div>
			</noscript><?php
		}
		if ( 'plugins.php' == $hook_suffix ) {
			global $gglnltcs_options;
			if ( empty( $gglnltcs_options ) ) {
				$gglnltcs_options = get_option( 'gglnltcs_options' );
			}
			if ( isset( $gglnltcs_options['first_install'] ) && strtotime( '-1 week' ) > $gglnltcs_options['first_install'] ) {
				bws_plugin_banner( $gglnltcs_plugin_info, 'gglnltcs', 'bws-google-analytics', '938dae82c516792dea3980ff61a6af29', '125', '//ps.w.org/bws-google-analytics/assets/icon-128x128.png' );
			}
			bws_plugin_banner_to_settings( $gglnltcs_plugin_info, 'gglnltcs_options', 'bws-google-analytics', 'admin.php?page=bws-google-analytics.php' );
		}
		if ( isset( $_GET['page'] ) && 'bws-google-analytics.php' == $_GET['page'] ) {
			bws_plugin_suggest_feature_banner( $gglnltcs_plugin_info, 'gglnltcs_options', 'bws-google-analytics' );
		}
	}
}

/* add help tab */
if ( ! function_exists( 'gglnltcs_add_tabs' ) ) {
	function gglnltcs_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id'		=> 'gglnltcs',
			'section'	=> '200538749'
		);
		bws_help_tab( $screen, $args );
	}
}

/* Add "Settings" Link On The Plugin Action Page */
if ( ! function_exists( 'gglnltcs_plugin_action_links' ) ) {
	function gglnltcs_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="' . admin_url( 'admin.php?page=bws-google-analytics.php' ) . '">' . __( 'Settings', 'bws-google-analytics' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/* Add "Settings", "FAQ", "Support" Links On The Plugin Page */
if ( ! function_exists ( 'gglnltcs_register_plugin_links' ) ) {
	function gglnltcs_register_plugin_links( $links, $file ) {
		static $this_plugin;
		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}
		if ( $file == $this_plugin ) {
			if ( ! is_network_admin() )
				$links[] = '<a href="admin.php?page=bws-google-analytics.php">' . __( 'Settings', 'bws-google-analytics' ) . '</a>';
			$links[] = '<a href="https://wordpress.org/plugins/bws-google-analytics/faq/" target="_blank">' . __( 'FAQ', 'bws-google-analytics' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'bws-google-analytics' ) . '</a>';
		}
		return $links;
	}
}

/* Build And Print Metrics or Dimensions Table */
if ( ! function_exists( 'gglnltcs_build_table' ) ) {
	function gglnltcs_build_table( $table_type_slug, $table_type, $data, $settings ) {
		$curr_category = $prev_category = '';
		$rows_counter = 0;
		?>
		<table id="gglnltcs-<?php echo $table_type_slug; ?>" class="form-table gglnltcs gglnltcs-metrics">
			<tr>
				<th class="gglnltcs-table-name"><?php echo $table_type; ?></th>
				<?php foreach ( $data as $item ) {
					$rows_counter++;
					$curr_category = $item['category'];
					if ( $curr_category != $prev_category ) {
						echo '<td><hr><strong>' . $curr_category . '</strong><hr>';
						$rows_counter = 0;
					} /* Build checkboxes for metrics options. */
					echo '<p><input id="' . $item['id'] . '" class="gglnltcs_metrics_checkbox" name="' . $item['name'] . '" type="checkbox" value="' . $item['value'] .'"';
					if ( isset( $settings[ $item['name'] ] ) || ( ! $settings && 'gglnltcs-ga-users' == $item['name'] ) ) {
						echo ' checked = "checked">';
					} else {
						echo '>';
					}
					echo '<label title="' . $item['title'] . '" for="' . $item['for'] . '"> ' . $item['label'] . '</label></p>';
					$prev_category = $curr_category;
					if ( $curr_category != $prev_category ) {
						echo '</td>';
					}
					if ( 10 == $rows_counter ) {
						echo '</td><td>';
						$rows_counter = 0;
					}
				} /* close foreach.*/?>
			</tr>
		</table>
	<?php }
}

/* Line Chart Tab */
if ( ! function_exists( 'gglnltcs_statistics_tab' ) ) {
	function gglnltcs_statistics_tab( $analytics, $hide_pro_block ) {
		global $gglnltcs_metrics_data, $gglnltcs_options;
		try {
			if ( empty( $gglnltcs_options ) ) {
				$gglnltcs_options = get_option( 'gglnltcs_options' );
			}
			$settings = isset( $gglnltcs_options['settings'] ) ? $gglnltcs_options['settings'] : '';
			/* Load metrics data */
			gglnltcs_load_metrics();
			$output     = '';
			$accounts   = $analytics->management_accounts->listManagementAccounts();
			$items      = $accounts->getItems();
			$default_id = preg_replace( '/(UA-)(\d+)(-\d+)/i', '${2}', $gglnltcs_options['tracking_id'] );
			if ( count( $items ) ) {
				foreach( $items as $account ) {
					$name     = $account->getName();
					$id       = $account->getId();
					$selected = '';
					if ( ( isset( $settings['gglnltcs_accounts'] ) && $settings['gglnltcs_accounts'] == $name ) || $default_id == $id ) {
						$selected = ' selected="selected"';
					}
					$output .= "<option{$selected} value=\"{$id}\">{$name}</option>";
					$profile_accounts[ $id ]['name'] = $name;
					$accounts_id[] = $id;
				}
				/* Main Form */ ?>
				<form id="gglnltcs-main-form" method="post" action="admin.php?page=bws-google-analytics.php">
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
					<table class="form-table gglnltcs">
						<tr>
							<th><?php _e( 'Accounts', 'bws-google-analytics' ); ?></th>
							<td>
								<select id="gglnltcs-accounts" class="gglnltcs-select" name="gglnltcs_accounts">
									<?php echo $output; ?>
								</select>
							</td>
						</tr>
						<?php gglnltcs_print_webproperties( $analytics, $profile_accounts, $accounts_id, $settings ); ?>
					</table>
					<?php if ( ! $hide_pro_block ) {
						gglnltcs_show_pro_ad();
					}
					gglnltcs_build_table( 'metrics', __( 'Metrics', 'bws-google-analytics' ), $gglnltcs_metrics_data, $settings );
					$start_date = empty( $settings['gglnltcs_start_date'] ) ? date( 'Y-m-d', strtotime( "-1 year" ) ) : $settings['gglnltcs_start_date'];
					$end_date   = empty( $settings['gglnltcs_end_date'] ) ? date( 'Y-m-d', time() ) : $settings['gglnltcs_end_date']; ?>
					<table class="form-table gglnltcs">
						<tr>
							<th><?php _e( 'Time range', 'bws-google-analytics' ); ?></th>
							<td>
								<label for="gglnltcs-start-date" class="gglnltcs-date">
									<?php _e( 'From', 'bws-google-analytics' ); ?>&nbsp;
									<input id="gglnltcs-start-date" class="gglnltcs_to_disable" name="gglnltcs_start_date" type="text" value="<?php echo $start_date; ?>" />
								</label>&nbsp;
								<label for="gglnltcs-end-date" class="gglnltcs-date">
									<?php _e( 'to', 'bws-google-analytics' ); ?>&nbsp;
									<input id="gglnltcs-end-date" class="gglnltcs_to_disable" name="gglnltcs_end_date" type="text" value="<?php echo $end_date; ?>" />
								</label>
								<?php echo bws_add_help_box(
									sprintf( __( 'Date values must match the pattern %s.', 'bws-google-analytics' ), 'YYYY-MM-DD' ) .
									'<br/>' .
									__( 'The gap between dates must not be more than 999 days.', 'bws-google-analytics' )
								); ?>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'View mode', 'bws-google-analytics' ); ?></th>
							<td class="gglnltcs-view-mode">
								<label for="gglnltcs-chart-mode">
									<input type="radio" id="gglnltcs-chart-mode" class="gglnltcs_to_disable" name="gglnltcs_view_mode" value="chart"<?php if ( ! isset( $settings['gglnltcs_view_mode'] ) || 'chart' == $settings['gglnltcs_view_mode'] ) echo ' checked="checked"'; ?>/>
									<?php _e( 'Line chart', 'bws-google-analytics' ); ?>&nbsp;
								</label>&nbsp;
								<label for="gglnltcs-table-mode">
									<input type="radio" id="gglnltcs-table-mode" class="gglnltcs_to_disable" name="gglnltcs_view_mode" value="table"<?php if ( isset( $settings['gglnltcs_view_mode'] ) && 'table' == $settings['gglnltcs_view_mode'] ) echo ' checked="checked"'; ?>/>
									<?php _e( 'Table', 'bws-google-analytics' ); ?>&nbsp;
								</label>
							</td>
						</tr>
						<tr>
							<th></th>
							<td>
								<input id="gglnltcs-get-statistics-button" type="submit" class="button-secondary" value="<?php _e( 'Get Statistic', 'bws-google-analytics' ); ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php if ( isset( $settings['gglnltcs_view_mode'] ) && 'table' == $settings['gglnltcs_view_mode'] ) {
					gglnltcs_get_statistic( $analytics, $settings, $gglnltcs_metrics_data );
				} else { ?>
					<div id="gglnltcs-results-wrapper">
						<div id="gglnltcs-chart"></div>
					</div>
				<?php }
			} else {
				gglnltcs_print_tracking_id_field( true );

			}
		} catch ( Google_Service_Exception $e ) {
			echo __( 'There was an API error', 'bws-google-analytics') . ': ' . $e->getCode() . ' : ' . $e->getMessage();
		} catch ( Exception $e ) {
			gglnltcs_print_tracking_id_field( true );
		}
		gglnltcs_print_log_out_field();
	}
}

/* Tracking Code & Results Tab */
if ( ! function_exists( 'gglnltcs_settings_tab' ) ) {
	function gglnltcs_settings_tab( $analytics ) {
		/* Print insert tracking Code Table */
		$display_error = false;
		if ( ! empty( $analytics ) ) {
			try {
				$accounts = $analytics->management_accounts->listManagementAccounts();
				$items    = $accounts->getItems();
				if ( ! count( $items ) )
					$display_error = true;
			} catch ( Exception $e ) {
				$display_error = true;
			}
		} ?>
		<div id="gglnltcs-tracking-id-table-content">
			<?php gglnltcs_print_tracking_id_field( $display_error ); ?>
		</div>
		<?php
	}
}

/* GO PRO Tab */
if ( ! function_exists( 'gglnltcs_go_pro_tab' ) ) {
	function gglnltcs_go_pro_tab( $hide_pro_block ) {
		global $gglnltcs_plugin_info;
		$plugin_basename = plugin_basename( __FILE__ );
		$go_pro_result   = bws_go_pro_tab_check( $plugin_basename, 'gglnltcs_options' );
		if ( ! empty( $go_pro_result['error'] ) ) { ?>
			<div id="gglnltcs-settings-error" class="error below-h2"><p><strong><?php echo $go_pro_result['error'] ?></strong></p></div>
		<?php }
		if ( ! empty( $go_pro_result['message'] ) ) { ?>
			<div id="gglnltcs-settings-message" class="updated fade below-h2"><p><strong><?php echo $go_pro_result['message'] ?></strong></p></div>
		<?php }
		bws_go_pro_tab_show( $hide_pro_block, $gglnltcs_plugin_info, $plugin_basename, 'bws-google-analytics.php', 'bws-google-analytics-pro.php', 'bws-google-analytics-pro/bws-google-analytics-pro.php', 'bws-google-analytics', '0ceb29947727cb6b38a01b29102661a3', '125', isset( $go_pro_result['pro_plugin_is_activated'] ) );
	}
}

/* Prints Webproperties List */
if ( ! function_exists( 'gglnltcs_print_webproperties' ) ) {
	function gglnltcs_print_webproperties( $analytics, $profile_accounts, $accounts_id, $settings ) {
		global $gglnltcs_options;
		$profile_webproperties = array();
		/* Web Properties: list
		 * https://developers.google.com/analytics/devguides/config/mgmt/v3/mgmtReference/management/webproperties/list */
		if ( !empty( $analytics ) ) {
			try {
				$output = '';
				$webproperties = $analytics->management_webproperties->listManagementWebproperties( '~all' );

				$items = $webproperties->getItems();
				if ( ! count( $items ) ) {
					return false;
				}

				$selected_account = $output = $selected = '';

				foreach( $items as $webproperty ) {
					$account_id  = $webproperty->getAccountId();
					$property_id = $webproperty->getId();

					$profiles = $analytics->management_profiles->listManagementProfiles( $account_id, $property_id );
					$profiles = $profiles->getItems();

					if ( ! count( $profiles ) ) {
						continue;
					}
					if ( ! $selected_account && ( ! $gglnltcs_options['tracking_id'] || ( $gglnltcs_options['tracking_id'] && $gglnltcs_options['tracking_id'] == $property_id ) ) ) {
						$selected_account = $account_id;
					}
					$profile_accounts[ $account_id ]['webproperties'][ $property_id ]['name'] = $webproperty->getName();

					foreach ( $profiles as $profile ) {
						$profile_accounts[ $account_id ]['webproperties'][ $property_id ]['profiles'][$profile->getId()] = $profile->getName();
					}
				}
				/* if tracking ID has not been found in the list of accounts, the first account`s data will be displayed */
				$current_account = array_key_exists( $selected_account, $profile_accounts ) ? $profile_accounts[ $selected_account ] : reset( $profile_accounts );
				foreach( $current_account['webproperties'] as $property_id => $property ) {
					$allowed = true;
					foreach( $property['profiles'] as $profile_id => $profile_name ) {
						if ( $allowed && $gglnltcs_options['tracking_id'] === $property_id ) {
							$selected = ' selected="selected"';
							$allowed  = false;
						} else {
							$selected = '';
						}
						$output .= "<option{$selected} value=\"ga:{$profile_id}\">{$property['name']} ( {$profile_name} )</option>";
					}
				} ?>
				<tr>
					<th><?php _e( 'Webproperties', 'bws-google-analytics' ); ?></th>
					<td>
						<select id="gglnltcs-webproperties" class="gglnltcs-select" name="gglnltcs_webproperties">
							<?php echo $output; ?>
						</select>
					</td>
				</tr>
				<script type="text/javascript">
					var profileAccounts = <?php echo json_encode( $profile_accounts ); ?>;
				</script>
			<?php } catch ( Google_Service_Exception $e ) {
				echo __( 'There was an Analytics API service error', 'bws-google-analytics' ) . ' ' . $e->getCode() . ':' . $e->getMessage();
			} catch ( Exception $e ) {
				echo __( 'There was a general API error', 'bws-google-analytics' ) . ' ' . $e->getCode() . ':' . $e->getMessage();
			}
		}
	}
}

/* Prints Insert tracking Code Form And Input Field */
if ( ! function_exists( 'gglnltcs_print_tracking_id_field' ) ) {
	function gglnltcs_print_tracking_id_field( $display_error = false ) {
		global $gglnltcs_options;
		if ( $display_error ) { ?>
			<div class="error below-h2">
				<p><?php _e( "It seems like you are not registered for Google Analytics or you don't have any Google Analytics Account", 'bws-google-analytics' ); ?>.</p>
				<p><?php _e( 'To gain access to Analytics you must', 'bws-google-analytics' ); ?> <a href="https://www.google.com/analytics/web/provision?et=&authuser=#provision/CreateAccount/" target="_blank"><?php _e( 'register for Google Analytics', 'bws-google-analytics' ); ?></a> <?php _e( 'and create an Analytics account', 'bws-google-analytics' ); ?>.</p>
			</div>
		<?php }
		$tracking_id = isset( $gglnltcs_options['tracking_id'] ) ? $gglnltcs_options['tracking_id'] : ''; ?>
		<form id="gglnltcs-tracking-id-form" class="bws_form" method="post" action="admin.php?page=bws-google-analytics.php&action=settings">
			<div id="gglnltcs-tracking-id-table">
				<table class="form-table gglnltcs">
					<tr>
						<th scope="row">
							Tracking ID
							<?php echo bws_add_help_box(
								__( 'To enable tracking and collect statistic from your site please', 'bws-google-analytics' ) . ':<br/>
									<ol>
										<li><a href="http://www.google.com/accounts/ServiceLogin?service=analytics" target="_blank">' . __( 'sign in', 'bws-google-analytics' ) . '</a> ' . __( 'to your Google Analytics account', 'bws-google-analytics' ) . '</li>
										<li>' . __( 'copy your tracking ID and paste it to the "Tracking ID" text field', 'bws-google-analytics' ) . '</li>
										<li>' . __( 'mark "Add tracking code to blog" chexbox', 'bws-google-analytics' ) . '</li>
										<li>' . __( 'save changes', 'bws-google-analytics' ) . '</li>
									</ol>' .
									__( 'For more info see', 'bws-google-analytics' ) . ' <a href="https://support.google.com/analytics/answer/1009694" target="_blank">' . __( 'Add an account', 'bws-google-analytics' ) . '</a>, <a href="https://support.google.com/analytics/answer/1042508" target="_blank">' . __( 'Set up a property', 'bws-google-analytics' ) . '</a>, <a href="https://support.google.com/analytics/answer/1032385" target="_blank">' . __( 'Find your tracking code, tracking ID, and property number', 'bws-google-analytics' ) . '</a>, <a href="https://support.google.com/analytics/?#topic=3544906" target="_blank">' . __( 'Google Analytics Help Center', 'bws-google-analytics' ) . '</a>'
							); ?>
						</th>
						<td>
							<input type="text" name="gglnltcs_tracking_id" value="<?php echo $tracking_id; ?>" />
							<br />
							<label><input id='gglnltcs-add-tracking-code-input' type="checkbox" name="gglnltcs_add_tracking_code" value="1" <?php if ( isset( $gglnltcs_options['add_tracking_code'] ) && 1 == $gglnltcs_options['add_tracking_code'] ) echo 'checked="checked"'; ?> /><?php _e( 'Add tracking code to blog', 'bws-google-analytics' ) ?></label>
						</td>
					</tr>
				</table>
			</div>
			<p class="submit">
				<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'bws-google-analytics' ); ?>" />
				<input type="hidden" name="gglnltcs_form_submit" value="submit" />
			</p>
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
		</form>
		<?php
		bws_form_restore_default_settings( plugin_basename( __FILE__ ) );
		if ( $display_error ) {
			exit();
		}
	}
}

/* Prints Log Out Form */
if ( ! function_exists( 'gglnltcs_print_log_out_field' ) ) {
	function gglnltcs_print_log_out_field() {
		global $gglnltcs_options;
		if ( ! empty( $gglnltcs_options['token'] ) ) { ?>
		<table class="form-table gglnltcs" id="gglnltcs-log-out-field">
			<tr>
				<th><?php _e( 'Deauthorize', 'bws-google-analytics' ); ?></th>
				<td>
					<form method="post" action="admin.php?page=bws-google-analytics.php">
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
						<input type="submit" name="gglnltcs_log_out" class="button-secondary" value="<?php _e( 'Log Out', 'bws-google-analytics' ) ?>">
					</form>
				</td>
			</tr>
		</table>
	<?php }
	}
}

/* Get Statistic */
if ( ! function_exists( 'gglnltcs_get_statistic' ) ) {
	function gglnltcs_get_statistic( $analytics, $settings, $metrics_data ) {
		$metrics = array();
		/* Create a comma-separated list of Analytics metrics. E.g., 'ga:visits,ga:pageviews'. */
		foreach ( $metrics_data as $metric ) {
			if ( isset( $settings[ $metric['name'] ] ) )
				$metrics[] = $settings[ $metric['name'] ];
		}
		$metrics = implode( ',', $metrics );
		$start_date = empty( $settings['gglnltcs_start_date'] ) ? '365daysAgo' : $settings['gglnltcs_start_date'];
		$end_date   = empty( $settings['gglnltcs_end_date'] )   ? 'today'      : $settings['gglnltcs_end_date']; ?>
		<noscript>
			<style>
				#gglnltcs-results-wrapper {
					max-width: 100%;
					min-height: 260px;
					overflow-x: auto;
				}
			</style>
		</noscript>
		<div id="gglnltcs-results-wrapper"><?php
			/* Get Analytics data for a view (profile).
			 * https://developers.google.com/analytics/devguides/reporting/core/v3/coreDevguide */
			try { ?>
				<table id="gglnltcs-group-by-Y-M-D" class="form-table">
					<tr>
						<th><?php _e( 'Results', 'bws-google-analytics' ); ?></th>
						<td class="hide-if-no-js">
							<span><?php _e( 'Group by', 'bws-google-analytics' ); ?></span>
							<input type="button" class="button-secondary" value="<?php _e( 'Year', 'bws-google-analytics' ); ?>">
							<input type="button" class="button-secondary" value="<?php _e( 'Month', 'bws-google-analytics' ); ?>">
							<input type="button" class="button-secondary gglnltcs-selected" value="<?php _e( 'Day', 'bws-google-analytics' ); ?>">
						</td>
					</tr>
				</table>
				<?php $dimensions = array( 'dimensions' => 'ga:year,ga:month,ga:day' );
				$results = $analytics->data_ga->get( $settings['gglnltcs_webproperties'], $start_date, $end_date, $metrics, $dimensions );
				$results = gglnltcs_print_results( $results, $metrics_data );
				echo $results;

				$dimensions = array( 'dimensions' => 'ga:year,ga:month' );
				$results = $analytics->data_ga->get( $settings['gglnltcs_webproperties'], $start_date, $end_date, $metrics, $dimensions );
				$results = gglnltcs_print_results( $results, $metrics_data );
				echo $results;

				$dimensions = array( 'dimensions' => 'ga:year' );
				$results = $analytics->data_ga->get( $settings['gglnltcs_webproperties'], $start_date, $end_date, $metrics, $dimensions );
				$results = gglnltcs_print_results( $results, $metrics_data );
				echo $results;
			} catch ( Google_Service_Exception $e ) { ?>
				<table class="gglnltcs gglnltcs-results">
					<tr>
						<td><div class="gglnltcs-bad-results gglnltcs-unsuccess-message"><?php echo __( 'There was an Analytics API service error', 'bws-google-analytics' ) . ' ' . $e->getCode() . ':' . $e->getMessage(); ?></div></td>
					</tr>
				</table>
			<?php } catch ( Exception $e ) { ?>
				<table class="gglnltcs gglnltcs-results">
					<tr>
						<td><div class="gglnltcs-bad-results gglnltcs-unsuccess-message"><?php echo __( 'There was a general API error', 'bws-google-analytics' ) . ' ' . $e->getCode() . ':' . $e->getMessage(); ?></div></td>
					</tr>
				</table>
			<?php } ?>
		</div>
	<?php }
}

/* Prints Results Tables On The Table Chart Tab */
if ( ! function_exists( 'gglnltcs_print_results' ) ) {
	function gglnltcs_print_results( $results, $gglnltcs_metrics_data ) {
		/* Print results */
		if ( count( $results->getRows() ) ) {
			$i = 0;
			$table = '<div class="gglnltcs-results-table-wrap">
						<table class="gglnltcs gglnltcs-results">';

			$dimension_labels = array(
				'ga:year'	=> __( 'Year', 'bws-google-analytics' ),
				'ga:month'	=> __( 'Month', 'bws-google-analytics' ),
				'ga:day'	=> __( 'Day', 'bws-google-analytics' )
			);
			foreach ( $results->getColumnHeaders() as $header ) {
				$label = isset( $gglnltcs_metrics_data[ $header->name ] ) ? $gglnltcs_metrics_data[ $header->name ]['label'] : $dimension_labels[ $header->name ];
				$table .= '<tr class="gglnltcs-row-' . trim( substr( $header->name, 3 ) ). '"><th>' . $label . '<th>';
				if ( 'ga:month' == $header->name ) {
					$months = array(
						'01' => __( 'Jan', 'bws-google-analytics' ),
						'02' => __( 'Feb', 'bws-google-analytics' ),
						'03' => __( 'Mar', 'bws-google-analytics' ),
						'04' => __( 'Apr', 'bws-google-analytics' ),
						'05' => __( 'May', 'bws-google-analytics' ),
						'06' => __( 'Jun', 'bws-google-analytics' ),
						'07' => __( 'Jul', 'bws-google-analytics' ),
						'08' => __( 'Aug', 'bws-google-analytics' ),
						'09' => __( 'Sep', 'bws-google-analytics' ),
						'10' => __( 'Oct', 'bws-google-analytics' ),
						'11' => __( 'Nov', 'bws-google-analytics' ),
						'12' => __( 'Dec', 'bws-google-analytics' )
					);
					foreach ( $results->getRows() as $row ) {
						$table .= '<td>' . $months[ $row[ $i ] ] . '</td>';
					}
				} else {
					foreach ( $results->getRows() as $row ) {
						$cell = floatval( $row[ $i ] );
						if ( 'ga:avgSessionDuration' == $header->name ) {
							$cell = gmdate( 'H:i:s', $cell );
						} else {
							$cell = round( $cell, 2 );
							$cell = $cell + 0;
						}
						$table .= '<td>' . $cell . ( ( 'ga:bounceRate' == $header->name && 0 != $cell ) ? '%' : '' ) . '</td>';
					}
				}
				$table .= "</tr>\n";
				$i++;
			} /* close foreach. */
			$table .= '</table>
						</div>';
		} else {
			$table .= '<table class="gglnltcs gglnltcs-results">
						<tr>
							<th><h3>' . _e( 'Results', 'bws-google-analytics' ) . '</h3></th>
							<td><div class="gglnltcs-bad-results">' . __( 'No results found', 'bws-google-analytics' ) . '.<div></td>
						</tr>
					</table>';
		}
		return $table;
	}
}

/**
 * Display ad block of the pro version of the plugin
 * @since  1.6.7
 * @uses   on plugin settings page
 * @see    gglnltcs_line_chart_tab(), gglnltcs_table_chart_tab()
 * @param  void
 * @return void
 */
if ( ! function_exists( 'gglnltcs_show_pro_ad' ) ) {
	function gglnltcs_show_pro_ad() {
		global $gglnltcs_plugin_info, $wp_version; ?>
		<div class="bws_pro_version_bloc">
			<div class="bws_pro_version_table_bloc">
				<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'bws-google-analytics' ); ?>"></button>
				<div class="bws_table_bg"></div>
				<table class="form-table gglnltcs bws_pro_version">
					<tr>
						<th><?php _e( 'Reporting', 'bws-google-analytics' ); ?></th>
						<td><!-- Reporting -->
							<select disabled="disabled" multiple="multiple" size="2" style="height: 50px;width: 150px;">
								<option><?php _e( 'Visits', 'bws-google-analytics' ); ?></option>
								<option><?php _e( 'Goals', 'bws-google-analytics' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>
			<div class="bws_pro_version_tooltip">
				<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/bws-google-analytics/?k=0ceb29947727cb6b38a01b29102661a3&pn=125&v=<?php echo $gglnltcs_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Google Analytics Pro by BestWebSoft plugin"><?php _e( 'Learn More', 'bws-google-analytics' ); ?></a>
				<div class="clear"></div>
			</div>
		</div>
	<?php }
}

/* Ajax Processing Function */
if ( ! function_exists( 'gglnltcs_process_ajax' ) ) {
	function gglnltcs_process_ajax() {
		global $gglnltcs_options, $gglnltcs_metrics_data;
		/* check ajax value */
		check_ajax_referer( 'gglnltcs_ajax_nonce_value', 'gglnltcs_nonce' );
		/* Get options from the database and set them to the global array */
		gglnltcs_settings();
		/* Create Analytics Object */
		$analytics = gglnltcs_get_analytics();
		/* Parse form data that came from ajax */
		parse_str( $_POST['settings'], $settings );
		/* Line Chart Tab */
		if ( ( 'line_chart' == $_POST['tab'] ) && ( ! empty( $analytics ) ) ) {
			$start_date = empty( $settings['gglnltcs_start_date'] ) ? '365daysAgo'   : $settings['gglnltcs_start_date'];
			$end_date = empty( $settings['gglnltcs_end_date'] )     ? 'today'        : $settings['gglnltcs_end_date'];
			$metrics = 'ga:users,ga:newUsers,ga:sessions,ga:bounceRate,ga:avgSessionDuration,ga:pageviews,ga:pageviewsPerSession';
			$dimensions = array( 'dimensions' => 'ga:year,ga:month,ga:day' );
			$results = $analytics->data_ga->get( $settings['gglnltcs_webproperties'], $start_date, $end_date, $metrics, $dimensions );
			$chart_data = $chart_date = $chart_users = $chart_new_users = $chart_sessions = $chart_bounce_rate = $chart_avg_session = $chart_pageviews = $chart_per_session = array();
			foreach ( $results->getRows() as $row ) {
				$chart_date[]        = array( $row[0], $row[1], $row[2] );
				$chart_users[]       = array( $row[3] );
				$chart_new_users[]   = array( $row[4] );
				$chart_sessions[]    = array( $row[5] );
				$chart_bounce_rate[] = array( $row[6] );
				$chart_avg_session[] = array( $row[7] );
				$chart_pageviews[]   = array( $row[8] );
				$chart_per_session[] = array( $row[9] );
			}
			array_push( $chart_data,
				$chart_date,
				$chart_users,
				$chart_new_users,
				$chart_sessions,
				$chart_bounce_rate,
				$chart_avg_session,
				$chart_pageviews,
				$chart_per_session
			);
			echo '<!-- start bws-ga-results -->' . json_encode( $chart_data ) . '<!-- end bws-ga-results -->';
		/* Table Tab Chart */
		} elseif ( ( 'table_chart' == $_POST['tab'] ) && ( ! empty( $analytics ) ) ) {
			/* Load metrics data */
			gglnltcs_load_metrics();
			gglnltcs_get_statistic( $analytics, $settings, $gglnltcs_metrics_data );
		}
		/* Save updated settings to the database */
		/* prepare data for update_option - unset unwanted $_POST vars */
		unset( $settings['gglnltcs_nonce_name'], $settings['_wp_http_referer'] );
		$gglnltcs_options['settings'] = $settings;
		update_option( 'gglnltcs_options', $gglnltcs_options );
		die();
	}
}

/* Load metrics data */
if ( ! function_exists( 'gglnltcs_load_metrics' ) ) {
	function gglnltcs_load_metrics() {
		global $gglnltcs_metrics_data;
		/*** METRICS ***/
		$gglnltcs_metrics_data = array(
			/** VISITOR **/
			/* Unique Visitors */
			'ga:users' => array(
				'id'		=> 'gglnltcs-ga-users',
				'name'		=> 'gglnltcs-ga-users',
				'value'		=> 'ga:users',
				'title'		=> __( 'Total number of visitors for the requested time period.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-users',
				'label'		=> __( 'Unique Visitors', 'bws-google-analytics' ),
				'category'	=> __( 'Visitor', 'bws-google-analytics' )
			),
			/* New Visits */
			'ga:newUsers' => array(
				'id'		=> 'gglnltcs-ga-new-users',
				'name'		=> 'gglnltcs-ga-new-users',
				'value'		=> 'ga:newUsers',
				'title'		=> __( 'The number of visitors whose visit to your property was marked as a first-time visit.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-new-users',
				'label'		=> __( 'New Visits', 'bws-google-analytics' ),
				'category'	=> __( 'Visitor', 'bws-google-analytics' )
			),
			/** SESSION **/
			/* Visitors */
			'ga:sessions' => array(
				'id'		=> 'gglnltcs-ga-sessions',
				'name'		=> 'gglnltcs-ga-sessions',
				'value'		=> 'ga:sessions',
				'title'		=> __( 'Counts the total number of sessions.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-sessions',
				'label'		=> __( 'Visits', 'bws-google-analytics' ),
				'category'	=> __( 'Session', 'bws-google-analytics' )
			),
			/* Bounce Rate */
			'ga:bounceRate' => array(
				'id'		=> 'gglnltcs-ga-bounce-rate',
				'name'		=> 'gglnltcs-ga-bounce-rate',
				'value'		=> 'ga:bounceRate',
				'title'		=> __( 'The percentage of single-page visits (i.e., visits in which the person left your property from the first page).' , 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-bounce-rate',
				'label'		=> __( 'Bounce Rate', 'bws-google-analytics' ),
				'category'	=> __( 'Session', 'bws-google-analytics' )
			),
			/* Average Visit Duration */
			'ga:avgSessionDuration' => array(
				'id'		=> 'gglnltcs-ga-avg-session-duration',
				'name'		=> 'gglnltcs-ga-avg-session-duration',
				'value'		=> 'ga:avgSessionDuration',
				'title'		=> __( 'The average duration visitor sessions.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-avg-session-duration',
				'label'		=> __( 'Average Visit Duration', 'bws-google-analytics' ),
				'category'	=> __( 'Session', 'bws-google-analytics' )
			),
			/** PAGE TRACKING **/
			/* Pageviews */
			'ga:pageviews' => array(
				'id'		=> 'gglnltcs-ga-pageviews',
				'name'		=> 'gglnltcs-ga-pageviews',
				'value'		=> 'ga:pageviews',
				'title'		=> __( 'The total number of pageviews for your property.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-pageviews',
				'label'		=> __( 'Pageviews', 'bws-google-analytics' ),
				'category'	=> __( 'Page Tracking', 'bws-google-analytics' )
			),
			/* Pages/Visit */
			'ga:pageviewsPerSession' => array(
				'id'		=> 'gglnltcs-ga-pageviews-per-session',
				'name'		=> 'gglnltcs-ga-pageviews-per-session',
				'value'		=> 'ga:pageviewsPerSession',
				'title'		=> __( 'The average number of pages viewed during a visit to your property. Repeated views of a single page are counted.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-pageviews-per-session',
				'label'		=> __( 'Pages / Visit', 'bws-google-analytics' ),
				'category'	=> __( 'Page Tracking', 'bws-google-analytics' )
			)
		);
	}
}

/* Delete All Database Options When User Uninstalls Plugin */
if ( ! function_exists( 'gglnltcs_delete_options' ) ) {
	function gglnltcs_delete_options() {
		global $wpdb;
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'bws-google-analytics-pro/bws-google-analytics-pro.php', $all_plugins ) ) {
			if ( is_multisite() ) {
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					delete_option( 'gglnltcs_options' );
				}
				switch_to_blog( $old_blog );
			} else {
				delete_option( 'gglnltcs_options' );
			}
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

register_activation_hook( __FILE__, 'gglnltcs_plugin_activate' );
add_action( 'init', 'gglnltcs_init' ); /* Load database options.*/
add_action( 'plugins_loaded', 'gglnltcs_plugins_loaded' );
add_action( 'admin_init', 'gglnltcs_admin_init' ); /* bws_plugin_info, gglnltcs_plugin_info, check WP version, plugin localization */
add_action( 'admin_menu', 'gglnltcs_admin_menu' ); /* Add menu page, add submenu page.*/
add_action( 'admin_enqueue_scripts', 'gglnltcs_scripts' );
add_action( 'admin_notices', 'gglnltcs_show_notices' );
add_filter( 'plugin_action_links', 'gglnltcs_plugin_action_links', 10, 2 ); /* Add "Settings" link to the plugin action page.*/
add_filter( 'plugin_row_meta', 'gglnltcs_register_plugin_links', 10, 2 ); /* Additional links on the plugin page - "Settings", "FAQ", "Support".*/
add_action( 'wp_footer', 'gglnltcs_past_tracking_code' ); /* Insert tracking code when front page loads.*/
add_action( 'wp_ajax_gglnltcs_action','gglnltcs_process_ajax' ); /* Ajax processing function.*/