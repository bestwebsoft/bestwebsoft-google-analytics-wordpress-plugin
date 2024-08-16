<?php
/**
Plugin Name: Analytics by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/bws-google-analytics/
Description: Add Google Analytics code to WordPress website and track basic stats.
Author: BestWebSoft
Text Domain: bws-google-analytics
Domain Path: /languages
Version: 2.0
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
 */

/**
  © Copyright 2021  BestWebSoft  ( https://support.bestwebsoft.com )

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

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\RunReportRequest;

if ( ! function_exists( 'add_gglnltcs_admin_menu' ) ) {
	/**
	 * Add menu page
	 */
	function add_gglnltcs_admin_menu() {
		global $submenu, $wp_version, $gglnltcs_plugin_info;

		$settings = add_menu_page(
			__( 'Analytics Settings', 'bws-google-analytics' ),
			'Analytics',
			'manage_options',
			'bws-google-analytics.php',
			'gglnltcs_settings_page',
			'none'
		);

		add_submenu_page(
			'bws-google-analytics.php',
			__( 'Analytics Settings', 'bws-google-analytics' ),
			__( 'Settings', 'bws-google-analytics' ),
			'manage_options',
			'bws-google-analytics.php',
			'gglnltcs_settings_page'
		);

		add_submenu_page(
			'bws-google-analytics.php',
			'BWS Panel',
			'BWS Panel',
			'manage_options',
			'gglnltcs-bws-panel',
			'bws_add_menu_render'
		);

		if ( isset( $submenu['bws-google-analytics.php'] ) ) {
			$submenu['bws-google-analytics.php'][] = array(
				'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'custom-search-plugin' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/bws-google-analytics/?k=0ceb29947727cb6b38a01b29102661a3&pn=125&v=' . $gglnltcs_plugin_info['Version'] . '&wp_v=' . $wp_version,
			);
		}
		add_action( 'load-' . $settings, 'gglnltcs_add_tabs' );
	}
}

if ( ! function_exists( 'gglnltcs_plugins_loaded' ) ) {
	/**
	 * Internationalization
	 */
	function gglnltcs_plugins_loaded() {
		load_plugin_textdomain( 'bws-google-analytics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'gglnltcs_init' ) ) {
	/**
	 * Plugin init
	 */
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
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $gglnltcs_plugin_info, '4.5' );

		/* Load options only on the frontend or on the plugin page. */
		if ( ! is_admin() || ( isset( $_GET['page'] ) && 'bws-google-analytics.php' == $_GET['page'] ) ) {
			gglnltcs_settings();
		}
	}
}

if ( ! function_exists( 'gglnltcs_admin_init' ) ) {
	/**
	 * Admin init
	 */
	function gglnltcs_admin_init() {
		global $pagenow, $bws_plugin_info, $gglnltcs_plugin_info, $gglnltcs_options, $wp_filesystem;
		/* Add variable for bws_menu */
		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id' => '125',
				'version' => $gglnltcs_plugin_info['Version'],
			);
		}

		if ( 'plugins.php' == $pagenow ) {
			if ( empty( $gglnltcs_options ) ) {
				gglnltcs_settings();
			}
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				bws_plugin_banner_go_pro( $gglnltcs_options, $gglnltcs_plugin_info, 'gglnltcs', 'bws-google-analytics', '938dae82c516792dea3980ff61a6af29', '125', 'bws-google-analytics' );
			}
		}
		if ( 'admin.php' == $pagenow && isset( $_GET['page'] ) && 'bws-google-analytics.php' === $_GET['page'] ) {
			WP_Filesystem();
			if ( ! $wp_filesystem->exists( dirname( dirname( __FILE__ ) ) . '/google-analytics-v4-api/credentials.json' ) && isset( $gglnltcs_options['api_credentials'] ) ) {
				$success = $wp_filesystem->put_contents( dirname( __FILE__ ) . '/google-analytics-v4-api/credentials.json', $gglnltcs_options['api_credentials'], FS_CHMOD_FILE );
			}
		}
	}
}

