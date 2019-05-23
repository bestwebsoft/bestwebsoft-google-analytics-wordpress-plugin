<?php
/**
 *  Display the content on the plugin settings page
 */

require_once( dirname( dirname( __FILE__ ) ) . '/bws_menu/class-bws-settings.php' );

if ( ! class_exists( 'Gglnltcs_Settings_Tabs' ) ) {
	class Gglnltcs_Settings_Tabs extends Bws_Settings_Tabs {
	    private $analytics, $curl_enabled, $client, $hide_pro_block;

		/**
		 *  Constructor
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__constructor() for more information in default arguments.
		 *
		 * @param string $plugin_basename
		 */

		public function __construct( $plugins_basename ) {
			global $gglnltcs_options, $gglnltcs_plugin_info;

			$tabs = array(
				'settings'    => array( 'label' => __( 'Settings', 'bws-google-analytics' ) ),
				'statistics'  => array( 'label' => __( 'Statistics', 'bws-google-analytics' ) ),
				'misc'        => array( 'label' => __( 'Misc', 'bws-google-analytics' ) ),
				'custom_code' => array( 'label' => __( 'Custom Code', 'bws-google-analytics' ) ),
				'license'     => array( 'label' => __( 'Licence Key', 'bws-google-analytics' ) )
			);

			parent::__construct( array(
				'plugin_basename' 	 => $plugins_basename,
				'plugins_info'		 => $gglnltcs_plugin_info,
				'prefix' 			 => 'gglnltcs',
				'default_options' 	 => gglnltcs_default_options(),
				'options' 			 => $gglnltcs_options,
				'is_network_options' => is_network_admin(),
				'tabs' 				 => $tabs,
				'wp_slug'			 => 'bws-google-analytics',
                'pro_page'          => 'admin.php?page=bws-google-analytics.php',
                'bws_license_plugin' => 'bws-google-analytics/bws-google-analytics.php',
                'link_key'          => '0ceb29947727cb6b38a01b29102661a3',
                'link_pn' => '125'
			) );

			$this->analytics = gglnltcs_get_analytics();
			$this->curl_enabled = function_exists( 'curl_init' );
			$this->client = gglnltcs_get_client();
			$this->hide_pro_block = $this->options['hide_premium_options'];

			add_action( get_parent_class( $this ) . '_display_custom_messages', array( $this, 'display_custom_messages' ) );
		}

		/**
		 *  Save plugin options to the database
		 * @access public
		 * @param void
		 * @return array   The action results
		 */
		public function save_options() {
            // saving code user gave us for authentication
            if ( isset( $_POST['code'] ) ) {
                $this->options['code'] = stripslashes( $_POST['code'] );
            }
            // save tracking id if its not empty
			if ( isset( $_POST['gglnltcs_tracking_id'] ) && isset( $_POST['gglnltcs_add_tracking_code'] ) ) {
				$this->options['tracking_id']       = trim( stripslashes( esc_html( $_POST['gglnltcs_tracking_id'] ) ) );
				$this->options['add_tracking_code'] = $_POST['gglnltcs_add_tracking_code'];
			}
			// save tracking id if its empty
			if ( isset( $_POST['gglnltcs_tracking_id'] ) && ! isset( $_POST['gglnltcs_add_tracking_code'] ) ) {
			    $this->options['tracking_id'] = trim( stripslashes( esc_html( $_POST['gglnltcs_tracking_id'] ) ) );
			    $this->options['add_tracking_code'] = 0;
            }
            // save client id
            if ( isset( $_POST['gglnltcs_client_id'] ) ) {
                $this->options['client_id'] = trim( stripslashes( esc_html( $_POST['gglnltcs_client_id'] ) ) );
            }
            // save client secret
            if ( isset( $_POST['gglnltcs_client_secret'] ) ) {
                $this->options['client_secret'] = trim( stripslashes( esc_html( $_POST['gglnltcs_client_secret'] ) ) );
            }
            // save API key
            if ( isset( $_POST['gglnltcs_api_key'] ) ) {
                $this->options['api_key'] = trim( stripslashes( esc_html( $_POST['gglnltcs_api_key'] ) ) );
            }
			if ( isset( $_POST['gglnltcs_log_out'] ) ) {
			    unset( $this->options['token'] );
			    unset( $this->options['settings'] );
			    unset( $this->options['code'] );
            }
			update_option( 'gglnltcs_options', $this->options );
			$message = __( "Settings saved", 'bws-google-analytics' );

			return compact( 'message', 'notice', 'error' );
		}

		public function tab_settings() { ?>
            <h3 class="bws_tab_label"><?php _e( 'Google Analytics Settings', 'bws-google-analytics' ); ?></h3>
			<?php $this->help_phrase(); ?>
            <hr>
            <div class="bws_tab_sub_label"><?php _e( 'Authentication', 'bws-google-analytics' ); ?></div>
            <?php
			$tracking_id = ! empty( $this->options['tracking_id'] ) ? $this->options['tracking_id'] : '';
			if ( empty( $this->options['tracking_id'] ) ) { ?>
                <div class="error">
                    <p>
					    <?php _e( 'To enable tracking and collect statistic from your site enter Tracking ID on the settings page' , 'bws-google-analytics' ); ?>
                    </p>
                </div>
			<?php } ?>
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
                                <label><input id='gglnltcs-add-tracking-code-input' type="checkbox" name="gglnltcs_add_tracking_code" value="1" <?php if ( isset( $this->options['add_tracking_code'] ) && 1 == $this->options['add_tracking_code'] ) echo 'checked="checked"'; ?> /><?php _e( 'Add tracking code to blog', 'bws-google-analytics' ) ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php

                                _e( 'Client ID', 'bws-google-analytics' );

                                $content =  __( 'To get authorized with your google account please', 'bws-google-analytics' ) . ':<br/>';
                                $content .= '<ol>';
                                $content .= sprintf( __( '%s Open the %s Google API Console Credentials %s page. %s', 'bws-google-analytics' ), '<li>', '<a href="https://console.developers.google.com/apis/credentials" target="_blank">', '</a>', '</li>' );
                                $content .= sprintf( __( '%s Click %s Select a project%s, then %s NEW PROJECT%s, and enter a name for the project, and optionally, edit the provided project ID. Click %s Create. %s', 'bws-google-analytics' ), '<li>', '<strong>', '</strong>', '<strong>', '</strong>', '<strong>', '</strong>', '</li>' );
                                $content .= sprintf( __( '%s On the Credentials page, select %s Create credentials%s, then %s OAuth client ID. %s', 'bws-google-analytics' ), '<li>', '<strong>', '</strong>', '<strong>', '</strong>', '</li>' );
                                $content .= sprintf( __( '%s You may be prompted to set a product name on the Consent screen; if so, click %s Configure consent screen%s, supply the requested information, and click %s Save %s to return to the Credentials screen. %s', 'bws-google-analytics' ), '<li>', '<strong>', '</strong>', '<strong>', '</strong>', '</li>' );
                                $content .= sprintf( __( '%s Select %s Other %s for the %s Application type %s and enter any additional information required. %s', 'bws-google-analytics' ), '<li>', '<strong>', '</strong>', '<strong>', '</strong>', '</li>' );
                                $content .= sprintf( __( '%s Click %s Create. %s %s', 'bws-google-analytics' ), '<li>', '<strong>', '</strong>', '</li>' );
                                $content .= sprintf( __( '%s On the page that appears, copy the %s client ID %s and %s client secret %s to your clipboard, as you will need them when you configure your client library. %s', 'bws-google-analytics' ), '<li>', '<strong>', '</strong>', '<strong>', '</strong>', '</li>' );
                                $content .= '</ol>';
                                $content .= sprintf( __( 'For more info see %s OAuth2 Authentication %s', 'bws-google-analytics' ), '<a href="https://developers.google.com/adwords/api/docs/guides/authentication" target="_blank">', '</a>' );

                                echo bws_add_help_box( $content );
                                ?>
                            </th>
                            <td>
                                <input<?php echo $this->change_permission_attr; ?> type="text" maxlength="100" name="gglnltcs_client_id" value="<?php echo array_key_exists( 'client_id', $this->options ) ? $this->options['client_id'] : ''; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Client Secret', 'bws-google-analytics' ); ?>
                            </th>
                            <td>
                                <input<?php echo $this->change_permission_attr; ?> type="text" maxlength="100" name="gglnltcs_client_secret" value="<?php echo array_key_exists( 'client_secret', $this->options ) ? $this->options['client_secret'] : ''; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'API key', 'bws-google-analytics' ); ?>
                            </th>
                            <td>
                                <input<?php echo $this->change_permission_attr; ?> type="text" maxlength="100" name="gglnltcs_api_key" value="<?php echo array_key_exists( 'api_key', $this->options ) ? $this->options['api_key'] : ''; ?>" />
                                <span class="bws_info"><?php _e( 'Optional', 'bws-google-analytics' ); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

			<?php
		}

		public function tab_statistics() { ?>
            <h3 class="bws_tab_label"><?php _e( 'Google Analytics Statistics', 'bws-google-analytics' ); ?></h3>
			<?php $this->help_phrase(); ?>
            <hr>
			<?php
            $form_loaded = false;
			$redirect = '';
			if ( empty( $this->options['token'] ) ) {
				if ( ! empty( $this->options['code'] ) ) {
					// If user submit form
					if ( $this->curl_enabled ) {
						if ( isset( $this->options['code'] ) ) {
							if ( empty( $this->options['code'] ) ) {
								$redirect = false;
							} else {
								try {
									$this->client->authenticate( $this->options['code'] );
									$redirect = true;
								} catch ( Google_auth_exception $e ) {
								    $error = '<div class="error"><strong><p> ' .
                                             __( 'Warning: ', 'bws-google-analytics' ) .
                                             '</strong>' .  __( 'Authentication Token expired. Authenticate with your Google Account once again.', 'bws-google-analytics' ) .
                                    '</p></div>';
								    echo $error;
									$redirect = false;
								}
								if ( $redirect ) {
									$this->options['token'] = $this->client->getAccessToken();
									update_option( 'gglnltcs_options', $this->options );
								}
								if ( ! empty( $error ) ) { ?>
                                    <table class="form-table gglnltcs" id="gglnltcs-log-out-field">
                                        <tr>
                                            <th><?php _e( 'Deauthorize', 'bws-google-analytics' ); ?></th>
                                            <td>
                                                <input type="submit" name="gglnltcs_log_out" class="button-secondary" value="<?php _e( 'Log Out', 'bws-google-analytics' ) ?>">
                                            </td>
                                        </tr>
                                    </table>
                                <?php } else { ?>
                                    <div class="gglnltcs-text-information">
                                        <input id="gglnltcs-get-statistics" type="submit" class="button-secondary" value="<?php _e( 'Get Statistic', 'bws-google-analytics' ); ?>" onClick="window.location.reload()">
                                    </div>
                                <?php }
							}
						}
					}
				} elseif ( ! isset( $_POST['code'] ) ) {
				    $form_loaded = true;
		            /*The post['code'] has not been passed yet, so let us offer the user to enter the Google Authentication Code.
					 * First we need to redirect user to the Google Authorization page.
					 * For this reason we create an URL to obtain user authorization. */
		            if ( $this->curl_enabled ) {
			            $authUrl  = $this->client->createAuthUrl();
			            $disabled = '';
		            } else {
			            $authUrl  = '#';
			            $disabled = ' disabled="disabled"';
		            }
		            if ( isset( $_POST['code'] ) && false === $redirect ) { ?>
                        <div class="error">
                            <p><?php _e( 'Invalid code. Please, try again', 'bws-google-analytics' ); ?>.</p>
                        </div>
		            <?php } ?>
                    <div class="gglnltcs-text-information">
                        <p><?php _e( "In order to use Google Analytics by BestWebSoft plugin, you must be signed in with a registered Google Account email address and password. If you don't have Google Account you can create it", 'bws-google-analytics' ); ?>
                            <a href="https://www.google.com/accounts/NewAccount"
                               target="_blank"><?php _e( 'here', 'bws-google-analytics' ); ?>.</a></p>
                        <input id="gglnltcs-google-sign-in" type="button" class="button-primary"
                               onclick="window.open( '<?php echo $authUrl; ?>', 'activate','width=640, height=480, menubar=0, status=0, location=0, toolbar=0' )"
                               value="<?php _e( 'Authenticate with your Google Account', 'bws-google-analytics' ); ?>"<?php echo $disabled; ?>>
                        <noscript>
                            <div class="button-primary gglnltcs-google-sign-in">
                                <a href="<?php echo $authUrl; ?>"
                                   target="_blanket"><?php _e( 'Or Click Here If You Have Disabled Javascript', 'bws-google-analytics' ); ?></a>
                            </div>
                        </noscript>
                        <p class="gglnltcs-authentication-instructions"><?php _e( 'When you finish authorization process you will get Google Authentication Code. You must enter this code in the field below and press "Save Changes" button. This code will be used to get an Authentication Token so you can access your website stats.', 'bws-google-analytics' ); ?></p>
                            <p><input id="gglnltcs-authentication-code-input" type="text"
                              name="code" <?php echo $disabled; ?>>
                            </p>
                    </div>
	            <?php }
            } else {
	            // functionality for showing main table on statistics tab
	            try {
		            $settings = isset( $this->options['settings'] ) ? $this->options['settings'] : '';
		            /* Load metrics data */
		            $gglnltcs_metrics_data = gglnltcs_load_metrics();
		            $output     = '';
		            $accounts   = $this->analytics->management_accounts->listManagementAccounts();
		            $items      = $accounts->getItems();
		            $default_id = preg_replace( '/(UA-)(\d+)(-\d+)/i', '${2}', $this->options['tracking_id'] );
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
					            <?php gglnltcs_print_webproperties( $this->analytics, $profile_accounts, $accounts_id, $settings ); ?>
                            </table>
				            <?php
			                if ( ! $this->hide_pro_tabs ) { ?>
                            <div class="bws_pro_version_bloc">
                                <div class="bws_pro_version_table_bloc">
                                    <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'google-captcha' ); ?>"></button>
                                    <div class="bws_table_bg"></div>
						            <?php gglnltcs_main_block(); ?>
                                </div>
					            <?php $this->bws_pro_block_links(); ?>
                            </div>
			                <?php }
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
			            <?php if ( isset( $settings['gglnltcs_view_mode'] ) && 'table' == $settings['gglnltcs_view_mode'] ) {
				            gglnltcs_get_statistic( $this->analytics, $settings, $gglnltcs_metrics_data );
			            } else { ?>
                            <div id="gglnltcs-results-wrapper">
                                <div id="gglnltcs-chart"></div>
                            </div>
			            <?php }
		            }
	            } catch ( Google_Service_Exception $e ) {
		            echo __( 'There was an API error', 'bws-google-analytics' ) . ': ' . $e->getCode() . ' : ' . $e->getMessage();
	            } catch ( Exception $e ) {
		            $error = '<div class="error"><strong><p> ' .
		                     __( 'Warning: ', 'bws-google-analytics' ) .
		                     '</strong>' . __( 'Authentication Token expired. Authenticate with your Google Account once again.', 'bws-google-analytics' ) .
		                     '</p></div>';
		            echo $error;
	            }
	            if ( ! empty( $this->options['token'] ) ) { ?>
                    <table class="form-table gglnltcs" id="gglnltcs-log-out-field">
                        <tr>
                            <th><?php _e( 'Deauthorize', 'bws-google-analytics' ); ?></th>
                            <td>
                                <input type="submit" name="gglnltcs_log_out" class="button-secondary" value="<?php _e( 'Log Out', 'bws-google-analytics' ) ?>">
                            </td>
                        </tr>
                    </table>
	            <?php }
            }
            // functionality to show form when its wasn't shown due to unknown error
			if ( ! $form_loaded && empty( $this->options['code'] ) && empty( $this->options['token'] ) ) {
				if ( $this->curl_enabled ) {
					$authUrl  = $this->client->createAuthUrl();
					$disabled = '';
				} else {
					$authUrl  = '#';
					$disabled = ' disabled="disabled"';
				}
				if ( isset( $_POST['code'] ) && false === $redirect ) { ?>
                    <div class="error">
                        <p><?php _e( 'Invalid code. Please, try again', 'bws-google-analytics' ); ?>.</p>
                    </div>
				<?php } ?>
                <div class="gglnltcs-text-information">
                    <p><?php _e( "In order to use Google Analytics by BestWebSoft plugin, you must be signed in with a registered Google Account email address and password. If you don't have Google Account you can create it", 'bws-google-analytics' ); ?>
                        <a href="https://www.google.com/accounts/NewAccount"
                           target="_blank"><?php _e( 'here', 'bws-google-analytics' ); ?>.</a></p>
                    <input id="gglnltcs-google-sign-in" type="button" class="button-primary"
                           onclick="window.open( '<?php echo $authUrl; ?>', 'activate','width=640, height=480, menubar=0, status=0, location=0, toolbar=0' )"
                           value="<?php _e( 'Authenticate with your Google Account', 'bws-google-analytics' ); ?>"<?php echo $disabled; ?>>
                    <noscript>
                        <div class="button-primary gglnltcs-google-sign-in">
                            <a href="<?php echo $authUrl; ?>"
                               target="_blanket"><?php _e( 'Or Click Here If You Have Disabled Javascript', 'bws-google-analytics' ); ?></a>
                        </div>
                    </noscript>
                    <p class="gglnltcs-authentication-instructions"><?php _e( 'When you finish authorization process you will get Google Authentication Code. You must enter this code in the field below and press "Save Changes" button. This code will be used to get an Authentication Token so you can access your website stats.', 'bws-google-analytics' ); ?></p>
                    <p><input id="gglnltcs-authentication-code-input" type="text"
                              name="code" <?php echo $disabled; ?>>
                    </p>
                </div> <?php
			}
		}

		public function getAnalytics() {
		    return $this->analytics;
        }

		public function display_custom_messages() { ?>
            <noscript>
                <div class="error below-h2">
                    <p><strong><?php _e( "Please enable JavaScript in your browser.", 'bws-google-analytics' ); ?></strong></p>
                </div>
            </noscript>
			<?php
		}

		/* Display bws_pro_version block by its name */
		public function pro_block( $block_name = '', $args = array(), $force = false ) {
		    $display = '';
		    if (  ! empty( $this->options['hide_premium_options'] ) ) {
                $display = 'style="display:none"';
            } else {
			    $block_name = 'gglnltcs_' . $block_name;
			    if ( ( ! $this->hide_pro_block || $force ) && function_exists( $block_name ) ) {  ?>
                    <div class="bws_pro_version_bloc gglnltcs-pro-feature" <?php echo $display; ?> >
                        <div class="bws_pro_version_table_bloc">
                            <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'pdf-print' ); ?>"></button>
                            <div class="bws_table_bg"></div>
                            <div class="bws_pro_version">
							    <?php $block_name( $args ); ?>
                            </div>
                        </div>
					    <?php $this->bws_pro_block_links(); ?>
                    </div>
			    <?php }
		    }
		}
	}
}