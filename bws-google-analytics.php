<?php
/*
Plugin Name: Google Analytics by BestWebSoft 
Plugin URI: http://bestwebsoft.com/products/
Description: This plugin allows you to retrieve basic stats from Google Analytics account and adds the necessary tracking code to your blog.
Author: BestWebSoft
Text Domain: bws-google-analytics
Domain Path: /languages
Version: 1.6.5
Author URI: http://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2015  BestWebSoft  ( http://support.bestwebsoft.com )

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

if ( ! function_exists( 'gglnltcs_add_admin_menu' ) ) {
	function gglnltcs_add_admin_menu() {
		bws_add_general_menu( plugin_basename( __FILE__ ) ); 
		add_submenu_page( 'bws_plugins', 'Google Analytics', 'Google Analytics', 'manage_options', 'bws-google-analytics.php', 'gglnltcs_settings_page' );
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
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$gglnltcs_plugin_info = get_plugin_data( __FILE__ );
		}
		/* Check if plugin is compatible with current WP version.*/
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $gglnltcs_plugin_info, '3.8', '3.3' );
		
		/* Load options only on the frontend or on the plugin page. */
		if ( ! is_admin() || ( isset( $_REQUEST['page'] ) && "bws-google-analytics.php" == $_REQUEST['page'] ) )
			gglnltcs_get_options_from_db();
	}
}

if ( ! function_exists( 'gglnltcs_admin_init' ) ) {
	function gglnltcs_admin_init() {
		global $bws_plugin_info, $gglnltcs_plugin_info;
		/* Add variable for bws_menu */
		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '125', 'version' => $gglnltcs_plugin_info['Version'] );
	}
}

/* Load Previously Saved User Options From The Database */
if ( ! function_exists( 'gglnltcs_get_options_from_db' ) ) {
	function gglnltcs_get_options_from_db() {
		global $gglnltcs_options, $gglnltcs_plugin_info;

		$gglnltcs_option_defaults = array(
			'plugin_option_version' 	=> $gglnltcs_plugin_info["Version"],
			'tracking_id' 				=> '',
			'add_tracking_code' 		=> 1,
			'display_settings_notice'	=>	1,
			'first_install'				=>	strtotime( "now" )
		);
		/* install the option defaults */
		if ( ! get_option( 'gglnltcs_options' ) )
			add_option( 'gglnltcs_options', $gglnltcs_option_defaults );

		/* get options from DB if exist */
		$gglnltcs_options = get_option( 'gglnltcs_options' );
		
		/* Array merge incase this version has added new options */
		if ( ! isset( $gglnltcs_options['plugin_option_version'] ) || $gglnltcs_options['plugin_option_version'] != $gglnltcs_plugin_info['Version'] ) {
			$gglnltcs_option_defaults['display_settings_notice'] = 0;
			$gglnltcs_options = array_merge( $gglnltcs_option_defaults, $gglnltcs_options );
			$gglnltcs_options['plugin_option_version'] = $gglnltcs_plugin_info['Version'];
			update_option( 'gglnltcs_options', $gglnltcs_options );
		}
	}
}

if ( ! function_exists( 'gglnltcs_get_analytics' ) ) {
	function gglnltcs_get_analytics() {
		global $gglnltcs_options;
		if ( ! isset( $gglnltcs_options['token'] ) )
			return;
		require_once 'google-api-php-client/api-code/Google_Client.php';
		require_once 'google-api-php-client/api-code/contrib/Google_AnalyticsService.php';
		$client = new Google_Client();
		$client->setApplicationName( 'Google Analytics by BestWebSoft' );
		$client->setClientId( '714548546682-ai821bsdfn2th170q8ofprgfmh5ch7cn.apps.googleusercontent.com' );
		$client->setClientSecret( 'pyBXulcOqPhQGzKiW4kehZZB' );
		$client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
		$client->setDeveloperKey( 'AIzaSyDA7L2CZgY4ud4vv6rw0Yu4GUDyfbRw0f0' );
		$client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
		$client->setUseObjects( true );
		$client->setAccessToken( $gglnltcs_options['token'] );
		/* Create Analytics Object */
		$analytics 	= new Google_AnalyticsService( $client ); 
		return $analytics;
	}
}

/* Displays Google Analytics Settings Page In The Admin Area. */
if ( ! function_exists( 'gglnltcs_settings_page' ) ) {
	function gglnltcs_settings_page() {
		global $gglnltcs_options, $gglnltcs_plugin_info;
		$message = $error = '';
		static $this_plugin;
		if ( ! $this_plugin )
			$this_plugin = plugin_basename( __FILE__ );
		if ( isset( $_POST['gglnltcs_form_submit'] ) && check_admin_referer( $this_plugin, 'gglnltcs_nonce_name' ) ) {
			$gglnltcs_options_submit['add_tracking_code'] = isset( $_POST['gglnltcs_add_tracking_code'] ) ? 1 : 0;
			$gglnltcs_options_submit['tracking_id'] = isset( $_POST['gglnltcs_tracking_id'] ) ? stripslashes( esc_html( $_POST['gglnltcs_tracking_id'] ) ) : '';
			if ( $gglnltcs_options_submit['add_tracking_code'] == 1 && $gglnltcs_options_submit['tracking_id'] == '' ) {
				$error .= __(  "Tracking code is empty. You must enter a tracking code to add it to your blog.", 'bws-google-analytics' );
			}
			if ( empty( $error ) ) {
				$gglnltcs_options = array_merge( $gglnltcs_options, $gglnltcs_options_submit );
				update_option( 'gglnltcs_options', $gglnltcs_options );
				$message .= __( "Settings saved.", 'bws-google-analytics' );
			} else {
				$error .= '&nbsp;' . __(  "Settings are not saved.", 'bws-google-analytics' );
			}
		}
		/* If user pressed log out button delete his Access Token from database. */
		if ( isset( $_POST['gglnltcs_log_out'] ) && check_admin_referer( $this_plugin, 'gglnltcs_nonce_name' ) ) {
			unset( $gglnltcs_options['token'] );
			unset( $gglnltcs_options['settings'] );
			update_option( 'gglnltcs_options', $gglnltcs_options );
		}
		$analytics 	= gglnltcs_get_analytics();
		/* Print Tab Navigation */ ?>
		<div class="icon32 icon32-bws" id="icon-options-general"></div>
		<h2 id="gglnltcs-main-header"><?php _e( 'Google Analytics Settings', 'bws-google-analytics' ); ?></h2>
		<h2 class="nav-tab-wrapper">
			<a id="gglnltcs-line-nav-tab" class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=bws-google-analytics.php"><?php _e( 'Line Chart', 'bws-google-analytics' ); ?></a>
			<a id="gglnltcs-table-nav-tab" class="nav-tab<?php if ( isset( $_GET['action'] ) && 'table-tab' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=bws-google-analytics.php&amp;action=table-tab"><?php _e( 'Table Chart', 'bws-google-analytics' ); ?></a>
			<a id="gglnltcs-tracking-code-nav-tab" class="nav-tab<?php if ( isset( $_GET['action'] ) && 'tracking-code-tab' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=bws-google-analytics.php&amp;action=tracking-code-tab"><?php _e( 'Tracking Code & Reset', 'bws-google-analytics' ); ?></a>
			<a id="gglnltcs-faq" class="nav-tab" href="http://bestwebsoft.com/products/bws-google-analytics/faq/" target="_blank"><?php _e( 'FAQ', 'bws-google-analytics' ); ?></a>
			<a id="gglnltcs-pro-nav-tab" class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=bws-google-analytics.php&amp;action=go_pro"><?php _e( 'Go PRO', 'bws-google-analytics' ); ?></a>
		</h2>
		<div id="gglnltcs-settings-message" class="updated fade" <?php if ( empty( $message ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
		<div id="gglnltcs-settings-error" class="error" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
		<?php bws_show_settings_notice(); ?>
		<div id="gglnltcs-main-content"><?php 
			/* Line Chart Tab */
			if ( ! isset( $_GET['action'] ) ) {
				if ( ! isset( $gglnltcs_options['token'] ) )
					gglnltcs_authenticate();
				else
					gglnltcs_line_chart_tab( $analytics );
			}
			/* Table Chart Tab */
			if ( isset( $_GET['action'] ) && 'table-tab' == $_GET['action'] ) {
				if ( ! isset( $gglnltcs_options['token'] ) )
					gglnltcs_authenticate();
				else 
					gglnltcs_table_chart_tab( $analytics );
			}
			/* Tracking Code & Reset Tab */
			if ( isset( $_GET['action'] ) && 'tracking-code-tab' == $_GET['action'] ) {
				gglnltcs_tracking_code_tab( true );
			}
			/* GO PRO Tab */
			if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
				gglnltcs_go_pro_tab();
			} ?>
		</div><?php
	} /* close gglnltcs_settings_page function.*/
}

if ( ! function_exists( 'gglnltcs_authenticate' ) ) {
	function gglnltcs_authenticate() {
		global $gglnltcs_options, $gglnltcs_plugin_info;
		require_once 'google-api-php-client/api-code/Google_Client.php';
		require_once 'google-api-php-client/api-code/contrib/Google_AnalyticsService.php';
		$client = new Google_Client();
		$client->setApplicationName( 'Google Analytics by BestWebSoft' );
		$client->setClientId( '714548546682-ai821bsdfn2th170q8ofprgfmh5ch7cn.apps.googleusercontent.com' );
		$client->setClientSecret( 'pyBXulcOqPhQGzKiW4kehZZB' );
		$client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
		$client->setDeveloperKey( 'AIzaSyDA7L2CZgY4ud4vv6rw0Yu4GUDyfbRw0f0' );
		$client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
		$client->setUseObjects( true );
		/* If getAccessToken() was successful */
		/* We have an authorized user and can display his website stats.*/
		$analytics = new Google_AnalyticsService( $client );
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
				} catch ( Google_AuthException $e ) {
					/* If user passes invalid Google Authentication Code. */
					$redirect = false;
				}

				/* Save Access Token to the database and reload the page. */
				if ( $redirect && check_admin_referer( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ) ) {
					$gglnltcs_options['token'] = $client->getAccessToken();
					update_option( 'gglnltcs_options', $gglnltcs_options );
					gglnltcs_line_chart_tab( $analytics );
				}
			}
		}
		/* Enter your Google Authentication Code in this box. */
		if ( ! isset( $_POST['code'] ) || ( isset( $_POST['code'] ) && $redirect === false ) ) {
			/*The post['code'] has not been passed yet, so let us offer the user to enter the Google Authentication Code.
			 * First we need to redirect user to the Google Authorization page.
			 * For this reason we create an URL to obtain user authorization. */
			$authUrl = $client->createAuthUrl(); ?>
			<div class="gglnltcs-text-information">
				<p><?php _e( "In order to use Google Analytics by BestWebSoft plugin, you must be signed in with a registered Google Account email address and password. If you don't have Google Account you can create it", 'bws-google-analytics' ); ?> <a href="https://www.google.com/accounts/NewAccount" target="_blank"><?php _e( 'here', 'bws-google-analytics' ); ?>.</a></p>
				<input id="gglnltcs-google-sign-in" type="button" class="button-primary" onclick="window.open('<?php echo $authUrl; ?>', 'activate','width=640, height=480, menubar=0, status=0, location=0, toolbar=0')" value="<?php _e( 'Authenticate with your Google Account', 'bws-google-analytics' ); ?>">
				<noscript>
					<div class="button-primary gglnltcs-google-sign-in">
						<a href="<?php echo $authUrl; ?>" target="_blanket"><?php _e( 'Or Click Here If You Have Disabled Javascript', 'bws-google-analytics' ); ?></a>
					</div>
				</noscript>
				<p class="gglnltcs-authentication-instructions"><?php _e( 'When you finish authorization process you will get Google Authentication Code. You must enter this code in the field below and press "Start Plugin" button. This code will be used to get an Authentication Token so you can access your website stats.', 'bws-google-analytics' ); ?></p>
				<form id="gglnltcs-authentication-form" method="post" action="admin.php?page=bws-google-analytics.php">
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
					<p><input id="gglnltcs-authentication-code-input" type="text" name="code"><input type="submit" class="button-primary" value="<?php _e( 'Start Plugin', 'bws-google-analytics' ); ?>"></p>
				</form>
			</div><?php 
			/* This message will appear if user enter invalid Google Authentication Code.
			 * Invalid code will cause exception in the $client->authenticate method. */
			if ( isset( $_POST['code'] ) && $redirect === false ) { ?>
				<p><span class="gglnltcs-unsuccess-message"><?php _e( 'Invalid code. Please, try again.', 'bws-google-analytics' ); ?></span></p><?php
			}
			bws_plugin_reviews_block( $gglnltcs_plugin_info['Name'], 'bws-google-analytics' );
		}
	}
}