if ( ! function_exists( 'gglnltcs_default_options' ) ) {
	/**
	 * Function to set up default options.
	 */
	function gglnltcs_default_options() {
		global $gglnltcs_options, $gglnltcs_plugin_info;

		$gglnltcs_default_options = array(
			'plugin_option_version'   => $gglnltcs_plugin_info['Version'],
			'tracking_id'             => '',
			'property_ids'            => array(),
			'api_credentials'         => '',
			'display_settings_notice'   => 1,
			'first_install'           => strtotime( 'now' ),
			'suggest_feature_banner'  => 1,
			'hide_premium_options'    => array(),
		);

		return $gglnltcs_default_options;
	}
}

if ( ! function_exists( 'gglnltcs_settings' ) ) {
	/**
	 * Settings functions
	 */
	function gglnltcs_settings() {
		global $gglnltcs_options, $gglnltcs_plugin_info;
		$gglnltcs_default_options = gglnltcs_default_options();
		if ( ! get_option( 'gglnltcs_options' ) ) {
			add_option( 'gglnltcs_options', $gglnltcs_default_options );
		}
		/* Get options from DB if exist */
		$gglnltcs_options = get_option( 'gglnltcs_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $gglnltcs_options['plugin_option_version'] ) || ( isset( $gglnltcs_options['plugin_option_version'] ) && $gglnltcs_options['plugin_option_version'] != $gglnltcs_plugin_info['Version'] ) ) {

			$gglnltcs_options = array_merge( $gglnltcs_default_options, $gglnltcs_options );
			$gglnltcs_options['plugin_option_version'] = $gglnltcs_plugin_info['Version'];
			$gglnltcs_options['hide_premium_options'] = array();
			gglnltcs_plugin_activate();
			update_option( 'gglnltcs_options', $gglnltcs_options );
		}
	}
}

