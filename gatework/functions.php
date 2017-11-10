<?php
/*
  +----------------------------------------------------------+
  | Woocommerce - Webmoney Payment Gateway                   |
  +----------------------------------------------------------+
  | Author: Oleg Budrin (Mofsy) <support@mofsy.ru>           |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

/**
 * Get current version WooCommerce
 *
 * @since 0.4.0.1
 */
if(!function_exists('gatework_wc_get_version_active'))
{
	function gatework_wc_get_version_active()
	{
		if ( ! function_exists( 'get_plugins' ) )
		{
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugin_folder = get_plugins( '/' . 'woocommerce' );
		$plugin_file = 'woocommerce.php';

		if(isset( $plugin_folder[$plugin_file]['Version'] ))
		{
			return $plugin_folder[$plugin_file]['Version'];
		}

		return null;
	}
}

