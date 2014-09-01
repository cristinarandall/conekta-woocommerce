<?php
/*
Plugin Name: Conekta Payment Gateway
Plugin URI: http://cristinarandall.com/
Description: Payment Gateway through Conekta.io for Woocommerce for both credit and debit cards as well as cash payments in OXXO.
Version: 1.0
Author: Cristina Randall
Author URI: http://cristinarandall.com/

*/

/*
 * Title   : Conekta Payment Extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : http://cristinarandall.com/
 * License : http://cristinarandall.com/
 */

function conekta_checkout_init_your_gateway()
{
    if (class_exists('WC_Payment_Gateway'))
    {
        include_once('conekta_card_gateway.php');
        include_once('conekta_cash_gateway.php');

    }
}

add_action('plugins_loaded', 'conekta_checkout_init_your_gateway', 0);