if ( ! function_exists( 'gglnltcs_plugin_activate' ) ) {
	/**
	 * Activation plugin function
	 */
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

if ( ! function_exists( 'gglnltcs_settings_page' ) ) {
	/**
	 * Display settings page
	 */
	function gglnltcs_settings_page() {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once( dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php' );
		}
		require_once( dirname( __FILE__ ) . '/includes/class-gglnltcs-settings.php' );
		$page = new Gglnltcs_Settings_Tabs( plugin_basename( __FILE__ ) ); ?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Analytics Settings', 'bws-google-analytics' ); ?></h1>
			<?php $page->display_content(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'gglnltcs_past_tracking_code' ) ) {
	/**
	 * Function that sets tracking code into the site header
	 */
	function gglnltcs_past_tracking_code() {
		global $gglnltcs_options;

		if ( ! empty( $gglnltcs_options['tracking_id'] ) ) {
			/* Google tracking code */
			wp_enqueue_script( 'gglnltcs_googletagmanager', 'https://www.googletagmanager.com/gtag/js?id=' . $gglnltcs_options['tracking_id'], array(), null );

			$script = "window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag('js', new Date());

				gtag('config', '" . $gglnltcs_options['tracking_id'] . "');";

			wp_add_inline_script( 'gglnltcs_googletagmanager', sprintf( $script ) );
		}
	}
}

if ( ! function_exists( 'gglnltcs_add_data_to_script' ) ) {
	/**
	 * Adds async/defer and data attributes to enqueued / registered scripts.
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle The script handle.
	 * @return string Script HTML string.
	 */
	function gglnltcs_add_data_to_script( $tag, $handle ) {
		if ( 'gglnltcs_googletagmanager' === $handle ) {
			$return_string = ' async';
			$tag = preg_replace( ':(?=></script>):', " $return_string", $tag, 1 );
		}
		return $tag;
	}
}

if ( ! function_exists( 'gglnltcs_scripts' ) ) {
	/**
	 * Load Plugin Scripts For Settings Page
	 */
	function gglnltcs_scripts() {
		global $gglnltcs_plugin_info;
		/* css for displaying an icon */
		wp_enqueue_style( 'gglnltcs_admin_page_stylesheet', plugins_url( 'css/admin_page.css', __FILE__ ), array(), $gglnltcs_plugin_info['Version'] );
		/* Load plugin styles and scripts only on the plugin settings page */
		if ( isset( $_REQUEST['page'] ) && 'bws-google-analytics.php' == $_REQUEST['page'] ) {
			/* This function is called from the inside of the function "gglnltcs_admin_menu" */
			wp_enqueue_script( 'gglnltcs_google_js_api', 'https://www.gstatic.com/charts/loader.js' ); /* Load Google object. It will be used for chart visualization.*/
			wp_enqueue_style( 'gglnltcs_stylesheet', plugins_url( 'css/style.css', __FILE__ ), array(), $gglnltcs_plugin_info['Version'] );
			wp_enqueue_style( 'gglnltcs_jquery_ui_stylesheet', plugins_url( 'css/jquery-ui.css', __FILE__ ), array(), $gglnltcs_plugin_info['Version'] );
			wp_enqueue_script( 'gglnltcs_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery-ui-datepicker', 'gglnltcs_google_js_api' ), $gglnltcs_plugin_info['Version'] );
			/* Script Localization */
			wp_localize_script(
				'gglnltcs_script',
				'gglnltcsLocalize',
				array(
					'matchPattern'          => sprintf( __( 'Date values must match the pattern %s.', 'bws-google-analytics' ), 'YYYY-MM-DD' ),
					'metricsValidation'     => __( 'Any request must supply at least one metric.', 'bws-google-analytics' ),
					'invalidDateRange'      => __( 'Invalid Date Range.', 'bws-google-analytics' ),
					'chartUsers'            => __( 'Unique Visitors', 'bws-google-analytics' ),
					'chartNewUsers'         => __( 'New Visits', 'bws-google-analytics' ),
					'chartSessions'         => __( 'Visits', 'bws-google-analytics' ),
					'chartBounceRate'       => __( 'Bounce Rate', 'bws-google-analytics' ),
					'chartAvgSession'       => __( 'Average Visit Duration', 'bws-google-analytics' ),
					'chartPageviews'        => __( 'Pageviews', 'bws-google-analytics' ),
					'chartPerSession'       => __( 'Pages / Visit', 'bws-google-analytics' ),
					'ajaxApiError'          => __( 'Failed to process the received data correctly', 'bws-google-analytics' ),
					'gglnltcs_ajax_nonce'   => wp_create_nonce( 'gglnltcs_ajax_nonce_value' ),
				)
			);
			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
		}
	}
}

if ( ! function_exists( 'gglnltcs_show_notices' ) ) {
	/**
	 * Add notices when JavaScript disable, adding banner
	 */
	function gglnltcs_show_notices() {
		global $hook_suffix, $gglnltcs_plugin_info;

		if ( 'plugins.php' == $hook_suffix ) {
			bws_plugin_banner_to_settings( $gglnltcs_plugin_info, 'gglnltcs', 'bws-google-analytics', 'admin.php?page=bws-google-analytics.php' );
		}

		if ( isset( $_REQUEST['page'] ) && 'bws-google-analytics' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $gglnltcs_plugin_info, 'gglnltcs_options', 'bws-google-analytics' );
		}
	}
}

if ( ! function_exists( 'gglnltcs_add_tabs' ) ) {
	/**
	 * Add help tab
	 */
	function gglnltcs_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id'        => 'gglnltcs',
			'section'   => '200538749',
		);
		bws_help_tab( $screen, $args );
	}
}

