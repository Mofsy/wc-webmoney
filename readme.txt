=== WooCommerce - Webmoney Payment Gateway===
Contributors: Mofsy
Tags: webmoney, payment gateway, woo commerce, woocommerce, ecommerce, gateway, woo webmoney, wmtransfer, merchant, woo
Requires at least: 3.0
Tested up to: 4.9
Requires PHP: 5.4
Stable tag: trunk
WC requires at least: 3.0
WC tested up to: 3.3
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://mofsy.ru/others/help

== Description ==
Allows you to use Webmoney payment gateway with the WooCommerce plugin.
This is the best payment gateway plugin for Webmoney, because it is the most integrated with Webmoney Merchant capabilities and is available for most versions WooCommerce and Wordpress.

Support purses: **WMR**, **WMZ**, **WME**, **WMU**

== Translations ==
* English - default, always included
* Russian - always included

*Note:* All my plugins are localized/ translateable by default. This is very important for all users worldwide.
So please contribute your language to the plugin to make it even more useful. For translating I recommend the awesome for validating the ["Poedit Editor"](http://www.poedit.net/).

== Installation ==
1. Archive extract and upload folder "wc-webmoney" to /wp-content/plugins
2. Activation plugin
3. Setting

For https://merchant.wmtransfer.com/conf/purses.asp settings:
<ul style="list-style:none;">
<li>Result URL: http://your_domain/?wc-api=wc_webmoney&action=result</li>
<li>Send parameters in the preliminary inquiry: Yes, security is good.</li>
<li>Success URL: http://your_domain/?wc-api=wc_webmoney&action=success</li>
<li>Fail URL: http://your_domain/?wc-api=wc_webmoney&action=fail</li>
<li>Request method for all: POST</li>
<li>Control sign forming method: sha256</li>
</ul>

== Changelog ==

= 1.0.0.1 =
* Add support WC version 3.3
* Init Gatework
* Remove free report
* Fix translation files
* Remove support WC versions 2.x

= 0.6.1.2 =
* Test on WP 4.8
* Test on WP 4.9
* Other fix

= 0.6.1.1 =
* Minor fix

= 0.6.0.1 =
* Test mode fix
* Test on WP 4.7

= 0.5.0.1 =
* Add WPML support
* Test on WP 4.5

= 0.4.3.1 =
* Fix check result #2

= 0.4.2.1 =
* Fix check result

= 0.4.1.2 =
* Fix readme

= 0.4.1.1 =
* Fix readme
* Fix PRE request

= 0.4.0.1 =
* Add notices
* Add debug tool (logging and send to author)
* Fix action error is TEST mode
* Minor fixes

= 0.3.0.5 =
* Fix version
* Fix readme

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