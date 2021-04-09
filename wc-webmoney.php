<?php
/**
 * Plugin Name: Payment gateway - Webmoney for WooCommerce
 * Description: Allows you to use Webmoney payment gateway with the WooCommerce plugin.
 * Plugin URI: https://mofsy.ru/projects/wc-webmoney
 * Version: 2.1.0
 * WC requires at least: 3.0
 * WC tested up to: 3.9
 * Text Domain: wc-webmoney
 * Domain Path: /languages
 * Author: Mofsy
 * Author URI: https://mofsy.ru
 * Copyright: Mofsy © 2015-2021
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Mofsy/WC_Webmoney
 */
defined('ABSPATH') || exit;

/**
 * Run
 *
 * @action wc_webmoney_init
 */
add_action('plugins_loaded', 'wc_webmoney_init', 0);

/**
 * Init plugin gateway
 *
 * @action wc_webmoney_gateway_init_before
 * @action wc_webmoney_gateway_init_after
 */
function wc_webmoney_init()
{
	// hook
	do_action('wc_webmoney_gateway_init_before');

	/**
     * Main check
     */
    if (!class_exists('WC_Payment_Gateway') || class_exists('WC_Webmoney'))
    {
        return;
    }

	/**
	 * Define plugin url
	 */
	if (!defined( 'WC_WEBMONEY_URL' ))
	{
		define('WC_WEBMONEY_URL', plugin_dir_url(__FILE__));
	}

	/**
	 * Plugin Dir
	 */
	if (!defined( 'WC_WEBMONEY_PLUGIN_DIR' ))
	{
		define( 'WC_WEBMONEY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin Name
	 */
	if (!defined( 'WC_WEBMONEY_PLUGIN_NAME' ))
	{
		define( 'WC_WEBMONEY_PLUGIN_NAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * GateWork
	 */
	include_once __DIR__ . '/gatework/init.php';

	/**
	 * Gateway main class
	 */
	include_once __DIR__ . '/includes/class-wc-webmoney.php';

	/**
	 * Run
	 */
	WC_Webmoney::instance();

	// hook
	do_action('wc_webmoney_gateway_init_after');
}