if ( ! function_exists( 'gglnltcs_process_ajax' ) ) {
	/**
	 * Ajax Processing Function
	 */
	function gglnltcs_process_ajax() {
		global $gglnltcs_options, $gglnltcs_metrics_data;
		/* Get options from the database and set them to the global array */
		gglnltcs_settings();

		if ( empty( $gglnltcs_options['property_ids'] ) ) {
			echo '<table class="gglnltcs gglnltcs-results">
				<tr>
					<td><div class="gglnltcs-bad-results gglnltcs-unsuccess-message">' . esc_html__( 'To enable report for statistics please enter Property ID.', 'bws-google-analytics' ) . '</div></td>
				</tr>
			</table>';
			die();
		}

		check_ajax_referer( 'gglnltcs_ajax_nonce_value', 'gglnltcs_nonce' );

		if ( isset( $_POST['settings'] ) ) {
			parse_str( $_POST['settings'], $settings );
			foreach ( $settings as $key => $value ) {
				$settings[ $key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		} else {
			echo '<table class="gglnltcs gglnltcs-results">
				<tr>
					<td><div class="gglnltcs-bad-results gglnltcs-unsuccess-message">' . esc_html__( 'There are not enough settings to create a request.', 'bws-google-analytics' ) . '</div></td>
				</tr>
			</table>';
			die();
		}

		if ( isset( $_POST['tab'] ) && 'line_chart' == sanitize_text_field( wp_unslash( $_POST['tab'] ) ) ) {
			$response = gglnltcs_load_data_from_api( $settings, array( 'year', 'month', 'day' ) );
			if ( ! empty( $response ) ) {
				$rows_results      = $response->getRows();
				$chart_data        = array();
				$chart_date        = array();
				$chart_users       = array();
				$chart_new_users   = array();
				$chart_sessions    = array();
				$chart_bounce_rate = array();
				$chart_avg_session = array();
				$chart_pageviews   = array();
				$chart_per_session = array();

				foreach ( $rows_results as $row ) {
					$dimension_values = array( $row->getDimensionValues()[0]->getValue() );
					if ( isset( $row->getDimensionValues()[1] ) ) {
						$dimension_values[] = $row->getDimensionValues()[1]->getValue();
					}
					if ( isset( $row->getDimensionValues()[2] ) ) {
						$dimension_values[] = $row->getDimensionValues()[2]->getValue();
					}
					$chart_date[]        = $dimension_values;
					$chart_users[]       = array( $row->getMetricValues()[0]->getValue() );
					$chart_new_users[]   = array( $row->getMetricValues()[1]->getValue() );
					$chart_sessions[]    = array( $row->getMetricValues()[2]->getValue() );
					$chart_bounce_rate[] = array( round( $row->getMetricValues()[3]->getValue(), 2 ) );
					$chart_avg_session[] = array( round( $row->getMetricValues()[4]->getValue() / 60, 1 ) );
					$chart_pageviews[]   = array( $row->getMetricValues()[5]->getValue() );
					$chart_per_session[] = array( round( $row->getMetricValues()[6]->getValue(), 2 ) );
				}
				array_push(
					$chart_data,
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
			}
		} elseif ( isset( $_POST['tab'] ) && 'table_chart' == sanitize_text_field( wp_unslash( $_POST['tab'] ) ) ) {
			?>
			<noscript>
				<style>
					#gglnltcs-results-wrapper {
						max-width: 100%;
						min-height: 260px;
						overflow-x: auto;
					}
				</style>
			</noscript>
			<div id="gglnltcs-results-wrapper">
				<table id="gglnltcs-group-by-Y-M-D" class="form-table">
					<tr class="hide-if-no-js">
						<th><?php esc_html_e( 'Group by', 'bws-google-analytics' ); ?></th>
						<td>
							<input type="button" class="button-secondary" value="<?php esc_html_e( 'Year', 'bws-google-analytics' ); ?>">
							<input type="button" class="button-secondary" value="<?php esc_html_e( 'Month', 'bws-google-analytics' ); ?>">
							<input type="button" class="button-secondary gglnltcs-selected" value="<?php esc_html_e( 'Day', 'bws-google-analytics' ); ?>">
						</td>
					</tr>
				</table>
				<?php
				$responses = gglnltcs_load_data_from_api( $settings, array( array( 'year', 'month', 'day' ), array( 'year', 'month' ), array( 'year' ) ), true );
				if ( ! empty( $responses ) ) {
					$reports = $responses->getReports();
					$table = '';
					foreach ( $reports as $response ) {
						$rows_results = $response->getRows();
						if ( count( $rows_results ) ) {
							$table .= gglnltcs_metrics_table_display( $rows_results, $settings );
						} else {
							$table = '<table class="gglnltcs gglnltcs-results">
								<tr>
									<th><h3>' . esc_html_e( 'Results', 'bws-google-analytics' ) . '</h3></th>
									<td><div class="gglnltcs-bad-results">' . __( 'No results found', 'bws-google-analytics' ) . '.<div></td>
								</tr>
							</table>';
						}
					}
					echo wp_kses_post( $table );
				}
				?>
			</div>
			<?php
		}

		unset( $settings['gglnltcs_nonce_name'], $settings['_wp_http_referer'] );

		$gglnltcs_options['settings'] = $settings;
		update_option( 'gglnltcs_options', $gglnltcs_options );
		die();
	}
}

if ( ! function_exists( 'gglnltcs_load_data_from_api' ) ) {
	/**
	 * Function for load data from API
	 *
	 * @param array $settings   Settings array from Settings page.
	 * @param array $dimensions Dimensions array for API.
	 * @param bool  $batch      Flag for batch.
	 */
	function gglnltcs_load_data_from_api( $settings, $dimensions, $batch = false ) {
		require __DIR__ . '/google-analytics-v4-api/vendor/autoload.php';

		putenv( 'GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/google-analytics-v4-api/credentials.json' );

		$property_id = $settings['gglnltcs_property'];

		$client = new BetaAnalyticsDataClient();

		if ( ! empty( $dimensions ) ) {
			$dimensions_array = array();
			$order_by_array   = array();
			foreach ( $dimensions as $key => $dimension_array ) {
				if ( true === $batch ) {
					$dimensions_array[ $key ] = array();
					$order_by_array[ $key ] = array();
					foreach ( $dimension_array as $dimension ) {
						$dimensions_array[ $key ][] = new Dimension(
							[
								'name' => $dimension,
							]
						);
						$order_by_array[ $key ][] = new OrderBy(
							[
								'dimension' => new OrderBy\DimensionOrderBy(
									[
										'dimension_name' => $dimension,
										'order_type' => OrderBy\DimensionOrderBy\OrderType::ALPHANUMERIC,
									]
								),
								'desc' => false,
							]
						);
					}
				} else {
					$dimensions_array[] = new Dimension(
						[
							'name' => $dimension_array,
						]
					);
					$order_by_array[] = new OrderBy(
						[
							'dimension' => new OrderBy\DimensionOrderBy(
								[
									'dimension_name' => $dimension_array,
									'order_type' => OrderBy\DimensionOrderBy\OrderType::ALPHANUMERIC,
								]
							),
							'desc' => false,
						]
					);
				}
			}
		}
		$metrics = [
			new Metric(
				[
					'name' => 'totalUsers',
				]
			),
			new Metric(
				[
					'name' => 'newUsers',
				]
			),
			new metric(
				[
					'name' => 'sessions',
				]
			),
			new metric(
				[
					'name' => 'engagementRate',
				]
			),
			new Metric(
				[
					'name' => 'averageSessionDuration',
				]
			),
			new metric(
				[
					'name' => 'screenpageviews',
				]
			),
			new metric(
				[
					'name' => 'screenPageViewsPerSession',
				]
			),
		];

		try {
			if ( true === $batch ) {
				$report_requests = array();
				foreach ( $dimensions_array as $key => $dimension ) {
					$report_requests[] = new RunReportRequest(
						[
							'date_ranges' => [
								new DateRange(
									[
										'start_date' => empty( $settings['gglnltcs_start_date'] ) ? '28daysAgo' : $settings['gglnltcs_start_date'],
										'end_date'   => empty( $settings['gglnltcs_end_date'] ) ? 'today' : $settings['gglnltcs_end_date'],
									]
								),
							],
							'dimensions' => $dimension,
							'metrics'    => $metrics,
							'order_bys'   => $order_by_array[ $key ],
						]
					);
				}
				$response = $client->batchRunReports(
					[
						'property' => 'properties/' . $property_id,
						'requests' => $report_requests,
					]
				);
			} else {
				$response = $client->runReport(
					[
						'property'   => 'properties/' . $property_id,
						'dateRanges' => [
							new DateRange(
								[
									'start_date' => empty( $settings['gglnltcs_start_date'] ) ? '28daysAgo' : $settings['gglnltcs_start_date'],
									'end_date'   => empty( $settings['gglnltcs_end_date'] ) ? 'today' : $settings['gglnltcs_end_date'],
								]
							),
						],
						'dimensions' => $dimensions_array,
						'metrics'    => $metrics,
						'orderBys'   => $order_by_array,
					]
				);
			}
			return $response;
		} catch ( Google\ApiCore\ApiException $e ) {
			?>
			<table class="gglnltcs gglnltcs-results">
				<tr>
					<td><div class="gglnltcs-bad-results gglnltcs-unsuccess-message"><?php echo esc_html__( 'There was an Analytics API service error', 'bws-google-analytics' ) . ': ' . esc_html( $e->getBasicMessage() ); ?></div></td>
				</tr>
			</table>
		<?php } catch ( Exception $e ) { ?>
			<table class="gglnltcs gglnltcs-results">
				<tr>
					<td><div class="gglnltcs-bad-results gglnltcs-unsuccess-message"><?php echo esc_html__( 'There was a general API error', 'bws-google-analytics' ) . ' ' . esc_html( $e->getCode() ) . ':' . esc_html( $e->getMessage() ); ?></div></td>
				</tr>
			</table>
			<?php
		}
	}
}

if ( ! function_exists( 'gglnltcs_metrics_table_display' ) ) {
	/**
	 * Prints Results Tables On The Table Chart Tab
	 *
	 * @param object $rows_results Results form API.
	 * @param array  $settings     Settings array from Settings page.
	 * @return string HTML.
	 */
	function gglnltcs_metrics_table_display( $rows_results, $settings ) {
		$table = '<div class="gglnltcs-results-table-wrap">
			<table class="gglnltcs gglnltcs-results">';

		$table .= '<tr class="gglnltcs-row-header"><td>' . __( 'Date', 'bws-google-analytics' ) . '</td>';
		if ( isset( $settings['gglnltcs-ga-users'] ) ) {
			$table .= '<td>' . __( 'Unique Visitors', 'bws-google-analytics' ) . '</td>';
		}
		if ( isset( $settings['gglnltcs-ga-new-users'] ) ) {
			$table .= '<td>' . __( 'New Visits', 'bws-google-analytics' ) . '</td>';
		}
		if ( isset( $settings['gglnltcs-ga-sessions'] ) ) {
			$table .= '<td>' . __( 'Visits', 'bws-google-analytics' ) . '</td>';
		}
		if ( isset( $settings['gglnltcs-ga-bounce-rate'] ) ) {
			$table .= '<td>' . __( 'Bounce Rate', 'bws-google-analytics' ) . '</td>';
		}
		if ( isset( $settings['gglnltcs-ga-avg-session-duration'] ) ) {
			$table .= '<td>' . __( 'Average Visit Duration', 'bws-google-analytics' ) . '</td>';
		}
		if ( isset( $settings['gglnltcs-ga-pageviews'] ) ) {
			$table .= '<td>' . __( 'Pageviews', 'bws-google-analytics' ) . '</tdh>';
		}
		if ( isset( $settings['gglnltcs-ga-pageviews-per-session'] ) ) {
			$table .= '<td>' . __( 'Pages / Visit', 'bws-google-analytics' ) . '</td>';
		}
		$table .= '</tr>';

		foreach ( $rows_results as $row ) {
			$table .= '<tr class="gglnltcs-row"><td>' . $row->getDimensionValues()[0]->getValue();
			if ( isset( $row->getDimensionValues()[1] ) ) {
				$table .= ' ' . date( 'M', strtotime( $row->getDimensionValues()[0]->getValue() . '-' . $row->getDimensionValues()[1]->getValue() . '-01' ) );
			}
			if ( isset( $row->getDimensionValues()[2] ) ) {
				$table .= ' ' . $row->getDimensionValues()[2]->getValue();
			}
			$table .= '</td>';
			if ( isset( $settings['gglnltcs-ga-users'] ) ) {
				$table .= '<td>' . $row->getMetricValues()[0]->getValue() . '</td>';
			}
			if ( isset( $settings['gglnltcs-ga-new-users'] ) ) {
				$table .= '<td>' . $row->getMetricValues()[1]->getValue() . '</td>';
			}
			if ( isset( $settings['gglnltcs-ga-sessions'] ) ) {
				$table .= '<td>' . $row->getMetricValues()[2]->getValue() . '</td>';
			}
			if ( isset( $settings['gglnltcs-ga-bounce-rate'] ) ) {
				$table .= '<td>' . round( $row->getMetricValues()[3]->getValue(), 4 ) * 100 . '%' . '</td>';
			}
			if ( isset( $settings['gglnltcs-ga-avg-session-duration'] ) ) {
				$table .= '<td>' . date( 'H:i:s', $row->getMetricValues()[4]->getValue() ) . '</td>';
			}
			if ( isset( $settings['gglnltcs-ga-pageviews'] ) ) {
				$table .= '<td>' . $row->getMetricValues()[5]->getValue() . '</td>';
			}
			if ( isset( $settings['gglnltcs-ga-pageviews-per-session'] ) ) {
				$table .= '<td>' . round( $row->getMetricValues()[6]->getValue(), 2 ) . '</td>';
			}
			$table .= "</tr>\n";
		}
		$table .= '</table>
					</div>';
		return $table;
	}
}

if ( ! function_exists( 'gglnltcs_load_metrics' ) ) {
	/**
	 * Load metrics data
	 */
	function gglnltcs_load_metrics() {
		global $gglnltcs_metrics_data;
		/*** METRICS */
		$gglnltcs_metrics_data = array(
			/** VISITOR */
			/* Unique Visitors */
			'ga:users' => array(
				'id'        => 'gglnltcs-ga-users',
				'name'      => 'gglnltcs-ga-users',
				'value'     => 'ga:users',
				'title'     => __( 'Total number of visitors for the requested time period.', 'bws-google-analytics' ),
				'for'       => 'gglnltcs-ga-users',
				'label'     => __( 'Unique Visitors', 'bws-google-analytics' ),
				'category'  => __( 'Visitor', 'bws-google-analytics' ),
			),
			/* New Visits */
			'ga:newUsers' => array(
				'id'        => 'gglnltcs-ga-new-users',
				'name'      => 'gglnltcs-ga-new-users',
				'value'     => 'ga:newUsers',
				'title'     => __( 'The number of visitors whose visit to your property was marked as a first-time visit.', 'bws-google-analytics' ),
				'for'       => 'gglnltcs-ga-new-users',
				'label'     => __( 'New Visits', 'bws-google-analytics' ),
				'category'  => __( 'Visitor', 'bws-google-analytics' ),
			),
			/** SESSION */
			/* Visitors */
			'ga:sessions' => array(
				'id'        => 'gglnltcs-ga-sessions',
				'name'      => 'gglnltcs-ga-sessions',
				'value'     => 'ga:sessions',
				'title'     => __( 'Counts the total number of sessions.', 'bws-google-analytics' ),
				'for'       => 'gglnltcs-ga-sessions',
				'label'     => __( 'Visits', 'bws-google-analytics' ),
				'category'  => __( 'Session', 'bws-google-analytics' ),
			),
			/* Bounce Rate */
			'ga:bounceRate' => array(
				'id'        => 'gglnltcs-ga-bounce-rate',
				'name'      => 'gglnltcs-ga-bounce-rate',
				'value'     => 'ga:bounceRate',
				'title'     => __( 'The percentage of single-page visits (i.e., visits in which the person left your property from the first page).', 'bws-google-analytics' ),
				'for'       => 'gglnltcs-ga-bounce-rate',
				'label'     => __( 'Bounce Rate', 'bws-google-analytics' ),
				'category'  => __( 'Session', 'bws-google-analytics' ),
			),
			/* Average Visit Duration */
			'ga:avgSessionDuration' => array(
				'id'        => 'gglnltcs-ga-avg-session-duration',
				'name'      => 'gglnltcs-ga-avg-session-duration',
				'value'     => 'ga:avgSessionDuration',
				'title'     => __( 'The average duration visitor sessions.', 'bws-google-analytics' ),
				'for'       => 'gglnltcs-ga-avg-session-duration',
				'label'     => __( 'Average Visit Duration', 'bws-google-analytics' ),
				'category'  => __( 'Session', 'bws-google-analytics' ),
			),
			/** PAGE TRACKING */
			/* Pageviews */
			'ga:pageviews' => array(
				'id'        => 'gglnltcs-ga-pageviews',
				'name'      => 'gglnltcs-ga-pageviews',
				'value'     => 'ga:pageviews',
				'title'     => __( 'The total number of pageviews for your property.', 'bws-google-analytics' ),
				'for'       => 'gglnltcs-ga-pageviews',
				'label'     => __( 'Pageviews', 'bws-google-analytics' ),
				'category'  => __( 'Page Tracking', 'bws-google-analytics' ),
			),
			/* Pages/Visit */
			'ga:pageviewsPerSession' => array(
				'id'        => 'gglnltcs-ga-pageviews-per-session',
				'name'      => 'gglnltcs-ga-pageviews-per-session',
				'value'     => 'ga:pageviewsPerSession',
				'title'     => __( 'The average number of pages viewed during a visit to your property. Repeated views of a single page are counted.', 'bws-google-analytics' ),
				'for'       => 'gglnltcs-ga-pageviews-per-session',
				'label'     => __( 'Pages / Visit', 'bws-google-analytics' ),
				'category'  => __( 'Page Tracking', 'bws-google-analytics' ),
			),
		);

		return $gglnltcs_metrics_data;
	}
}

if ( ! function_exists( 'gglnltcs_plugin_action_links' ) ) {
	/**
	 * This links under plugin name
	 *
	 * @param array  $links Array with links.
	 * @param string $file  File name.
	 * @return array $links
	 */
	function gglnltcs_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=bws-google-analytics.php">' . __( 'Settings', 'bws-google-analytics' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

if ( ! function_exists( 'gglnltcs_register_plugin_links' ) ) {
	/**
	 * This links in plugin description
	 *
	 * @param array  $links Array with links.
	 * @param string $file  File name.
	 * @return array $links
	 */
	function gglnltcs_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );

		if ( $file == $base ) {
			if ( ! is_network_admin() ) {
				$links[] = '<a href="admin.php?page=bws-google-analytics.php">' . __( 'Settings', 'bws-google-analytics' ) . '</a>';
			}
			$links[] = '<a href="https://wordpress.org/plugins/bws-google-analytics/faq/" target="_blank">' . __( 'FAQ', 'bws-google-analytics' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'bws-google-analytics' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'gglnltcs_delete_options' ) ) {
	/**
	 * Delete All Database Options When User Uninstalls Plugin
	 */
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
add_action( 'plugins_loaded', 'gglnltcs_plugins_loaded' );
add_action( 'admin_menu', 'add_gglnltcs_admin_menu' ); /* Add menu page, add submenu page.*/

add_action( 'init', 'gglnltcs_init' ); /* Load database options.*/
add_action( 'admin_init', 'gglnltcs_admin_init' ); /* bws_plugin_info, gglnltcs_plugin_info, check WP version, plugin localization */

add_action( 'admin_enqueue_scripts', 'gglnltcs_scripts' );
add_action( 'wp_enqueue_scripts', 'gglnltcs_past_tracking_code' ); /* Insert tracking code when front page loads.*/
add_filter( 'script_loader_tag', 'gglnltcs_add_data_to_script', 10, 2 );

add_action( 'admin_notices', 'gglnltcs_show_notices' );

add_filter( 'plugin_action_links', 'gglnltcs_plugin_action_links', 10, 2 ); /* Add "Settings" link to the plugin action page.*/
add_filter( 'plugin_row_meta', 'gglnltcs_register_plugin_links', 10, 2 ); /* Additional links on the plugin page - "Settings", "FAQ", "Support".*/

add_action( 'wp_ajax_gglnltcs_action', 'gglnltcs_process_ajax' ); /* Ajax processing function.*/
