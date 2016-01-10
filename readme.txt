=== WooCommerce - Webmoney Payment Gateway===
Contributors: Mofsy
Tags: webmoney, payment gateway, woo commerce, woocommerce, ecommerce, gateway, woo webmoney, wmtransfer, merchant, woo
Requires at least: 3.0
Tested up to: 4.4.1
Stable tag: trunk
WC requires at least: 2.2
WC tested up to: 2.5
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://mofsy.ru/about/help

== Description ==
= English =
Allows you to use Webmoney payment gateway with the WooCommerce plugin.
This is the best payment gateway plugin for Webmoney, because it is the most integrated with Webmoney Merchant capabilities and is available for most versions WooCommerce and Wordpress.

Error found? [bug report](https://mofsy.ru/contacts/email)

Localization support:
<ul style="list-style:none;">
<li>English</li>
<li>Russian</li>
</ul>

== Installation ==
= English =
1. Archive extract and upload "wc-webmoney" to /wp-content/plugins
2. Activation plugin
3. Setting

For https://merchant.wmtransfer.com/conf/purses.asp settings:
<ul style="list-style:none;">
<li>Result URL: http://your_domain/?wc-api=wc_webmoney&action=result</li>
<li>Success URL: http://your_domain/?wc-api=wc_webmoney&action=success</li>
<li>Fail URL: http://your_domain/?wc-api=wc_webmoney&action=fail</li>
<li>Request method for all: POST</li>
<li>Control sign forming method: sha256</li>
</ul>

== Upgrade Notice ==
New settings save

== Changelog ==
= 0.3.0.5 =
* Fix version

= 0.3.0.4 =
* Fix readme

= 0.3.0.3 =
* Fix version

= 0.3.0.2 =
* Fix readme.txt

= 0.3.0.1 =
* Fix test mode
* Add renaming order button text
* Update language files

= 0.2.2.1 =
* Fix readme

= 0.2.1.1 =
* Init release