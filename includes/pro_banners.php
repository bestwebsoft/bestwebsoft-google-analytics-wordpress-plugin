<?php
/**
 * Banners in plugin settings page
 * @package Google Analytics by BestWebSoft
 * @since 1.7.4
 */

if ( ! function_exists( 'gglnltcs_pro_block' ) ) {
	function gglnltcs_pro_block( $func, $show_cross = true, $display_always = false ) {
		global $gglnltcs_plugin_info, $wp_version, $gglnltcs_options;
		if ( ! bws_hide_premium_options_check( $gglnltcs_options ) || ! $display_always ) { ?>
            <div class="bws_pro_version_bloc gglnltcs_pro_block <?php echo $func;?>" title="<?php _e( 'This options is available in Pro version of the plugin', 'bws-google-analytics' ); ?>">
                <div class="bws_pro_version_table_bloc">
					<?php if ( $show_cross ) { ?>
                        <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'bws-google-analytics' ); ?>"></button>
					<?php } ?>
                    <div class="bws_table_bg"></div>
                    <div class="bws_pro_version">
					    <?php call_user_func( $func ); ?>
                    </div>
                </div>
                <div class="bws_pro_version_tooltip">
                    <a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/google-analytics/?k=d9da7c9c2046bed8dfa38d005d4bffdb&pn=101&v=<?php echo $gglnltcs_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Google Analytics Pro Plugin"><?php _e( 'Upgrade to Pro', 'bws-google-analytics' ); ?></a>
                    <div class="clear"></div>
                </div>
            </div>
		<?php }
	}
}

if ( ! function_exists( 'gglnltcs_main_block' ) ) {
	function gglnltcs_main_block() { ?>
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
	<?php }
}