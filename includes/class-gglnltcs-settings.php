<?php
/**
 *  Display the content on the plugin settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Gglnltcs_Settings_Tabs' ) ) {
	/**
	 * Class Gglnltcs_Settings_Tabs for display Settings tab
	 */
	class Gglnltcs_Settings_Tabs extends Bws_Settings_Tabs {
		/**
		 *  Constructor
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__constructor() for more information in default arguments.
		 *
		 * @param string $plugins_basename Plugin basename.
		 */
		public function __construct( $plugins_basename ) {
			global $gglnltcs_options, $gglnltcs_plugin_info;

			$tabs = array(
				'settings'          => array( 'label' => __( 'Settings', 'bws-google-analytics' ) ),
				'statistics'        => array( 'label' => __( 'Statistics', 'bws-google-analytics' ) ),
				'visual_statistics' => array( 'label' => __( 'Visual Statistics', 'bws-google-analytics' ) ),
				'misc'              => array( 'label' => __( 'Misc', 'bws-google-analytics' ) ),
				'custom_code'       => array( 'label' => __( 'Custom Code', 'bws-google-analytics' ) ),
				'license'           => array( 'label' => __( 'Licence Key', 'bws-google-analytics' ) ),
			);

			parent::__construct(
				array(
					'plugin_basename'    => $plugins_basename,
					'plugins_info'       => $gglnltcs_plugin_info,
					'prefix'             => 'gglnltcs',
					'default_options'    => gglnltcs_default_options(),
					'options'            => $gglnltcs_options,
					'is_network_options' => is_network_admin(),
					'tabs'               => $tabs,
					'wp_slug'            => 'bws-google-analytics',
					'link_key'           => '0ceb29947727cb6b38a01b29102661a3',
					'link_pn'            => '125',
					'doc_link'           => 'https://bestwebsoft.com/documentation/analytics/analytics-user-guide/',
				)
			);

			add_action( get_parent_class( $this ) . '_display_custom_messages', array( $this, 'display_custom_messages' ) );
		}

		/**
		 * Display custom error\message\notice
		 *
		 * @access public
		 * @param array $save_results Array with error\message\notice.
		 */
		public function display_custom_messages( $save_results ) {
			if ( empty( $this->options['tracking_id'] ) ) { ?>
				<div class="error inline">
					<p><?php esc_html_e( 'To enable tracking and collect statistic from your site please enter Tracking ID.', 'bws-google-analytics' ); ?></p>
				</div>
			<?php } ?>
			<noscript>
				<div class="error below-h2"><p><strong><?php esc_html_e( 'Please enable JavaScript in your browser.', 'bws-google-analytics' ); ?></strong></p></div>
			</noscript>
			<?php
		}

		/**
		 *  Save plugin options to the database
		 *
		 * @access public
		 * @return array The action results
		 */
		public function save_options() {
			global $wp_filesystem;
			$message = '';
			$notice  = '';
			$error   = '';

			if ( ! isset( $_POST['gglnltcs_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gglnltcs_nonce_field'] ) ), 'gglnltcs_action' ) ) {
				print esc_html__( 'Sorry, your nonce did not verify.', 'bws-google-analytics' );
				exit;
			} else {
				$this->options['tracking_id']     = isset( $_POST['gglnltcs_tracking_id'] ) ? sanitize_text_field( wp_unslash( $_POST['gglnltcs_tracking_id'] ) ) : '';
				$this->options['property_ids']    = isset( $_POST['gglnltcs_property_ids'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST['gglnltcs_property_ids'] ) ) : array();
				$this->options['property_ids']    = array_unique( array_diff( $this->options['property_ids'], array( '' ) ) );
				$this->options['api_credentials'] = isset( $_POST['gglnltcs_api_credentials'] ) ? wp_kses_post( wp_unslash( $_POST['gglnltcs_api_credentials'] ) ) : '';
				
				$this->options['settings']['gglnltcs-ga-users']                 = isset( $_POST['gglnltcs-ga-users'] ) ? 1 : 0;
				$this->options['settings']['gglnltcs-ga-new-users']             = isset( $_POST['gglnltcs-ga-new-users'] ) ? 1 : 0;
				$this->options['settings']['gglnltcs-ga-sessions']              = isset( $_POST['gglnltcs-ga-sessions'] ) ? 1 : 0;
				$this->options['settings']['gglnltcs-ga-bounce-rate']           = isset( $_POST['gglnltcs-ga-bounce-rate'] ) ? 1 : 0;
				$this->options['settings']['gglnltcs-ga-avg-session-duration']  = isset( $_POST['gglnltcs-ga-avg-session-duration'] ) ? 1 : 0;
				$this->options['settings']['gglnltcs-ga-pageviews']             = isset( $_POST['gglnltcs-ga-pageviews'] ) ? 1 : 0;
				$this->options['settings']['gglnltcs-ga-pageviews-per-session'] = isset( $_POST['gglnltcs-ga-pageviews-per-session'] ) ? 1 : 0;
				$this->options['settings']['gglnltcs_start_date']               = isset( $_POST['gglnltcs_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['gglnltcs_start_date'] ) ) : date( 'Y-m-d', strtotime( '-28 days' ) );
				$this->options['settings']['gglnltcs_end_date']                 = isset( $_POST['gglnltcs_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['gglnltcs_end_date'] ) ) : date( 'Y-m-d', time() );
				$this->options['settings']['gglnltcs_view_mode']                = isset( $_POST['gglnltcs_view_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['gglnltcs_view_mode'] ) ) : 'chart';

				WP_Filesystem();
				$success = $wp_filesystem->put_contents( dirname( dirname( __FILE__ ) ) . '/google-analytics-v4-api/credentials.json', $this->options['api_credentials'], FS_CHMOD_FILE );
				if ( ! $success ) {
					$error = sprintf( esc_html__( 'Writing to file "%s" failed. It is necessary to manually fill the file or grant write permissions to create the file', 'bws-google-analytics' ), dirname( dirname( __FILE__ ) ) . '/google-analytics-v4-api/credentials.json' );
				}

				update_option( 'gglnltcs_options', $this->options );

				$message = __( 'Settings saved', 'bws-google-analytics' );
			}

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 * Display tab settings
		 */
		public function tab_settings() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Analytics Settings', 'bws-google-analytics' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<div class="bws_tab_sub_label"><?php esc_html_e( 'Authentication', 'bws-google-analytics' ); ?></div>
			<table class="form-table gglnltcs">
				<tr>
					<th><?php esc_html_e( 'Tracking ID', 'bws-google-analytics' ); ?></th>
					<td>
						<input type="text" name="gglnltcs_tracking_id" value="<?php echo esc_attr( $this->options['tracking_id'] ); ?>" /><br />
						<span class="bws_info"><?php esc_html_e( 'Want to know how to add your tracking ID?', 'bws-google-analytics' ); ?> <a href="https://support.bestwebsoft.com/hc/en-us/articles/202352589"><?php esc_html_e( 'Learn More', 'bws-google-analytics' ); ?></a></span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Property ID', 'bws-google-analytics' ); ?></th>
					<td>
						<?php
						if ( ! empty( $this->options['property_ids'] ) ) {
							foreach ( $this->options['property_ids'] as $property_id ) {
								?>
								<label><input type="text" name="gglnltcs_property_ids[]" value="<?php echo esc_attr( $property_id ); ?>" /> <span class="gglnltcs-delete"><img src="<?php echo esc_url( plugins_url( 'images/del_icon.png', dirname( __FILE__ ) ) ); ?>" alt="Delete" /></span><br /><br /></label>
								<?php
							}
						}
						?>
						<input type="text" name="gglnltcs_property_ids[]" value="" /><br />
						<span class="bws_info"><?php esc_html_e( 'Want to know how to add your property ID?', 'bws-google-analytics' ); ?> <a href="https://support.bestwebsoft.com/hc/en-us/articles/202352589"><?php esc_html_e( 'Learn More', 'bws-google-analytics' ); ?></a></span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'API Credentials', 'bws-google-analytics' ); ?></th>
					<td>
						<textarea name="gglnltcs_api_credentials" rows="10" <?php echo esc_html( $this->change_permission_attr ); ?>><?php echo esc_html( $this->options['api_credentials'] ); ?></textarea>
					</td>
				</tr>
			</table>
			<?php wp_nonce_field( 'gglnltcs_action', 'gglnltcs_nonce_field' ); ?>
			<?php
		}

		/**
		 * Display tab statistics
		 */
		public function tab_statistics() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Analytics Statistics', 'bws-google-analytics' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<?php
			$form_loaded = false;
			$redirect = '';

			try {
				$settings = isset( $this->options['settings'] ) ? $this->options['settings'] : '';
				/* Load metrics data */
				$metrics_data = gglnltcs_load_metrics();
				$output  = '';

				$start_date = empty( $settings['gglnltcs_start_date'] ) ? date( 'Y-m-d', strtotime( '-28 days' ) ) : $settings['gglnltcs_start_date'];
				$end_date   = empty( $settings['gglnltcs_end_date'] ) ? date( 'Y-m-d', time() ) : $settings['gglnltcs_end_date'];
				if ( ! empty( $this->options['property_ids'] ) ) {
					?>
					<table class="form-table gglnltcs">
						<tr>
							<th><?php esc_html_e( 'Property', 'bws-google-analytics' ); ?></th>
							<td>
								<select id="gglnltcs-accounts" class="gglnltcs-select bws_no_bind_notice" name="gglnltcs_property">
									<?php
									foreach ( $this->options['property_ids'] as $property_id ) {
										echo '<option value="' . esc_attr( $property_id ) . '">' . esc_html( $property_id ) . '</option>';
									}
									?>
								</select>
							</td>
						</tr>
					</table>
					<?php
				}
				?>
				<table  class="form-table gglnltcs">
					<tr id="gglnltcs-metrics">
						<th><?php esc_html_e( 'Metrics', 'bws-google-analytics' ); ?></th>
						<td>
							<?php
							$curr_category = '';
							foreach ( $metrics_data as $item ) {
								if ( $curr_category != $item['category'] ) {
									echo '<hr><strong>' . esc_html( $item['category'] ) . '</strong><hr>';
									$curr_category = $item['category'];
								} /* Build checkboxes for metrics options. */
								echo '<p><input id="' . esc_attr( $item['id'] ) . '" class="gglnltcs_metrics_checkbox bws_no_bind_notice" name="' . esc_attr( $item['name'] ) . '" type="checkbox" value="' . esc_attr( $item['value'] ) . '"';
								if ( isset( $settings[ $item['name'] ] ) || ( ! $settings && 'gglnltcs-ga-users' == $item['name'] ) ) {
									echo ' checked = "checked">';
								} else {
									echo '>';
								}
								echo '<label title="' . esc_html( $item['title'] ) . '" for="' . esc_attr( $item['for'] ) . '"> ' . esc_html( $item['label'] ) . '</label></p>';
							}
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Time Range', 'bws-google-analytics' ); ?></th>
						<td>
							<label for="gglnltcs-start-date" class="gglnltcs-date">
								<?php esc_html_e( 'From', 'bws-google-analytics' ); ?>&nbsp;
								<input id="gglnltcs-start-date" class="gglnltcs_to_disable bws_no_bind_notice" size="8" name="gglnltcs_start_date" type="text" value="<?php echo esc_attr( $start_date ); ?>" />
							</label>&nbsp;
							<label for="gglnltcs-end-date" class="gglnltcs-date">
								<?php esc_html_e( 'to', 'bws-google-analytics' ); ?>&nbsp;
								<input id="gglnltcs-end-date" class="gglnltcs_to_disable bws_no_bind_notice" size="8" name="gglnltcs_end_date" type="text" value="<?php echo esc_attr( $end_date ); ?>" />
							</label>
							<?php
							echo wp_kses_post(
								bws_add_help_box(
									sprintf( __( 'Date values must match the pattern %s.', 'bws-google-analytics' ), 'YYYY-MM-DD' ) .
									'<br/>' .
									__( 'The gap between dates must not be more than 999 days.', 'bws-google-analytics' )
								)
							);
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'View Mode', 'bws-google-analytics' ); ?></th>
						<td>
							<fieldset>
								<label for="gglnltcs-chart-mode">
									<input type="radio" id="gglnltcs-chart-mode" class="gglnltcs_to_disable bws_no_bind_notice" name="gglnltcs_view_mode" value="chart"
									<?php
									if ( ! isset( $settings['gglnltcs_view_mode'] ) || 'chart' == $settings['gglnltcs_view_mode'] ) {
										echo ' checked="checked"';}
									?>
									/>
									<?php esc_html_e( 'Line chart', 'bws-google-analytics' ); ?>
								</label>
								<br/>
								<label for="gglnltcs-table-mode">
									<input type="radio" id="gglnltcs-table-mode" class="gglnltcs_to_disable bws_no_bind_notice" name="gglnltcs_view_mode" value="table"
									<?php
									if ( isset( $settings['gglnltcs_view_mode'] ) && 'table' == $settings['gglnltcs_view_mode'] ) {
										echo ' checked="checked"';}
									?>
									/>
									<?php esc_html_e( 'Table', 'bws-google-analytics' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th></th>
						<td>
							<input id="gglnltcs-get-statistics-button" type="submit" class="button-secondary" value="<?php esc_html_e( 'Get Statistic', 'bws-google-analytics' ); ?>">
							<?php if ( ! $this->hide_pro_tabs ) { ?>
								<div class="bws_pro_version_bloc">
									<div class="bws_pro_version_table_bloc">
										<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'bws-google-analytics' ); ?>"></button>
										<div class="bws_table_bg"></div>
										<input type="submit" class="button-secondary" value="<?php esc_html_e( 'Save to scv', 'bws-google-analytics' ); ?>">
									</div>
									<?php $this->bws_pro_block_links(); ?>
								</div>
							<?php } ?>
						</td>
					</tr>
				</table>
				<?php
				if ( isset( $settings['gglnltcs_view_mode'] ) && 'table' == $settings['gglnltcs_view_mode'] ) {
					?>
					<div id="gglnltcs-results-wrapper">
					</div>
					<?php
				} else {
					?>
					<div id="gglnltcs-results-wrapper">
						<div id="gglnltcs-chart"></div>
					</div>
					<?php
				}
			} catch ( Google_Service_Exception $e ) {
				echo esc_html__( 'There was an API error', 'bws-google-analytics' ) . ': ' . esc_html( $e->getCode() ) . ' : ' . esc_html( $e->getMessage() );
			} catch ( Exception $e ) {
				$error = '<div class="error"><strong><p> ' .
				 __( 'Warning: ', 'bws-google-analytics' ) .
				 '</strong>' . __( 'Authentication Token expired. Authenticate with your Google Account once again.', 'bws-google-analytics' ) .
				 '</p></div>';
				echo wp_kses_post( $error );
			}
		}

		public function tab_visual_statistics() {
			global $accounts_properties;
			$current_property = '';
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Analytics Visual Statistics', 'bws-google-analytics' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'bws-google-analytics' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table gglnltcs bws_pro_version">
							<tr>
								<th><?php esc_html_e( 'Property', 'bws-google-analytics' ); ?></th>
								<td>
									<select class="gglnltcs-select bws_no_bind_notice" disabled="disabled">
										<option></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Get Statistic for URL', 'bws-google-analytics' ); ?></th>
								<td>
									<input class="gglnltcs-select bws_no_bind_notice" type="text" value="" disabled="disabled" />
								</td>
							</tr>
							<tr>
								<th></th>
								<td>
									<input type="submit" class="button-secondary" value="<?php esc_html_e( 'Get Visual Statistic', 'bws-google-analytics' ); ?>" disabled="disabled" />
								</td>
							</tr>
						</table>
						<img src="<?php echo plugins_url( 'images/visual_stats.png', dirname( __FILE__ ) ); ?> " />
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php }
		}
	}
}