/* Function that sets tracking code into the site header. */
if ( ! function_exists( 'gglnltcs_past_tracking_code' ) ) {
	function gglnltcs_past_tracking_code() {
		global $gglnltcs_options;
		if ( isset( $gglnltcs_options['tracking_id'] ) && '' != $gglnltcs_options['tracking_id'] && isset( $gglnltcs_options['add_tracking_code'] ) && 1 == $gglnltcs_options['add_tracking_code'] ) {
			$tracking_id = json_encode( $gglnltcs_options['tracking_id'] ); 
			/* Google tracking code */ ?>
			<script id="gglnltcs-tracking-script" type="text/javascript">
				(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
				/* Put our tracking id here */
				ga( 'create', '<?php echo $gglnltcs_options['tracking_id']; ?>', 'auto' );
				ga( 'send', 'pageview' );
			</script><?php
		} else {
			return;
		}
	}
}

/* Load Plugin Scripts For Settings Page */
if ( ! function_exists( 'gglnltcs_scripts' ) ) {
	function gglnltcs_scripts() {
		/* Load plugin styles and scripts only on the plugin settings page */
		if ( isset( $_REQUEST['page'] ) && "bws-google-analytics.php" == $_REQUEST['page'] ) {
			/* This function is called from the inside of the function "gglnltcs_add_admin_menu" */
			wp_enqueue_style( 'gglnltcs_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_style( 'gglnltcs_jquery_ui_stylesheet', plugins_url( 'css/jquery-ui.css', __FILE__ ) );
			wp_enqueue_script( 'gglnltcs_google_js_api', 'http://www.google.com/jsapi' ); /* Load Google object. It will be used for visualization.*/
			wp_enqueue_script( 'gglnltcs_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery-ui-datepicker' ) ); /* Load main plugin script. It is important to load google object first.*/
			/* Script Localization */
			wp_localize_script( 'gglnltcs_script', 'gglnltcsLocalize', array(
				'matchPattern' 			=> 	__( 'Date values must match the pattern YYYY-MM-DD.', 'bws-google-analytics' ),
				'metricsValidation' 	=> 	__( 'Any request must supply at least one metric.', 'bws-google-analytics' ),
				'invalidDateRange'  	=> 	__( 'Invalid Date Range.', 'bws-google-analytics' ),
				'chartVisitors' 		=> 	__( 'Unique Visitors', 'bws-google-analytics' ),
				'chartNewVisits'		=> 	__( 'New Visits', 'bws-google-analytics' ),
				'chartVisits' 			=> 	__( 'Visits', 'bws-google-analytics' ),
				'chartBounceRate'		=> 	__( 'Bounce Rate', 'bws-google-analytics' ),
				'chartAvgTime' 			=> 	__( 'Average Visit Duration', 'bws-google-analytics' ),
				'chartPageviews' 		=> 	__( 'Pageviews', 'bws-google-analytics' ),
				'chartPerVisit' 		=> 	__( 'Pages / Visit', 'bws-google-analytics' ),
				'ajaxApiError'	 		=> 	__( 'Failed to process the received data correctly', 'bws-google-analytics' ),
				'gglnltcs_ajax_nonce'	=> wp_create_nonce( 'gglnltcs_ajax_nonce_value' )
			));
		}
	}
}
	
/* Add notices when JavaScript disable, adding banner */
if ( ! function_exists( 'gglnltcs_show_notices' ) ) {
	function gglnltcs_show_notices() {
		global $hook_suffix;
		if ( 'plugins.php' == $hook_suffix || ( isset( $_REQUEST['page'] ) && 'bws_plugins' == $_REQUEST['page'] ) || ( isset( $_REQUEST['page'] ) && 'bws-google-analytics.php' == $_REQUEST['page'] ) ) { ?>
			<noscript>
				<div class="error">
					<p><?php _e( 'If you want Google Analytics by BestWebSoft plugin to work correctly, please enable JavaScript in your browser!', 'bws-google-analytics' ); ?></p>
				</div>
			</noscript><?php 
		} 
		if ( 'plugins.php' == $hook_suffix ) {   
			global $gglnltcs_plugin_info, $gglnltcs_options;
			if ( empty( $gglnltcs_options ) )
				$gglnltcs_options = get_option( 'gglnltcs_options' );

			if ( isset( $gglnltcs_options['first_install'] ) && strtotime( '-1 week' ) > $gglnltcs_options['first_install'] )
				bws_plugin_banner( $gglnltcs_plugin_info, 'gglnltcs', 'bws-google-analytics', '938dae82c516792dea3980ff61a6af29', '125', '//ps.w.org/bws-google-analytics/assets/icon-128x128.png' );
			
			bws_plugin_banner_to_settings( $gglnltcs_plugin_info, 'gglnltcs_options', 'bws-google-analytics', 'admin.php?page=bws-google-analytics.php' );
		}
	}
}

/* Add "Settings" Link On The Plugin Action Page */
if ( ! function_exists( 'gglnltcs_plugin_action_links' ) ) {
	function gglnltcs_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename( __FILE__ );
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="' . admin_url( 'admin.php?page=bws-google-analytics.php' ) . '">' . __( 'Settings', 'bws-google-analytics' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/* Add  "Settings", "FAQ", "Support" Links On The Plugin Page */
if ( ! function_exists ( 'gglnltcs_register_plugin_links' ) ) {
	function gglnltcs_register_plugin_links( $links, $file ) {
		static $this_plugin;
		if ( ! $this_plugin )
			$this_plugin = plugin_basename( __FILE__ );
		if ( $file == $this_plugin ) {
			if ( ! is_network_admin() )
				$links[] = '<a href="admin.php?page=bws-google-analytics.php">' . __( 'Settings', 'bws-google-analytics' ) . '</a>';
			$links[] = '<a href="http://wordpress.org/plugins/bws-google-analytics/faq/" target="_blank">' . __( 'FAQ', 'bws-google-analytics' ) . '</a>';
			$links[] = '<a href="http://support.bestwebsoft.com">' . __( 'Support', 'bws-google-analytics' ) . '</a>';
		}
		return $links;
	}
}

/* Build And Print Metrics or Dimensions Table */
if ( ! function_exists( 'gglnltcs_build_table' ) ) {
	function gglnltcs_build_table( $table_type_slug, $table_type, $data, $settings ) { 
		$curr_category = $prev_category = '';
		$rows_counter = 0; ?>
		<table id="gglnltcs-<?php echo $table_type_slug; ?>" class="form-table gglnltcs">
			<tr>
				<th class="gglnltcs-table-name"><?php echo $table_type; ?></th>
				<?php foreach ( $data as $item ) {
					$rows_counter++;
					$curr_category = $item['category'];
					if ( $curr_category != $prev_category ) {
						echo '<td><hr><strong>' . $curr_category . '</strong><hr>';
						$rows_counter = 0;
					} /* Build checkboxes for dimensions or metrics options. */
					echo '<p><input id="' . $item['id'] . '" name="' . $item['name'] . '" type="checkbox" value="' . $item['value'] .'"';
					if ( isset( $settings[ $item['name'] ] ) || ( ! $settings &&  $item['name'] == 'gglnltcs-ga-visitors' ) ) { 
						echo ' checked = "checked">';
					} else {
						echo '>';
					}
					echo '<label title="' . $item['title'] . '" for="' . $item['for'] . '"> ' . $item['label'] . '</label></p>';
					$prev_category = $curr_category;
					if ( $curr_category != $prev_category ) {
						echo '</td>';
					}
					if ( $rows_counter == 10 ) {
						echo '</td><td>';
						$rows_counter = 0;
					}
				} /* close foreach.*/?>
			</tr>
		</table><?php
	}
} 

/* Line Chart Tab */
if ( ! function_exists( 'gglnltcs_line_chart_tab' ) ) {
	function gglnltcs_line_chart_tab( $analytics ) {
		global $gglnltcs_plugin_info, $gglnltcs_metrics_data, $gglnltcs_dimensions_data;
		/* Load metrics and dimensions data */
		gglnltcs_load_metrics_and_dimensions();
		/* Main Form */ ?>
		<form id="gglnltcs-main-form" method="post" action="admin.php?page=bws-google-analytics.php">
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
			<table class="form-table gglnltcs"><?php
				/* Print Accounts */ 
				$func_return 	  = gglnltcs_print_accounts( $analytics );
				$profile_accounts = $func_return[0];
				$settings 		  = $func_return[1];
				$accounts_id	  = $func_return[2];
				/* Print Webproperties */
				gglnltcs_print_webproperties( $analytics, $profile_accounts, $accounts_id, $settings ); ?>
			</table><?php
			/* Print Table for Metrics */
			gglnltcs_build_table( 'metrics-line-chart', __( 'Metrics', 'bws-google-analytics' ), $gglnltcs_metrics_data, $settings ); ?>
			<table class="form-table gglnltcs">
				<tr>
					<th><!-- Empty Field --></th>
					<td class="gglnltcs-get-statistics-cell"><!-- Get Data -->
						<input id="gglnltcs-get-statistics-button-line-chart" type="submit" class="button-secondary" value="<?php _e( 'Get Statistic', 'bws-google-analytics' ); ?>">
					</td>
				</tr>
			</table>
		</form>
		<!-- Line Chart -->
		<div id="gglnltcs-continuous_chart_div_container">
			<div id="gglnltcs-continuous_chart_div" style="width: 98%; height: 200px;"></div>
		</div>
		<br/>
		<?php bws_plugin_reviews_block( $gglnltcs_plugin_info['Name'], 'bws-google-analytics' );
	}
}

/* Table Chart Tab */
if ( ! function_exists( 'gglnltcs_table_chart_tab' ) ) {
	function gglnltcs_table_chart_tab( $analytics ) {
		global $gglnltcs_plugin_info, $gglnltcs_metrics_data, $gglnltcs_dimensions_data;
		/* Load metrics and dimensions data */
		gglnltcs_load_metrics_and_dimensions();
		/* Main Form */ ?>
		<form id="gglnltcs-main-form" method="post" action="admin.php?page=bws-google-analytics.php&amp;action=table-tab">
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
			<table class="form-table gglnltcs"><?php
				/* Print Accounts */ 
				$func_return 	  = gglnltcs_print_accounts( $analytics );
				$profile_accounts = $func_return[0];
				$settings 		  = $func_return[1];
				$accounts_id	  = $func_return[2];
				/* Print Webproperties */
				gglnltcs_print_webproperties( $analytics, $profile_accounts, $accounts_id, $settings ); ?>
			</table><?php
			/* Print Table for Metrics */
			gglnltcs_build_table( 'metrics', __( 'Metrics', 'bws-google-analytics' ), $gglnltcs_metrics_data, $settings );
			/* Print Table for Dimensions */
			gglnltcs_build_table( 'dimensions', __( 'Dimensions', 'bws-google-analytics' ), $gglnltcs_dimensions_data, $settings );
			/* Print Table for Date Range */ ?>
			<table id="gglnltcs-date" class="form-table gglnltcs">
				<tr><!-- Start Date -->
					<th><?php _e( 'Start Date', 'bws-google-analytics' ); ?></label></th>
					<td class='gglnltcs-date'>
						<input id="gglnltcs-start-date" name="gglnltcs_start_date" type="text" value="<?php if ( isset( $settings['gglnltcs_start_date'] ) ) echo $settings['gglnltcs_start_date']; ?>">
						<span class="gglnltcs-note"><?php _e( 'Date values must match the pattern YYYY-MM-DD.', 'bws-google-analytics' ); ?></span>
					</td>
				</tr>
				<tr><!-- End Date -->
					<th><?php _e( 'End Date', 'bws-google-analytics' ); ?></th>
					<td class='gglnltcs-date'>
						<input id="gglnltcs-end-date" name="gglnltcs_end_date" type="text" value="<?php if ( isset( $settings['gglnltcs_end_date'] ) ) echo $settings['gglnltcs_end_date']; ?>">
						<span class="gglnltcs-note"><?php _e( 'Date values must match the pattern YYYY-MM-DD.', 'bws-google-analytics' ); ?></span>
						<!-- Get Data -->						
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input id="gglnltcs-get-statistics-button" type="submit" class="button-secondary" value="<?php _e( 'Get Statistic', 'bws-google-analytics' ); ?>">
					</td>				
				</tr>
			</table><?php
			/* Get statistics. */
			if ( isset( $_POST[ 'gglnltcs_accounts' ] ) ) {
				$post_data = $_POST;
				gglnltcs_get_statistic( $analytics, $post_data, $gglnltcs_metrics_data, $gglnltcs_dimensions_data );
			} /* close if isset post accounts.*/?>
		</form>
		<?php bws_plugin_reviews_block( $gglnltcs_plugin_info['Name'], 'bws-google-analytics' );
	}
}

/* Tracking Code & Results Tab */
if ( ! function_exists( 'gglnltcs_tracking_code_tab' ) ) {
	function gglnltcs_tracking_code_tab( $self_redirect ) {
		global $gglnltcs_plugin_info;
		/* Print insert tracking Code Table */ ?>
		<div id="gglnltcs-tracking-id-table-content">
			<?php $tracking_id = gglnltcs_print_tracking_id_field( $self_redirect ); ?>
			<?php if ( ! $tracking_id ) { ?>
				<div id="gglnltcs-tracking-id-instructions">
					<ol>
						<p><?php _e( 'If you want to enable tracking and collect statistic from the', 'bws-google-analytics' ); ?> <strong>"<?php bloginfo( 'name' ); ?>"</strong>, <?php _e( 'you need to insert tracking code to your blog. To do this you should follow next steps', 'bws-google-analytics' ); ?>:</p>
						<li><a href="http://www.google.com/accounts/ServiceLogin?service=analytics" target="_blank"><?php _e( 'Sign in', 'bws-google-analytics' ); ?></a> <?php _e( 'to your Google Analytics account. Click ', 'bws-google-analytics' ); ?> <strong>Admin</strong> <?php _e( 'in the menu bar at the top of any page.', 'bws-google-analytics' ); ?></li>
						<li><?php _e( 'In the', 'bws-google-analytics' ); ?> <em><?php _e( 'Account column', 'bws-google-analytics' ); ?></em>, <?php _e( 'select the account from the dropdown that you want to add the property to.', 'bws-google-analytics' ); ?></li>
						<li><?php _e( 'In the dropdown in the', 'bws-google-analytics' ); ?> <em><?php _e( 'Property column', 'bws-google-analytics' ); ?></em>, <?php _e( 'click', 'bws-google-analytics' ); ?> <strong>Create new property</strong>.</li>
						<li><?php _e( 'Select', 'bws-google-analytics' ); ?> <strong>Website</strong>.</li>
						<li><?php _e( 'Select a tracking method. Click either', 'bws-google-analytics' ); ?> <strong>Universal Analytics</strong> <?php _e( 'or', 'bws-google-analytics' ); ?> <strong>Classic Analytics</strong>. <?php _e( 'We strongly recommend Universal Analytics.', 'bws-google-analytics' ); ?></li>
						<li><?php _e( 'Enter the', 'bws-google-analytics' ); ?> <strong><?php _e( 'name of your WordPress blog', 'bws-google-analytics' ); ?></strong></li>
						<li><?php _e( 'Enter the', 'bws-google-analytics' ); ?> <strong>Website URL</strong> <?php _e( 'of your blog', 'bws-google-analytics' ); ?> <code><?php echo str_replace( 'http://', '', site_url( '', 'http' ) ); ?></code></li>
						<li><?php _e( 'Select an', 'bws-google-analytics' ); ?> <strong>Industry Category</strong></li>
						<li><?php _e( 'Select the', 'bws-google-analytics' ); ?> <strong>Reporting Time Zone</strong></li>
						<li><?php _e( 'Click', 'bws-google-analytics' ); ?> <strong>Get Tracking ID</strong>.</li>
						<li><?php _e( 'Copy', 'bws-google-analytics' ); ?> <strong>Tracking ID</strong> <?php _e( 'that looks like', 'bws-google-analytics' ); ?> <span class="gglnltcs-tracking-id">UA-xxxxx-y</span> <?php _e( 'and past it to the field above.', 'bws-google-analytics' ); ?></li>
						<li><?php _e( 'Check', 'bws-google-analytics' ); ?> <strong><?php _e( 'Add tracking Code To Your Blog', 'bws-google-analytics' ); ?></strong> <?php _e( 'checkbox (if not checked) and click', 'bws-google-analytics' ); ?> <strong><?php _e( 'Save Changes', 'bws-google-analytics' ); ?></strong> <?php _e( 'button.', 'bws-google-analytics' ); ?> </li>
					</ol>
				</div>
			<?php }
			/* Log out field. */
			gglnltcs_print_log_out_field(); ?>
		</div>
		<?php bws_plugin_reviews_block( $gglnltcs_plugin_info['Name'], 'bws-google-analytics' );
	}
}
/* GO PRO Tab */
if ( ! function_exists( 'gglnltcs_go_pro_tab' ) ) {
	function gglnltcs_go_pro_tab() {
		global $gglnltcs_plugin_info;
		$error = '';
		$plugin_basename = plugin_basename( __FILE__ );
		/* GO PRO */
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
			$go_pro_result = bws_go_pro_tab_check( $plugin_basename );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error']; 
		} ?>
		<div id="gglnltcs-settings-error" class="error" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
		<div class="wrap">
			<?php bws_go_pro_tab( $gglnltcs_plugin_info, $plugin_basename, 'bws-google-analytics.php', 'bws-google-analytics-pro.php', 'bws-google-analytics-pro/bws-google-analytics-pro.php', 'bws-google-analytics', '0ceb29947727cb6b38a01b29102661a3', '125', isset( $go_pro_result['pro_plugin_is_activated'] ) ); ?>
		</div>
	<?php }
}
/* Prints Account List */
if ( ! function_exists( 'gglnltcs_print_accounts' ) ) {
	function gglnltcs_print_accounts( $analytics ) {
		global $gglnltcs_options, $gglnltcs_plugin_info;
		$profile_accounts = $accounts_id = array();

		if ( isset( $_POST[ 'gglnltcs_accounts' ] ) && check_admin_referer( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ) ) {
			/* Save checkboxes */
			$settings = $_POST;
			/* prepare data for update_option - unset unwanted $_POST vars and sanitize inpit */
			unset( $settings['gglnltcs_nonce_name'], $settings['_wp_http_referer'] );
			$settings['gglnltcs_start_date'] = stripslashes( esc_html( $settings['gglnltcs_start_date'] ) );
			$settings['gglnltcs_end_date'] = stripslashes( esc_html( $settings['gglnltcs_end_date'] ) );
			/* end of preparation */
			$gglnltcs_options['settings'] = $settings;
			update_option( 'gglnltcs_options', $gglnltcs_options );
		} else if ( isset( $gglnltcs_options['settings'] ) ) {
			/* Get settings from database */
			$settings = $gglnltcs_options['settings'];
		} else {
			$settings = '';
		}
		/* Accounts: list
		 * https://developers.google.com/analytics/devguides/config/mgmt/v3/mgmtReference/management/accounts/list */
		if ( !empty( $analytics ) ) {
			try {
				$output   = '';
				$accounts = $analytics->management_accounts->listManagementAccounts();
				$items    = $accounts->getItems();
				if ( count( $items ) != 0 ) {
					foreach( $items as $account ) {
						$output .= '<option';
						if ( isset( $settings[ 'gglnltcs-accounts' ] ) && $settings[ 'gglnltcs_accounts' ] == $account->getName() ) {
							$output .= ' selected = "selected">';
						} else {
							$output .= '>';
						}
						$output .= $account->getName() . '</option>';
						$profile_accounts[ $account->getId() ][ 'name' ] = $account->getName();
						$accounts_id[] = $account->getId();
					} /* close foreach.*/?>
					<tr>
						<th><?php _e( 'Accounts', 'bws-google-analytics' ); ?></th>
						<td>
							<select id="gglnltcs-accounts" name="gglnltcs_accounts">
								<?php echo $output; ?>
							</select>
						</td>
					</tr><?php 
				} else {
					gglnltcs_no_analytics_accounts();
				}
				$func_return = array( $profile_accounts, $settings, $accounts_id );
				return $func_return;
			} catch ( apiServiceException $e ) {
				echo __( 'There was an API error', 'bws-google-analytics') . ': ' . $e->getCode() . ' : ' . $e->getMessage();
			} catch ( Exception $e ) {
				gglnltcs_no_analytics_accounts();
			}
		}
	}
}

/* Prints Webproperties List */
if ( ! function_exists( 'gglnltcs_print_webproperties' ) ) {
	function gglnltcs_print_webproperties( $analytics, $profile_accounts, $accounts_id, $settings ) {
		$profile_webproperties 	= array();
		/* Web Properties: list
		 * https://developers.google.com/analytics/devguides/config/mgmt/v3/mgmtReference/management/webproperties/list */
		if ( !empty( $analytics ) ) {
			try {
				$output = '';
				$webproperties = $analytics->management_webproperties->listManagementWebproperties( '~all' );
				$items = $webproperties->getItems();
				if ( count( $items ) != 0 ) {
					foreach( $items as $webproperty ) {
						$profile_accounts[ $webproperty->getAccountId() ]['webproperties'][ $webproperty->getId() ] = $webproperty->getName();
						$profiles = $analytics->management_profiles->listManagementProfiles( $webproperty->getAccountId(), $webproperty->getId() );
						$profiles_items = $profiles->getItems();
						if ( count( $profiles_items ) != 0 ) {
							foreach ( $profiles_items as &$profile ) {
								$profile_webproperties[ $webproperty->getId() ] = $profile->getId();
							}
						}
					} /* close foreach.*/
					/* get properties of the first account */
					$first_account = current( $profile_accounts );
					$first_account_webprops = '';
					foreach ( $first_account['webproperties'] as $first_account_webprop ) {
						if ( $first_account_webprops == '' ) {
							$first_account_webprops = '<option selected = "selected">' . $first_account_webprop . '</option>' ;
						} else {
							$first_account_webprops .= '<option>' . $first_account_webprop . '</option>' ;
						}
					} ?>
					<tr>
						<th><?php _e( 'Webproperties', 'bws-google-analytics' ); ?></th>
						<td><!-- Webproperties -->
							<select id="gglnltcs-webproperties" name="gglnltcs_webproperties">
								<?php echo $first_account_webprops; ?>
							</select>
							<!-- View (Profile) ID -->
							<input id="gglnltcs-view-id" name="gglnltcs_view_id" type="hidden">
						</td>
					</tr><?php
					$profile_accounts 	   = json_encode( $profile_accounts );
					$profile_webproperties = json_encode( $profile_webproperties );
					$accounts_id           = json_encode( $accounts_id ); ?>
					<script type="text/javascript">
						var profileAccounts      = <?php echo $profile_accounts; ?>;
						var profileWebproperties = <?php echo $profile_webproperties; ?>;
						var accountsId           = <?php echo $accounts_id; ?>;<?php 
						if ( isset( $settings['gglnltcs-webproperties'] ) ) {
							$selected_webproperty  = json_encode( $settings['gglnltcs_webproperties'] ); ?>
							var selectedWebroperty = <?php echo $selected_webproperty; ?>;
							<?php
						} ?>
						var webPropIDs = [];
						getWebproperties();
						setViewID();
					</script><?php
				} /* close if count items.*/
			} catch ( apiServiceException $e ) {
				echo __( 'There was an Analytics API service error', 'bws-google-analytics' ) . ' ' . $e->getCode() . ':' . $e->getMessage();
			} catch ( apiException $e ) {
				echo __( 'There was a general API error', 'bws-google-analytics' ) . ' ' . $e->getCode() . ':' . $e->getMessage();
			}
		}
	}
}

/* Prints Necessary Instructions For User When He Doesn't Have Google Accounts */
if ( ! function_exists( 'gglnltcs_no_analytics_accounts' ) ) {
	function gglnltcs_no_analytics_accounts() { 
		global $gglnltcs_plugin_info; ?>
		<tr>
			<td>
				<div class="gglnltcs-text-information">
					<p><span class='gglnltcs-unsuccess-message'><?php _e( "It seems like you are not registered for Google Analytics or you don't have any Google Analytics Account.", 'bws-google-analytics' ); ?></span></p>
					<p><?php _e( 'To gain access to Analytics you must', 'bws-google-analytics' ); ?> <a href="https://www.google.com/analytics/web/provision?et=&authuser=#provision/CreateAccount/" target="_blank"><?php _e( 'register for Google Analytics', 'bws-google-analytics' ); ?></a> <?php _e( 'and create an Analytics account, a one-time, simple process.', 'bws-google-analytics' ); ?></p>
					<ol>
						<li><?php _e( 'Select', 'bws-google-analytics' ); ?> <strong>Website</strong> <?php _e( 'option', 'bws-google-analytics' ); ?>.</li>
						<li><?php _e( 'Select a tracking method. Click either', 'bws-google-analytics' ); ?> <strong>Universal Analytics</strong> <?php _e( 'or', 'bws-google-analytics' ); ?> <strong>Classic Analytics</strong>. <?php _e( 'We strongly recommend Universal Analytics.', 'bws-google-analytics' ); ?></li>
						<li><?php _e( 'Under the section called', 'bws-google-analytics' ); ?> <em>Setting up your Account</em>, <?php _e( 'enter an ', 'bws-google-analytics' ); ?> <strong><?php _e( 'Account Name', 'bws-google-analytics' ); ?></strong>. <?php _e( 'Use a specific and descriptive name, so you can easily tell what this account is for when you see the name in the Account list.', 'bws-google-analytics' ); ?></li>
						<li><?php _e( 'Under the section called', 'bws-google-analytics' ); ?> <em>Setting up your Property</em>, <?php _e( 'enter the ', 'bws-google-analytics' ); ?> <strong><?php _e( 'name of your WordPress blog', 'bws-google-analytics' ); ?></strong></li>
						<li><?php _e( 'Enter the', 'bws-google-analytics' ); ?> <strong>Website URL</strong> <?php _e( 'of your blog', 'bws-google-analytics' ); ?> <code><?php echo str_replace( 'http://', '', site_url( '', 'http' ) ); ?></code></li>
						<li><?php _e( 'Select an', 'bws-google-analytics' ); ?> <strong>Industry Category</strong></li>
						<li><?php _e( 'Select the', 'bws-google-analytics' ); ?> <strong>Reporting Time Zone</strong></li>
						<li><?php _e( 'Click', 'bws-google-analytics' ); ?> <strong>Get Tracking ID</strong>.</li>
						<li><?php _e( 'Copy', 'bws-google-analytics' ); ?> <strong>Tracking ID</strong> <?php _e( 'that looks like', 'bws-google-analytics' ); ?> <span class="gglnltcs-tracking-id">UA-xxxxx-y</span> <?php _e( 'and past it to the field below.', 'bws-google-analytics' ); ?></li>
						<li><?php _e( 'Check', 'bws-google-analytics' ); ?> <strong><?php _e( 'Add tracking Code To Your Blog', 'bws-google-analytics' ); ?></strong> <?php _e( 'checkbox (if not checked) and click', 'bws-google-analytics' ); ?> <strong><?php _e( 'Save Changes', 'bws-google-analytics' ); ?></strong> <?php _e( 'button.', 'bws-google-analytics' ); ?> </li>
					</ol>
				</div>
			</td>
		</tr>			
		</tr><?php 
			/* Tracking ID field */
			gglnltcs_print_tracking_id_field(); ?>
		</tr>		
		<?php /* Log out field. */ 
		gglnltcs_print_log_out_field();
		bws_plugin_reviews_block( $gglnltcs_plugin_info['Name'], 'bws-google-analytics' );
		exit();
	}
}

/* Prints Insert tracking Code Form And Input Field */
if ( ! function_exists( 'gglnltcs_print_tracking_id_field' ) ) {
	function gglnltcs_print_tracking_id_field( $self_redirect = false ) {
		global $gglnltcs_options; 
		$tracking_id = isset( $gglnltcs_options['tracking_id'] ) ? $gglnltcs_options['tracking_id'] : ""; ?>		
		<form id="gglnltcs-tracking-id-form" class="bws_form" method="post" action="admin.php?page=bws-google-analytics.php<?php if ( $self_redirect ) echo '&action=tracking-code-tab'; ?>">
			<table class="form-table gglnltcs">
				<tr>
					<th colspan='2'>
						<h3><?php _e( 'Insert tracking Code To Your Blog', 'bws-google-analytics' ); ?></h3>
					</th>
				</tr>
				<tr>
					<th scope="row">Tracking ID</th>					
					<td>
						<input type="text" name="gglnltcs_tracking_id" value="<?php echo $tracking_id; ?>" ><br />
						<label><input id='gglnltcs-add-tracking-code-input' type="checkbox" name="gglnltcs_add_tracking_code" value="1" <?php if ( isset( $gglnltcs_options['add_tracking_code'] ) && 1 == $gglnltcs_options['add_tracking_code'] ) echo 'checked="checked"'; ?> /><?php _e( 'Add tracking Code To Your Blog', 'bws-google-analytics' ) ?></label>
					</td>
				</tr>
			</table>				
			<p class="submit">
				<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'bws-google-analytics' ); ?>" />
				<input type="hidden" name="gglnltcs_form_submit" value="submit" />
			</p>
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
		</form>
		<?php $tracking_id = ( $tracking_id && $tracking_id !== '""' ) ? true : false;
		return $tracking_id;
	}
}

