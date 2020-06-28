<?php

class WC_Boacompra_Payment
{

    public static function init()
    {
        // Load plugin text domain.
        add_action('init', array(__CLASS__, 'load_plugin_textdomain'));

        // Checks with WooCommerce is installed.
        if (class_exists('WC_Payment_Gateway')) {
            self::includes();

            add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));
            add_filter('woocommerce_billing_fields', array(__CLASS__, 'extra_billing_fields'), 9999);
            add_filter('woocommerce_shipping_fields', array(__CLASS__, 'extra_shipping_fields'), 9999);

            if (is_admin()) {
                add_filter('plugin_action_links_'.plugin_basename(BOACOMPRA_BASE_DIR), array(__CLASS__, 'plugin_action_links'));
            }
        } else {
            add_action('admin_notices', array(__CLASS__, 'woocommerce_missing_notice'));
        }
    }

    public static function add_gateway($methods)
    {
        $methods[] = 'WC_Boacompra_Payment_Gateway';

        return $methods;
    }

    private static function includes()
    {
        include_once dirname(__FILE__).'/boacompra-payment-gateway.php';
    }

    public static function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="'.esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=boacompra-payment')).'">'.__('BoaCompra Settings', BOACOMPRA_DOMAIN).'</a>';

        return array_merge($plugin_links, $links);
    }

    /**
     * Transparent checkout billing fields.
     *
     * @param array $fields Checkout fields.
     * @return array
     */
    public static function extra_billing_fields($fields)
    {
        if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
            if (isset($fields['billing_neighborhood'])) {
                $fields['billing_neighborhood']['required'] = true;
            }
            if (isset($fields['billing_number'])) {
                $fields['billing_number']['required'] = true;
            }
        }

        return $fields;
    }

    /**
     * Transparent checkout billing fields.
     *
     * @param array $fields Checkout fields.
     * @return array
     */
    public static function extra_shipping_fields($fields)
    {
        if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
            if (isset($fields['shipping_neighborhood'])) {
                $fields['shipping_neighborhood']['required'] = true;
            }
        }

        return $fields;
    }

    public static function get_template_dir()
    {
        return plugin_dir_path(BOACOMPRA_BASE_DIR).'templates/';
    }

    public static function load_plugin_textdomain()
    {
        $locale = apply_filters('plugin_locale', get_locale(), 'boacompra-payment');

        load_textdomain('boacompra-payment', trailingslashit(WP_LANG_DIR).'boacompra/boacompra-payment-'.$locale.'.mo');
        load_plugin_textdomain('boacompra-payment', false, dirname(plugin_basename(BOACOMPRA_BASE_DIR)).'/languages/');
    }
}
