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
Позволяет использовать платежный шлюз Webmoney с плагином WooCommerce.
Это самый лучший плагин платежного шлюза для Webmoney, т.к. он максимально интегрирован с возможностями Webmoney Merchant и доступен под большинство версий WooCommerce и Wordpress.

Нашли ошибку? [напишите об этом](https://mofsy.ru/contacts/email)

Поддерживаемые языки:
<ul style="list-style:none;">
<li>Английский</li>
<li>Русский</li>
</ul>

== Installation ==
1. Распакуйте архив и загрузите "wc-webmoney" в папку /wp-content/plugins
2. Активируйте плагин
3. Настройте

Настройки для https://merchant.webmoney.ru/conf/purses.asp :
<ul style="list-style:none;">
<li>Result URL: http://your_domain/?wc-api=wc_webmoney&action=result</li>
<li>Success URL: http://your_domain/?wc-api=wc_webmoney&action=success</li>
<li>Fail URL: http://your_domain/?wc-api=wc_webmoney&action=fail</li>
<li>Метод запросов: POST</li>
<li>Метод формирования контрольной подписи: sha256</li>
</ul>

== Upgrade Notice ==

Просто пере-сохраните настройки

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