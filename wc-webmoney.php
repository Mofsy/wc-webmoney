<?php
/*
    Plugin Name: WooCommerce - Webmoney Payment Gateway
    Plugin URI: https://mofsy.ru/projects/woocommerce-webmoney-payment-gateway
    Description: Allows you to use Webmoney payment gateway with the WooCommerce plugin.
    Version: 1.0.0.1
    Author: Mofsy
    Author URI: https://mofsy.ru
    Text Domain: wc-webmoney
    Domain Path: /languages
    Copyright: © 2015-2018 Mofsy
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if(!defined('ABSPATH'))
{
	exit;
}

/**
 * Run
 *
 * @action woocommerce_webmoney_init
 */
add_action('plugins_loaded', 'woocommerce_webmoney_init', 0);

/**
 * Init plugin gateway
 */
function woocommerce_webmoney_init()
{
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
	define('WC_WEBMONEY_URL', plugin_dir_url(__FILE__));

	/**
	 * GateWork
	 */
	include_once __DIR__ . '/gatework/init.php';

	/**
	 * Gateway main class
	 */
	include_once __DIR__ . '/class-wc-webmoney.php';

	/**
     * Load language
	 *
	 * todo: optimize load
     */
    load_plugin_textdomain( 'wc-webmoney',  false, __DIR__ . '/languages' );

    /**
     * Check status in Order
     */
    if( class_exists('WooCommerce_Payment_Status') )
    {
        add_filter( 'woocommerce_valid_order_statuses_for_payment', array( 'WC_Webmoney', 'valid_order_statuses_for_payment' ), 52, 2 );
    }

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param $methods
	 *
	 * @return array
	 */
    function wc_webmoney_gateway_add($methods)
    {
        $methods[] = 'WC_Webmoney';

        return $methods;
    }

	/**
	 * Add payment method
	 *
	 * @filter wc_webmoney_gateway_add
	 */
    add_filter('woocommerce_payment_gateways', 'wc_webmoney_gateway_add');
}