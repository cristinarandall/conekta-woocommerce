<?php
/*
Plugin Name: Conekta Payment Gateway
Plugin URI: https://github.com/cristinarandall/conekta-woocommerce
Description: Payment Gateway through Conekta.io for Woocommerce for both credit and debit cards as well as cash payments in OXXO.
Version: 1.0.1
Author: Cristina Randall
Author URI: https://github.com/cristinarandall
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/*
 * Title   : Conekta Payment Extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : https://github.com/cristinarandall/conekta-woocommerce
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