/* Prints Log Out Form */
if ( ! function_exists( 'gglnltcs_print_log_out_field' ) ) {
	function gglnltcs_print_log_out_field() { 
		global $gglnltcs_options;
		if ( empty( $gglnltcs_options['token'] ) )
			return; ?>
		<table class="gglnltcs" id="gglnltcs-log-out-field">
			<tr>
				<th><h3><?php _e( 'Deauthorize & Reset Settings', 'bws-google-analytics' ); ?></h3></th>
				<td>
					<form method="post" action="admin.php?page=bws-google-analytics.php">
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'gglnltcs_nonce_name' ); ?>
						<input type="submit" name="gglnltcs_log_out" class="button-secondary" value="<?php _e( 'Log Out', 'bws-google-analytics' ) ?>">
					</form>
				</td>
			</tr>
		</table><?php
	}
}

/* Get Statistic */
if ( ! function_exists( 'gglnltcs_get_statistic' ) ) {
	function gglnltcs_get_statistic( $analytics, $post_data, $gglnltcs_metrics_data, $gglnltcs_dimensions_data ) {
		$metrics = $dimensions = array();
		/* Create a comma-separated list of Analytics metrics. E.g., 'ga:visits,ga:pageviews'. */
		foreach ( $gglnltcs_metrics_data as $metric ) {
			if ( isset( $post_data[ $metric['name'] ] ) ) {
				$metrics[] = $post_data[ $metric['name'] ];
			}
		}
		$metrics = implode( ',', $metrics );
		/* Create a comma-separated list of dimensions that determine the sort order for Analytics data. */
		foreach ( $gglnltcs_dimensions_data as $dimension ) {
			if ( isset( $post_data[ $dimension['name'] ] ) ) {
				$dimensions[] = $post_data[ $dimension['name'] ];
			}
		}
		$dimensions = implode( ',', $dimensions );
		/* Do not delete the comment below! */
		/* $dimensions = array( 'dimensions' => $dimensions ); */?>
		<div id="gglnltcs-results-wrapper"><?php
			/* Get Analytics data for a view (profile).
			 * https://developers.google.com/analytics/devguides/reporting/core/v3/coreDevguide */
			try { 
				$dimensions = array( 'dimensions' => 'ga:year,ga:month,ga:day' );
				$results = $analytics->data_ga->get( $post_data['gglnltcs_view_id'], $post_data['gglnltcs_start_date'], $post_data['gglnltcs_end_date'], $metrics, $dimensions );
				$results = gglnltcs_print_results( $results, $gglnltcs_metrics_data, $gglnltcs_dimensions_data );
				echo $results[0], $results[1];

				$dimensions = array( 'dimensions' => 'ga:year,ga:month' );
				$results = $analytics->data_ga->get( $post_data['gglnltcs_view_id'], $post_data['gglnltcs_start_date'], $post_data['gglnltcs_end_date'], $metrics, $dimensions );	
				$results = gglnltcs_print_results( $results, $gglnltcs_metrics_data, $gglnltcs_dimensions_data );
				echo $results[0], $results[1];

				$dimensions = array( 'dimensions' => 'ga:year' );
				$results = $analytics->data_ga->get( $post_data['gglnltcs_view_id'], $post_data['gglnltcs_start_date'], $post_data['gglnltcs_end_date'], $metrics, $dimensions );	
				$results = gglnltcs_print_results( $results, $gglnltcs_metrics_data, $gglnltcs_dimensions_data );
				echo $results[0], $results[1]; ?>
				<div id="gglnltcs-group-by-Y-M-D">
					<span><?php _e( 'Group by', 'bws-google-analytics' ); ?></span>
					<div>
						<input type="button" class="button-secondary" value="<?php _ex( 'Year', 'group-by', 'bws-google-analytics' ); ?>">
						<input type="button" class="button-secondary" value="<?php _ex( 'Month', 'group-by', 'bws-google-analytics' ); ?>">
						<input type="button" class="button-secondary gglnltcs-selected" value="<?php _ex( 'Day', 'group-by', 'bws-google-analytics' ); ?>">
					</div>
				</div><?php
			} catch ( Google_ServiceException $e ) { ?>
				<table class="gglnltcs gglnltcs-results">
					<tr>
						<th><h3><?php _e( 'Results', 'bws-google-analytics' ); ?></h3></th>
						<td><div class="gglnltcs-bad-results gglnltcs-unsuccess-message"><?php _e( 'Invalid Date Range', 'bws-google-analytics' ); ?><div></td>
					</tr>
				</table><?php
			} /* close catch.*/ ?>
		</div><?php
	}
}

