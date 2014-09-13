=== Conekta Woocommerce v.0.1.1 ===
Contributors: cristinarandall
Tags: conekta, payment gateway
Requires at least: 3.5.2
Tested up to: 3.5.2
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Payment Gateway for Conekta.io

This bundles functionality to process credit cards and cash (OXXO) payments securely as well as send email notifications to your customers when they complete a successful purchase.

== Description ==

Current version features:

* Uses Conekta.js      - No PCI Compliance Issues ( Requires an SSL Certificate)
* Credit and Debit Card implemented
* Cash payments implemented
* Sandbox testing capability.
* Automatic order status management
* Email notifications on successful purchase

== Installation ==

* Upload the plugin zip file in Plugins > Add New and then click "Install Now"
* Once installed, activate the plugin.
* Add your API keys in Woocommerce > Settings > Checkout from your Conekta account (admin.conekta.io) in https://admin.conekta.io#developers.keys
* To manage orders for offline payments so that the status changes dynamically, you will need to add the following url as a webhook in your Conekta account:
http://tusitio.com/wc-api/WC_Conekta_Cash_Gateway

Replace to tusitio.com with your domain name

