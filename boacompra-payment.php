<?php
/**
 * Plugin Name:          BoaCompra Payment for WooCommerce
 * Description:          BoaCompra Payment for WooCommerce.
 * Author:               BoaCompra
 * Author URI:           https://boacompra.com/
 * Plugin URI:           https://boacompra.com/
 * Version:              1.0.3
 * Text Domain:          woocommerce-boacompra-payment
 * Domain Path:          /languages
 * WC requires at least: 3.0.0
 * WC tested up to:      3.4.0
 * Copyright:            © 2019 BoaCompra
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'BOACOMPRA_VERSION', '1.0.3' );
define( 'BOACOMPRA_DOMAIN', 'boacompra-payment' );
define( 'BOACOMPRA_BASE_DIR', __FILE__ );

if ( ! class_exists( 'WC_Boacompra_Payment' ) ) {
    include_once dirname( __FILE__ ) . '/classes/boacompra-payment-base.php';
    add_action( 'plugins_loaded', array( 'WC_Boacompra_Payment', 'init' ) );
    add_action( 'woocommerce_api_wc_gateway_boacompra_ipn', array('WC_Boacompra_Payment_Gateway', 'ipn_boacompra'));
    add_action( 'woocommerce_api_wc_gateway_boacompra_ipn_refund', array('WC_Boacompra_Payment_Gateway', 'ipn_boacompra_refund'));
    add_action( 'wp_ajax_consultboacompra', array('WC_Boacompra_Payment_Gateway', 'consultTransaction'));
    add_action( 'wc_ajax_boacompra_installments', array('WC_Boacompra_Payment_Gateway', 'boacompra_installments'));
    add_action('woocommerce_api_wc_boacompra_hosted_request', array('WC_Boacompra_Payment_Gateway', 'request_hosted'));
}