/* Prints Results Tables On The Table Chart Tab */
if ( ! function_exists( 'gglnltcs_print_results' ) ) {
	function gglnltcs_print_results( $results, $gglnltcs_metrics_data, $gglnltcs_dimensions_data ) {
		/* Print results */
		if ( count( $results->getRows() ) > 0 ) {
			$i = 0;
			$table  = '<table class="gglnltcs-results gglnltcs">';
			$table .= '<tr><th><h3>' . __( 'Results', 'bws-google-analytics' ) . '</h3></th><td><table class="gglnltcs-table-header gglnltcs" >';
			$second_table = '<td><div class="gglnltcs-table-body gglnltcs"><table>';
			foreach ( $results->getColumnHeaders() as $header ) {
				$table .= '<tr>';
				$table .= '<td>';
				$table .= isset( $gglnltcs_metrics_data[ $header->name ] ) ? $gglnltcs_metrics_data[ $header->name ]['label'] : $gglnltcs_dimensions_data[ $header->name ]['label'];
				$table .= '</td></tr>';
				$second_table .= '<tr class="gglnltcs-row-' . ltrim( $header->name, 'ga:' ) . '">';
				if ( $header->name == 'ga:month' ) {
					$monthes = array(
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
						$second_table .= '<td>' . $monthes[ $row[ $i ] ] . '</td>';
					}
				} else {
					foreach ( $results->getRows() as $row ) {
						$cell = floatval( $row[ $i ] );
						if ( $header->name == 'ga:avgTimeOnSite' ) {
							$cell = gmdate( 'H : i : s', $cell );
						} else {
							$cell = round( $cell, 2 );
							$cell = $cell + 0;
						}
						$second_table .= '<td>' . $cell;
						if ( $header->name == 'ga:visitBounceRate' ) {
							if ( $cell != 0 ) {
								$second_table .= '%';
							}
						}
						$second_table .= '</td>';
					}
				}
				$second_table .= '</td></tr>';
				$i++;
			} /* close foreach. */
			$table .= '</table></td>';
			$second_table .= '</table></div></td></tr></table>';
			$table = array( $table );
			$table[] = $second_table;
		} else {
			$table .= '<table class="gglnltcs-results gglnltcs">
						<tr>
							<th><h3>' . _e( 'Results', 'bws-google-analytics' ) . '</h3></th>
							<td><div class="gglnltcs-bad-results">No results found.<div></td>
						</tr>
					  </table>';
		}
		return $table;
	}
}

