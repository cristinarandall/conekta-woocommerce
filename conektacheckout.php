<?php
/*
Plugin Name: ConektaCheckout (Gateway using Conekta.js)
Plugin URI: http://cristinarandall.com/
Description: Credit Card Payment Gateway through Conekta.io for Woocommerce.
Version: 1.0
Author: Cristina Randall
Author URI: http://cristinarandall.com/

*/

/*
 * Title   : Conekta Credit and Debit Card Payment Extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : http://cristinarandall.com/
 * License : http://cristinarandall.com/
 */

function conektacheckout_init_your_gateway()
{
    if (class_exists('WC_Payment_Gateway'))
    {
        include_once('conekta_gateway.php');
    }
}

add_action('plugins_loaded', 'conektacheckout_init_your_gateway', 0);