/* Ajax Processing Function */
if ( ! function_exists( 'gglnltcs_process_ajax' ) ) {
	function gglnltcs_process_ajax() {
		global $gglnltcs_options, $gglnltcs_metrics_data, $gglnltcs_dimensions_data;
		/* check ajax value */
		check_ajax_referer( 'gglnltcs_ajax_nonce_value', 'gglnltcs_nonce' );
		/* Get options from the database and set them to the global array */
		gglnltcs_get_options_from_db();
		/* Create Analytics Object */
		$analytics = gglnltcs_get_analytics();
		/* Parse form data that came from ajax */
		parse_str( $_POST['settings'], $settings );
		/* Line Chart Tab */
		if ( ( $_POST['tab'] == 'line_chart' ) && ( !empty( $analytics ) ) ) {
			/* Save up date range data for the Table Tab */
			if ( isset( $gglnltcs_options['settings']['gglnltcs_start_date'] ) ) {
				$start_date = $gglnltcs_options['settings']['gglnltcs_start_date'];
				$settings['gglnltcs_start_date'] = $start_date;
			}
			if ( isset( $gglnltcs_options['settings']['gglnltcs_end_date'] ) ) {
				$end_date = $gglnltcs_options['settings']['gglnltcs_end_date'];
				$settings['gglnltcs_end_date'] = $end_date;
			}
			/* Get analytics data that will be passed to the Google Chart */
			$dimensions = 'ga:year,ga:month,ga:day';
			$metrics 	= 'ga:visitors,ga:newVisits,ga:visits,ga:visitBounceRate,ga:avgTimeOnSite,ga:pageviews,ga:pageviewsPerVisit';
			$results 	= $analytics->data_ga->get( $_POST['viewProfileId'], '365daysAgo', 'today', $metrics, array( 'dimensions' => $dimensions ) );
			$monthes = array(
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
			$chart_data = $chart_date = $chart_visitors = $chart_new_visits = $chart_visits = $chart_bounce_rate = $chart_avg_time = $chart_pageviews = $chart_per_visit = array();
			foreach ( $results->getRows() as $row ) {
				$chart_date[] 		 = array( $row[0], $row[1], $row[2] );
				$chart_visitors[]    = array( $row[3] );
				$chart_new_visits[]  = array( $row[4] );
				$chart_visits[]      = array( $row[5] );
				$chart_bounce_rate[] = array( $row[6] );
				$chart_avg_time[]    = array( $row[7] );
				$chart_pageviews[]   = array( $row[8] );
				$chart_per_visit[]   = array( $row[9] );
			}
			array_push( $chart_data, 
				$chart_date,
				$chart_new_visits,
				$chart_visitors,
				$chart_visits,
				$chart_bounce_rate,
				$chart_avg_time,
				$chart_pageviews,
				$chart_per_visit
			);
			$chart_data = json_encode( $chart_data );
			echo $chart_data;
		/* Table Tab Chart */
		} else if ( ( $_POST['tab'] == 'table_chart' ) && ( !empty( $analytics ) ) ) {
			/* Load metrics and dimensions data */
			gglnltcs_load_metrics_and_dimensions();
			gglnltcs_get_statistic( $analytics, $settings, $gglnltcs_metrics_data, $gglnltcs_dimensions_data );
		}
		/* Save updated settings to the database */
		/* prepare data for update_option - unset unwanted $_POST vars */
		unset( $settings['gglnltcs_nonce_name'], $settings['_wp_http_referer'] );
		$gglnltcs_options['settings'] = $settings;
		update_option( 'gglnltcs_options', $gglnltcs_options );
		die();
	}
}

/* Ajax Function To Print Tab Content When User Click Another Tab */
if ( ! function_exists( 'gglnltcs_print_tab_content' ) ) {
	function gglnltcs_print_tab_content() {
		global $gglnltcs_options, $gglnltcs_plugin_info;
		/* check ajax value */
		check_ajax_referer( 'gglnltcs_ajax_nonce_value', 'gglnltcs_nonce' );
		/* Get options from the database and set them to the global array */
		gglnltcs_get_options_from_db();
		/* Create Analytics Object */
		$analytics = gglnltcs_get_analytics();
		if ( !empty( $analytics ) ) {
			if ( $_POST['tab'] == 'line_chart' ) {
				gglnltcs_line_chart_tab( $analytics ); /* Line Chart Tab.*/
			} else if ( $_POST['tab'] == 'table_chart' )
				gglnltcs_table_chart_tab( $analytics ); /* Table Chart Tab.*/
		} else if ( $_POST['tab'] == 'line_chart' || $_POST['tab'] == 'table_chart' ) {
			gglnltcs_authenticate(); 
		}		
		if ( $_POST['tab'] == 'tracking_code' ) {
			gglnltcs_tracking_code_tab( true ); /*Tracking Code & Reset Tab.*/
		}
		if ( $_POST['tab'] == 'go_pro' ) {
			gglnltcs_go_pro_tab(); /* Go Pro Tab.*/
		}
		die();
	}
}

/* Load metrics and dimensions data */
if ( ! function_exists( 'gglnltcs_load_metrics_and_dimensions' ) ) {
	function gglnltcs_load_metrics_and_dimensions() {
		global $gglnltcs_metrics_data, $gglnltcs_dimensions_data;
		/*** METRICS ***/
		$gglnltcs_metrics_data = array(
			/** VISITOR **/
			/* Unique Visitors */
			'ga:visitors' => array( 
				'id' 		=> 'gglnltcs-ga-visitors',
				'name' 		=> 'gglnltcs-ga-visitors',
				'value' 	=> 'ga:visitors',
				'title' 	=> __( 'Total number of visitors for the requested time period.', 'bws-google-analytics' ),
				'for' 		=> 'gglnltcs-ga-visitors',
				'label'		=> __( 'Unique Visitors', 'bws-google-analytics' ),
				'category' 	=> __( 'Visitor', 'bws-google-analytics' )
			),
			/* New Visits */
			'ga:newVisits' => array(
				'id'	 	=> 'gglnltcs-ga-new-visits',
				'name' 		=> 'gglnltcs-ga-new-visits',
				'value' 	=> 'ga:newVisits',
				'title' 	=> __( 'The number of visitors whose visit to your property was marked as a first-time visit.', 'bws-google-analytics' ),
				'for' 		=> 'gglnltcs-ga-new-visits',
				'label' 	=> __( 'New Visits', 'bws-google-analytics' ),
				'category' 	=> __( 'Visitor', 'bws-google-analytics' )
			),
			/** SESSION **/
			/* Visits */
			'ga:visits' => array(
				'id'		=> 'gglnltcs-ga-visits',
				'name'		=> 'gglnltcs-ga-visits',
				'value'		=> 'ga:visits',
				'title'		=> __( 'Counts the total number of sessions.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-visits',
				'label'		=> __( 'Visits', 'bws-google-analytics' ),
				'category'	=> __( 'Session', 'bws-google-analytics' )
			),
			/* Bounce Rate */
			'ga:visitBounceRate' => array(
				'id' 		=> 'gglnltcs-ga-visit-bounce-rate',
				'name'		=> 'gglnltcs-ga-visit-bounce-rate',
				'value'		=> 'ga:visitBounceRate',
				'title'		=> __( 'The percentage of single-page visits (i.e., visits in which the person left your property from the first page).' , 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-visit-bounce-rate',
				'label'		=> __( 'Bounce Rate', 'bws-google-analytics' ),
				'category'	=> __( 'Session', 'bws-google-analytics' )
			),
			/* Average Visit Duration */
			'ga:avgTimeOnSite' => array(
				'id'		=> 'gglnltcs-ga-avg-time-on-site',
				'name'		=> 'gglnltcs-ga-avg-time-on-site',
				'value'		=> 'ga:avgTimeOnSite',
				'title'		=> __( 'The average duration visitor sessions.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-avg-time-on-site',
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
			'ga:pageviewsPerVisit' => array(
				'id'		=> 'gglnltcs-ga-pageviews-per-visit',
				'name'		=> 'gglnltcs-ga-pageviews-per-visit',
				'value'		=> 'ga:pageviewsPerVisit',
				'title'		=> __( 'The average number of pages viewed during a visit to your property. Repeated views of a single page are counted.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-pageviews-per-visit',
				'label'		=> __( 'Pages / Visit', 'bws-google-analytics' ),
				'category' 	=> __( 'Page Tracking', 'bws-google-analytics' )
			)
		);
		/*** DIMENSIONS ***/
		$gglnltcs_dimensions_data = array(
			/** VISITOR */
			/* Visitor Type */
			'ga:visitorType' => array(
				'id' 		=> 'gglnltcs-ga-visitor-type',
				'name' 		=> 'gglnltcs-ga-visitor-type',
				'value' 	=> 'ga:visitorType',
				'title' 	=> __( 'A boolean indicating if a visitor is new or returning. Possible values: New Visitor, Returning Visitor.', 'bws-google-analytics' ),
				'for' 		=> 'gglnltcs-ga-visitor-type',
				'label' 	=> __( 'Visitor Type', 'bws-google-analytics' ),
				'category' 	=> __( 'Visitor', 'bws-google-analytics' )
			),
			/** GEO NETWORKT **/
			/* Continent */
			'ga:continent' => array(
				'id'		=> 'gglnltcs-ga-continent',
				'name' 		=> 'gglnltcs-ga-continent',
				'value' 	=> 'ga:continent',
				'title' 	=> __( 'The continents of property visitors, derived from IP addresses.', 'bws-google-analytics' ),
				'for' 		=> 'gglnltcs-ga-continent',
				'label' 	=> __( 'Continent', 'bws-google-analytics' ),
				'category' 	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Sub Continent Region */
			'ga:subContinent' => array(
				'id' 		=> 'gglnltcs-ga-sub-continent',
				'name' 		=> 'gglnltcs-ga-sub-continent',
				'value'		=> 'ga:subContinent',
				'title'		=> __( 'The sub-continent of visitors, derived from IP addresses. For example, Polynesia or Northern Europe.', 'bws-google-analytics' ), 
				'for'		=> 'gglnltcs-ga-sub-continent',
				'label'		=> __( 'Sub Continent Region', 'bws-google-analytics' ),
				'category' 	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Country / Territory */
			'ga:country' => array(
				'id'		=> 'gglnltcs-ga-country',
				'name'		=> 'gglnltcs-ga-country',
				'value'		=> 'ga:country',
				'title'		=> __( 'The countries of website visitors, derived from IP addresses.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-country',
				'label'		=> __( 'Country / Territory', 'bws-google-analytics' ),
				'category'	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Region */
			'ga:region' => array(
				'id' 		=> 'gglnltcs-ga-region',
				'name' 		=> 'gglnltcs-ga-region',
				'value' 	=> 'ga:region',
				'title' 	=> __( 'The region of visitors to your property, derived from IP addresses. In the U.S., a region is a state, such as New York.', 'bws-google-analytics' ),
				'for' 		=> 'gglnltcs-ga-region',
				'label' 	=> __( 'Region', 'bws-google-analytics' ),
				'category' 	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Metro */
			'ga:metro' => array(

				'id' 		=> 'gglnltcs-ga-metro',
				'name' 		=> 'gglnltcs-ga-metro',
				'value' 	=> 'ga:metro',
				'title' 	=> __( 'The Designated Market Area (DMA) from where traffic arrived on your property.', 'bws-google-analytics' ),
				'for' 		=> 'gglnltcs-ga-metro',
				'label' 	=> __( 'Metro', 'bws-google-analytics' ),
				'category' 	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* City */
			'ga:city' => array(
				'id'		=> 'gglnltcs-ga-city',
				'name'		=> 'gglnltcs-ga-city',
				'value'		=> 'ga:city',
				'title'		=> __( 'The cities of property visitors, derived from IP addresses.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-city',
				'label'		=> __( 'City', 'bws-google-analytics' ),
				'category' 	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Latitude */
			'ga:latitude' => array(
				'id'		=> 'gglnltcs-ga-latitude',
				'name'		=> 'gglnltcs-ga-latitude',
				'value'		=> 'ga:latitude',
				'title'		=> __( 'The approximate latitude of the visitor\'s city. Derived from IP address. Locations north of the equator are represented by positive values and locations south of the equator by negative values.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-latitude',
				'label'		=> __( 'Latitude', 'bws-google-analytics' ),
				'category' 	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Longitude */
			'ga:longitude' => array(
				'id'		=> 'gglnltcs-ga-longitude',
				'name'		=> 'gglnltcs-ga-longitude',
				'value'		=> 'ga:longitude',
				'title'		=> __( 'The approximate longitude of the visitor\'s city. Derived from IP address. Locations east of the meridian are represented by positive values and locations west of the meridian by negative values.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-longitude',
				'label'		=> __( 'Longitude', 'bws-google-analytics' ),
				'category'	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Network Domain */
			'ga:networkDomain' => array(
				'id'		=> 'gglnltcs-ga-network-domain',
				'name'		=> 'gglnltcs-ga-network-domain',
				'value'		=> 'ga:networkDomain',
				'title'		=> __( 'The domain name of the ISPs used by visitors to your property. This is derived from the domain name registered to the IP address.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-network-domain',
				'label'		=> __( 'Network Domain', 'bws-google-analytics' ),
				'category'	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/* Service Provider */
			'ga:networkLocation' => array(
				'id' 		=> 'gglnltcs-ga-network-location',
				'name' 		=> 'gglnltcs-ga-network-location',
				'value' 	=> 'ga:networkLocation',
				'title' 	=> __( 'The name of service providers used to reach your property. For example, if most visitors to your website come via the major service providers for cable internet, you will see the names of those cable service providers in this element.', 'bws-google-analytics' ),
				'for' 		=> 'gglnltcs-ga-network-location',
				'label' 	=> __( 'Service Provider', 'bws-google-analytics' ),
				'category' 	=> __( 'Geo Network', 'bws-google-analytics' )
			),
			/** Platform or Device **/
			/* Browser */
			'ga:browser' => array(
				'id'		=> 'gglnltcs-ga-browser',
				'name'		=> 'gglnltcs-ga-browser',
				'value'		=> 'ga:browser',
				'title'		=> __( 'The names of browsers used by visitors to your website. For example, Internet Explorer or Firefox.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-browser',
				'label'		=> __( 'Browser', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/* Browser Version */
			'ga:browserVersion' => array(
				'id'		=> 'gglnltcs-ga-browser-version',
				'name'		=> 'gglnltcs-ga-browser-version',
				'value'		=> 'ga:browserVersion',
				'title'		=> __( 'The browser versions used by visitors to your website. For example, 2.0.0.14', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-browser-version',
				'label'		=> __( 'Browser Version', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/* Operating System */
			'ga:operatingSystem' => array(
				'id'		=> 'gglnltcs-ga-operating-system',
				'name'		=> 'gglnltcs-ga-operating-system',
				'value'		=> 'ga:operatingSystem',
				'title'		=> __( 'The operating system used by your visitors. For example, Windows, Linux , Macintosh, iPhone, iPod.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-operating-system',
				'label'		=> __( 'Operating System', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/* Operating System Version */
			'ga:operatingSystemVersion' => array(
				'id'		=> 'gglnltcs-ga-operating-system-version',
				'name'		=> 'gglnltcs-ga-operating-system-version',
				'value'		=> 'ga:operatingSystemVersion',
				'title'		=> __( 'The version of the operating system used by your visitors, such as XP for Windows or PPC for Macintosh.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-operating-system-version',
				'label'		=> __( 'Operating System Version', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/* Mobile Device Branding */
			'ga:mobileDeviceBranding' => array(
				'id'		=> 'gglnltcs-ga-mobile-device-branding',
				'name'		=> 'gglnltcs-ga-mobile-device-branding',
				'value'		=> 'ga:mobileDeviceBranding',
				'title'		=> __( 'Mobile manufacturer or branded name.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-mobile-device-branding',
				'label'		=> __( 'Mobile Device Branding', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/* Mobile Device Model */
			'ga:mobileDeviceModel' => array(
				'id'		=> 'gglnltcs-ga-mobile-device-model',
				'name'		=> 'gglnltcs-ga-mobile-device-model',
				'value'		=> 'ga:mobileDeviceModel',
				'title'		=> __( 'Mobile device model.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-mobile-device-model',
				'label'		=> __( 'Mobile Device Model', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/* Mobile Input Selector */
			'ga:mobileInputSelector' => array(
				'id'		=> 'gglnltcs-ga-mobile-input-selector',
				'name'		=> 'gglnltcs-ga-mobile-input-selector',
				'value'		=> 'ga:mobileInputSelector',
				'title'		=> __( 'Selector used on the mobile device (e.g.: touchscreen, joystick, clickwheel, stylus).', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-mobile-input-selector',
				'label'		=> __( 'Mobile Input Selector', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/* Device Category */
			'ga:deviceCategory' => array(
				'id'		=> 'gglnltcs-ga-device-category',
				'name'		=> 'gglnltcs-ga-device-category',
				'value'		=> 'ga:deviceCategory',
				'title'		=> __( 'The type of device: desktop, tablet, or mobile.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-device-category',
				'label'		=> __( 'Device Category', 'bws-google-analytics' ),
				'category'	=> __( 'Platform or Device', 'bws-google-analytics' )
			),
			/** Time **/
			/* Year */
			'ga:year' => array(
				'id'		=> 'gglnltcs-ga-year',
				'name'		=> 'gglnltcs-ga-year',
				'value'		=> 'ga:year',
				'title'		=> __( 'The year of the visit. A four-digit year from 2005 to the current year.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-year',
				'label'		=> _x( 'Year', 'table-head', 'bws-google-analytics' ),
				'category'	=> __( 'Time', 'bws-google-analytics' )
			),
			/* Month of the year */
			'ga:month' => array(
				'id'		=> 'gglnltcs-ga-month',
				'name'		=> 'gglnltcs-ga-month',
				'value'		=> 'ga:month',
				'title'		=> __( 'The month of the visit. A two digit integer from 01 to 12.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-month',
				'label'		=> _x( 'Month', 'table-head', 'bws-google-analytics' ),
				'category'	=> __( 'Time', 'bws-google-analytics' )
			),
			/* Week of the Year */
			'ga:week' => array(
				'id'		=> 'gglnltcs-ga-week',
				'name'		=> 'gglnltcs-ga-week',
				'value'		=> 'ga:week',
				'title'		=> __( 'The week of the visit. A two-digit number from 01 to 53. Each week starts on Sunday.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-week',
				'label'		=> __( 'Week of the Year', 'bws-google-analytics' ),
				'category'	=> __( 'Time', 'bws-google-analytics' )
			),
			/* Day of the month */
			'ga:day' => array(
				'id'		=> 'gglnltcs-ga-day',
				'name'		=> 'gglnltcs-ga-day',
				'value'		=> 'ga:day',
				'title'		=> __( 'The day of the month. A two-digit number from 01 to 31.', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-day',
				'label'		=> _x( 'Day', 'table-head', 'bws-google-analytics' ),
				'category'	=> __( 'Time', 'bws-google-analytics' )
			),
			/* Hour */
			'ga:hour' => array(
				'id'		=> 'gglnltcs-ga-hour',
				'name'		=> 'gglnltcs-ga-hour',
				'value'		=> 'ga:hour',
				'title'		=> __( 'A two-digit hour of the day ranging from 00-23 in the timezone configured for the account. This value is also corrected for daylight savings time, adhering to all local rules for daylight savings time. If your timezone follows daylight savings time, there will be an apparent bump in the number of visits during the change-over hour (e.g. between 1:00 and 2:00) for the day per year when that hour repeats. A corresponding hour with zero visits will occur at the opposite changeover. (Google Analytics does not track visitor time more precisely than hours.)', 'bws-google-analytics' ),
				'for'		=> 'gglnltcs-ga-hour',
				'label'		=> __( 'Hour', 'bws-google-analytics' ),
				'category'	=> __( 'Time', 'bws-google-analytics' )
			)
		);
	}
}

/* Delete All Database Options When User Uninstalls Plugin */
if ( ! function_exists( 'gglnltcs_delete_options' ) ) {
	function gglnltcs_delete_options() {
		global $wpdb;
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
}

add_action( 'init', 'gglnltcs_init' ); /* Load database options.*/
add_action( 'plugins_loaded', 'gglnltcs_plugins_loaded' );
add_action( 'admin_init', 'gglnltcs_admin_init' ); /* bws_plugin_info, gglnltcs_plugin_info, check WP version, plugin localization */
add_action( 'admin_menu', 'gglnltcs_add_admin_menu' ); /* Add menu page, add submenu page.*/
add_action( 'admin_enqueue_scripts', 'gglnltcs_scripts' );
add_action( 'admin_notices', 'gglnltcs_show_notices' );
add_filter( 'plugin_action_links', 'gglnltcs_plugin_action_links', 10, 2 ); /* Add "Settings" link to the plugin action page.*/
add_filter( 'plugin_row_meta', 'gglnltcs_register_plugin_links', 10, 2 ); /* Additional links on the plugin page - "Settings", "FAQ", "Support".*/
add_action( 'wp_footer', 'gglnltcs_past_tracking_code' ); /* Insert tracking code when front page loads.*/
add_action( 'wp_ajax_gglnltcs_action','gglnltcs_process_ajax' ); /* Ajax processing function.*/
add_action( 'wp_ajax_gglnltcs_print_tab_content','gglnltcs_print_tab_content' ); /* Print tab content when user click another tab.*/
register_uninstall_hook( __FILE__, 'gglnltcs_delete_options' );