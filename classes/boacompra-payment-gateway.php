<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Boacompra_Payment_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id = 'boacompra-payment';
        //$this->icon               = apply_filters( 'woocommerce_boacompra_icon', plugins_url( 'assets/images/boacompra.png', plugin_dir_path( __FILE__ ) ) );
        $this->method_title = __('BoaCompra Payment for WooCommerce', BOACOMPRA_DOMAIN);
        $this->method_description = __('Start selling in Latam with more than 140 local payments options.', BOACOMPRA_DOMAIN);
        $this->has_fields = true;
        //$this->order_button_text = __('Proceed to payment', BOACOMPRA_DOMAIN);

        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->merchantid = $this->get_option('merchantid');
        $this->secretkey = $this->get_option('secretkey');
        $this->sandbox_merchantid = $this->get_option('sandbox_merchantid');
        $this->sandbox_secretkey = $this->get_option('sandbox_secretkey');

        $this->boacompra_environment = $this->get_option('boacompra_environment', 'sandbox');
        $this->mode = $this->get_option('boacompra_mode', 'direct');
        $this->redirect_message = $this->get_option('redirect_message');
        $groups = array(
            'card' => $this->get_option('card'),
            'cash' => $this->get_option('cash'),
            'online wallet' => $this->get_option('wallet'),
            'transfer' => $this->get_option('transfer'),
            'sms' => $this->get_option('sms'),
        );
        $store_raw_country = get_option('woocommerce_default_country');
        $split_country = explode(":", $store_raw_country);
        $store_country = $split_country[0];
        if ($store_country != 'TR') {
            unset($groups['sms']);
        }
        foreach ($groups as $key => $value) {
            if ($value == 'yes') {
                $this->hosted_groups[] = $key;
            }
        }

        $this->credit = $this->get_option('credit', 'yes');
        $this->billet = $this->get_option('billet', 'yes');
        $this->debit = $this->get_option('debit', 'yes');
        $this->billet_message = $this->get_option('billet_message');
        $this->credit_max_installment = $this->get_option('maximum_installment', 6);

        $this->debug = $this->get_option('debug');
        $this->analysisStatuses = array('1', '2');
        $this->approvedStatuses = array('3', '4');
        $this->reprovedStatuses = array('5', '6', '7', '8');
        $this->supports = array('products', 'refunds');

        if ($this->debug == 'yes') {
            $this->context = array('source' => $this->id);
            if (function_exists('wc_get_logger')) {
                $this->log = wc_get_logger();
            } else {
                $this->log = new WC_Logger();
            }
        }

        // Main actions.
        //add_action('woocommerce_api_wc_boacompra_gateway', array($this, 'ipn_handler'));
        //add_action('valid_boacompra_ipn_request', array($this, 'update_order_status'));
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'order_info'));
        //add_action('woocommerce_receipt_'.$this->id, array($this, 'receipt_page'));

        // Transparent checkout actions.
        if ($this->mode == 'direct') {
            add_action('woocommerce_thankyou_'.$this->id, array($this, 'order_received'));
            add_action('woocommerce_order_details_after_order_table', array($this, 'pending_payment_message'), 5);
            //add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);
            add_action('wp_enqueue_scripts', array($this, 'checkout_scripts'));
        }
    }

    public function init_form_fields()
    {
        $statuses = array('' => '-- Selecione um status --');
        $statuses = array_merge($statuses, wc_get_order_statuses());
        $url = get_site_url().'/wc-api/wc_gateway_callback/';
        $this->form_fields = array(
            'enabled'               => array(
                'title'    => __('Enable / Disable', BOACOMPRA_DOMAIN),
                'type'     => 'checkbox',
                'label'    => __('Enable BoaCompra Payment', BOACOMPRA_DOMAIN),
                'desc_tip' => false,
                'default'  => 'yes',
            ),
            'title'                 => array(
                'title'       => __('Method Title', BOACOMPRA_DOMAIN),
                'type'        => 'text',
                'description' => __('This controls the title that users will see during checkout.', BOACOMPRA_DOMAIN),
                'default'     => __('BoaCompra', BOACOMPRA_DOMAIN),
                'desc_tip'    => true,
            ),

            'boacompra_environment' => array(
                'title'    => __('BoaCompra Environment', BOACOMPRA_DOMAIN),
                'type'     => 'select',
                'desc_tip' => true,
                'default'  => 'production',
                'label'    => __('Choose Environment for BoaCompra Integration', BOACOMPRA_DOMAIN),
                'options'  => array(
                    'sandbox'    => __('Sandbox', BOACOMPRA_DOMAIN),
                    'production' => __('Production', BOACOMPRA_DOMAIN),
                ),
            ),
            'merchantid'            => array(
                'title'       => __('BoaCompra MerchantID', BOACOMPRA_DOMAIN),
                'type'        => 'text',
                'description' => __('BoaCompra MerchantID', BOACOMPRA_DOMAIN),
                'default'     => '',
                'desc_tip'    => false,
            ),
            'secretkey'             => array(
                'title'       => __('BoaCompra SecretKey', BOACOMPRA_DOMAIN),
                'type'        => 'text',
                'description' => __('BoaCompra SecretKey', BOACOMPRA_DOMAIN),
                'default'     => '',
                'desc_tip'    => false,
            ),

            'sandbox_merchantid'    => array(
                'title'       => __('BoaCompra Sandbox MerchantID', BOACOMPRA_DOMAIN),
                'type'        => 'text',
                'description' => __('BoaCompra Sandbox MerchantID', BOACOMPRA_DOMAIN),
                'default'     => '',
                'desc_tip'    => false,
            ),
            'sandbox_secretkey'     => array(
                'title'       => __('BoaCompra Sandbox SecretKey', BOACOMPRA_DOMAIN),
                'type'        => 'text',
                'description' => __('BoaCompra Sandbox SecretKey', BOACOMPRA_DOMAIN),
                'default'     => '',
                'desc_tip'    => false,
            ),

            'boacompra_mode'        => array(
                'title'    => __('BoaCompra Mode', BOACOMPRA_DOMAIN),
                'type'     => 'select',
                'desc_tip' => true,
                'default'  => 'direct',
                'label'    => __('Choose Environment for BoaCompra Integration', BOACOMPRA_DOMAIN),
                'options'  => array(
                    'direct' => __('Direct Checkout (Transparente)', BOACOMPRA_DOMAIN),
                    'hosted' => __('Hosted Checkout (Redirect)', BOACOMPRA_DOMAIN),
                ),
            ),

            'redirect_message'      => array(
                'title'       => __('Redirect Message', BOACOMPRA_DOMAIN),
                'type'        => 'text',
                'description' => __('Message showed to client on checkout', BOACOMPRA_DOMAIN),
                'default'     => __('Confirm your order to be redirected to the payment page.'),
                'desc_tip'    => false,
            ),

            'transparent_checkout'  => array(
                'title'       => __('Direct Checkout Options', BOACOMPRA_DOMAIN),
                'type'        => 'title',
                'description' => '',
            ),
            'credit'                => array(
                'title'   => __('Credit Card', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable Credit Card for Direct Checkout', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),
            'billet'                => array(
                'title'   => __('Billet', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable Billet for Direct Checkout', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),
            'debit'                 => array(
                'title'   => __('E-Wallet', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable E-Wallet for Direct Checkout', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),

            'hosted_checkout'  => array(
                'title'       => __('Hosted Checkout Payment Groups', BOACOMPRA_DOMAIN),
                'type'        => 'title',
                'description' => '',
            ),
            'card'                => array(
                'title'   => __('Credit Card', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable Credit Card for Hosted Checkout', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),
            'cash'            => array(
                'title'   => __('Cash', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable Cash for Hosted Checkout', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),
            'wallet'       => array(
                'title'   => __('E-Wallet', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable E-Wallet for Hosted Checkout', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),
            'transfer'            => array(
                'title'   => __('Transfer', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable Transfer for Hosted Checkout', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),
            'sms'            => array(
                'title'   => __('SMS', BOACOMPRA_DOMAIN),
                'type'    => 'checkbox',
                'label'   => __('Enable SMS for Hosted Checkout (Turkey only)', BOACOMPRA_DOMAIN),
                'default' => 'yes',
            ),

            'credit_card_block'     => array(
                'title'       => __('Credit Card Options', BOACOMPRA_DOMAIN),
                'type'        => 'title',
                'description' => '',
            ),
            'maximum_installment'   => array(
                'title'       => __('Installment Within', BOACOMPRA_DOMAIN),
                'type'        => 'select',
                'description' => __('Maximum number of installments for orders in your store.', BOACOMPRA_DOMAIN),
                'desc_tip'    => true,
                'class'       => 'wc-enhanced-select',
                'default'     => '6',
                'options'     => array(
                    '1'  => '1x',
                    '2'  => '2x',
                    '3'  => '3x',
                    '4'  => '4x',
                    '5'  => '5x',
                    '6'  => '6x',
                    '7'  => '7x',
                    '8'  => '8x',
                    '9'  => '9x',
                    '10' => '10x',
                    '11' => '11x',
                    '12' => '12x',
                ),
            ),

            'billet_block'          => array(
                'title'       => __('Billet Options', BOACOMPRA_DOMAIN),
                'type'        => 'title',
                'description' => '',
            ),
            'billet_message'        => array(
                'title'       => __('Billet Checkout Message', BOACOMPRA_DOMAIN),
                'type'        => 'text',
                'description' => __('This controls the message that users will see before print the billet.', BOACOMPRA_DOMAIN),
                'default'     => __('Após a confirmação do pedido, lembre-se de quitar o boleto o mais rápido possível.', BOACOMPRA_DOMAIN),
            ),

            'extra_options'         => array(
                'title'       => __('Extra Options', BOACOMPRA_DOMAIN),
                'type'        => 'title',
                'description' => '',
            ),
            'debug'                 => array(
                'title'       => __('Enable Module Logs', BOACOMPRA_DOMAIN),
                'label'       => __('Enable', BOACOMPRA_DOMAIN),
                'type'        => 'checkbox',
                'desc_tip'    => false,
                'default'     => 'no',
                'description' => sprintf(__('Acesso aos logs do módulo: %s', BOACOMPRA_DOMAIN), $this->link_log()),
            ),

            'status_iniciado'       => array(
                'title'       => __('Status Iniciado', BOACOMPRA_DOMAIN),
                'type'        => 'select',
                'desc_tip'    => true,
                'description' => __('O pedido mudará automaticamente para este status em caso de aprovação no BoaCompra', BOACOMPRA_DOMAIN),
                'default'     => '',
                'options'     => $statuses,
            ),
            'status_aprovado'       => array(
                'title'       => __('Status Aprovado', BOACOMPRA_DOMAIN),
                'type'        => 'select',
                'desc_tip'    => true,
                'description' => __('O pedido mudará automaticamente para este status em caso de aprovação no BoaCompra', BOACOMPRA_DOMAIN),
                'default'     => '',
                'options'     => $statuses,
            ),
            'status_cancelado'      => array(
                'title'       => __('Status Cancelado', BOACOMPRA_DOMAIN),
                'type'        => 'select',
                'desc_tip'    => true,
                'description' => __('O pedido mudará automaticamente para este status quando a transação for cancelada no BoaCompra', BOACOMPRA_DOMAIN),
                'default'     => '',
                'options'     => $statuses,
            ),
            'status_aguardando'     => array(
                'title'       => __('Status Aguardando', BOACOMPRA_DOMAIN),
                'type'        => 'select',
                'desc_tip'    => true,
                'description' => __('O pedido mudará automaticamente para este status quando a transação estiver no status aguardando no BoaCompra', BOACOMPRA_DOMAIN),
                'default'     => '',
                'options'     => $statuses,
            ),
        );

        $store_raw_country = get_option('woocommerce_default_country');
        $split_country = explode(":", $store_raw_country);
        $store_country = $split_country[0];
        if ($store_country != 'TR') {
            unset($this->form_fields['sms']);
        }
    }

    /**
     * Display pending payment message in order details.
     *
     * @param  int $order_id Order id.
     *
     * @return string        Message HTML.
     */
    public static function pending_payment_message($order_id)
    {
        $order = new WC_Order($order_id);
        $order_id = is_callable(array($order, 'get_id')) ? $order->get_id() : $order->id;
        $method = is_callable(array($order, 'get_payment_method')) ? $order->get_payment_method() : $order->payment_method;
        $urlBoleto = get_post_meta($order_id, '_bc_billet_url', true);
        $linha = get_post_meta($order_id, '_bc_digitable_line', true);
        $barcode = get_post_meta($order_id, '_bc_barcode_number', true);
        $status = get_post_meta($order_id, '_bc_status', true);
        $allowedStatuses = array('PENDING');
        if (!empty($urlBoleto) && $method == BOACOMPRA_DOMAIN && in_array($status, $allowedStatuses)) {
            if ('postpay' == get_post_meta($order_id, '_bc_method', true)) {
                $textoBotao = __('Imprimir Boleto');
                $mensagem = __('Clique no link ao lado para imprimir o boleto.');
            } else {
                $textoBotao = __('Wallet');
                $urlBoleto = '';
            }
            if (!empty($urlBoleto)) {
                wp_enqueue_script( 'barcode-script', plugin_dir_url(__DIR__).'js/JsBarcode.code39.min.js' );
                wp_enqueue_script( 'render-barcode-script', plugin_dir_url(__DIR__).'js/renderbarcode.js', array('barcode-script') );
                $html = '<div class="woocommerce-info">';
                $html .= sprintf('<a class="button" href="%s" target="_blank" style="display: block !important; visibility: visible !important;">%s</a>', esc_url($urlBoleto), $textoBotao.' &rarr;');
                $html .= $mensagem.'<br />';
                $html .= '</div>';
                $html .= '<div>';
                $html .= '<input type="text" value="'.$linha.'" style="width: 75%;" disabled="disabled" id="linhaDigitavel"/><button onclick="copyLine()">Copiar código</button><br />';
                $html .= '<svg class="billet_barcode"
                            jsbarcode-format="code39"
                            jsbarcode-value="'.$barcode.'"
                            jsbarcode-textmargin="0"
                            jsbarcode-displayvalue="false"
                            style="width: 100%;">
                        </svg>';
                $html .= '</div>';
                echo $html;
            }
        }
    }

    public function order_received($order_id)
    {
        $order = new WC_Order($order_id);
        $method = is_callable(array($order, 'get_payment_method')) ? $order->get_payment_method() : $order->payment_method;
        $additional = '';
        if ($method == $this->id) {
            $urlBoleto = get_post_meta($order_id, '_bc_billet_url', true);
            $email = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
            $msg = sprintf(__('Você receberá um e-mail em %s com todos os detalhes do pedido.', BOACOMPRA_DOMAIN), $email);
            $mensagem = '';
            $class = 'woocommerce-info';
            if ('postpay' == get_post_meta($order_id, '_bc_method', true)) {
                $mensagem .= __('Pending Payment.', BOACOMPRA_DOMAIN)." $msg <br />";
                $mensagem .= "Não esqueça de pagar seu boleto o mais rápido possível. <br />";
                $mensagem .= "Seu pedido só será processado após a confirmação do pagamento.";
            } else {
                $status = get_post_meta($order_id, '_bc_status', true);
                if (in_array($status, $this->approvedStatuses)) {
                    $mensagem .= __('Approved Payment.', BOACOMPRA_DOMAIN)." $msg <br />";
                    $class = 'woocommerce-message';
                } elseif (in_array($status, $this->analysisStatuses) || !in_array($status, $this->reprovedStatuses)) {
                    //timeout ou análise
                    $mensagem .= "Estamos aguardando a confirmação de pagamento e em breve sua compra será efetivada. <br />";
                    $mensagem .= "Assim que isso ocorrer, você será notificado por e-mail.";
                } else {
                    //reprovado / erro
                    $class = 'woocommerce-error';
                    $mensagem .= __('Not Authorised Payment.', BOACOMPRA_DOMAIN)." <br />";
                    $mensagem .= "Algum dado pode estar incorreto ou ocorreu algum problema com a operadora. <br />";
                    $mensagem .= "Por favor, revise seus dados de pagamento e tente novamente.";
                }
            }
            if (!empty($mensagem)) {
                $html = '<div class="'.$class.'">';
                $html .= $mensagem.'<br />';
                $html .= '</div>';
                echo $html;
            }
            if (!empty($additional)) {
                echo $additional;
            }
        }
    }

    public function order_info()
    {
        if (!empty($_REQUEST['post'])) {
            $order_id = sanitize_text_field($_REQUEST['post']);
            $order = new WC_Order($order_id);
            $method = is_callable(array($order, 'get_payment_method')) ? $order->get_payment_method() : $order->payment_method;
            if ($method == 'boacompra-payment') {
                $status = get_post_meta($order_id, '_bc_status', true);
                $params = array(
                    't_id'       => get_post_meta($order_id, '_bc_tid', true),
                    't_msg'      => $this->getBoaCompraMessage($status),
                    'order_id'   => $order_id,
                    'parcelas'   => get_post_meta($order_id, '_bc_installment', true),
                    'bandeira'   => get_post_meta($order_id, '_bc_card_type', true),
                    'status'     => $status,
                    'billet_url' => get_post_meta($order_id, '_bc_billet_url', true),
                    'metodo'     => get_post_meta($order_id, '_bc_method', true),
                );
                wc_get_template(
                    'boacompra-payment-admin.php', $params, '', WC_Boacompra_Payment::get_template_dir()
                );
            }
        }
    }

    /**
     * Recupera o IP do Cliente
     *
     * @return string
     */
    public function getClientIp($onlyipv4 = false)
    {
        $ip = '';
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if ($onlyipv4) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            } elseif (filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $_SERVER['SERVER_ADDR'];
            } else {
                $host = gethostname();
                $ip = gethostbyname($host);
                return $ip;
            }
        }

        return '';
    }

    public function is_available()
    {
        $available = $this->get_option('enabled') == 'yes';
        return $available;
    }

    public function admin_options()
    {
        wp_enqueue_script('boacompra-config', plugins_url('js/config.js', plugin_dir_path(__FILE__)), array('jquery'), BOACOMPRA_VERSION, true);

        parent::admin_options();
    }

    public function get_merchantid()
    {
        return $this->is_sandbox() ? $this->sandbox_merchantid : $this->merchantid;
    }

    public function get_secretkey()
    {
        return $this->is_sandbox() ? $this->sandbox_secretkey : $this->secretkey;
    }

    public function is_sandbox()
    {
        return $this->boacompra_environment == 'sandbox';
    }

    public function is_production()
    {
        return $this->boacompra_environment == 'production';
    }

    public function domain()
    {
        return 'boacompra.com';
    }

    public function static_path()
    {
        return 'https://stc.'.$this->domain();
    }

    public function api_path()
    {
        return 'https://api.'.($this->is_sandbox() ? 'sandbox.' : '').$this->domain();
    }

    public function consult_transaction_url($transaction_code)
    {
        if (!empty($transaction_code)) {
            return $this->api_path().'/v3/transactions/'.$transaction_code;
        } else {
            return $this->api_path().'/v2/transactions';
        }
    }

    public function notification_url($notification_code)
    {
        return $this->api_path().'/v3/transactions/notifications/'.$notification_code;
    }

    public function is_direct()
    {
        return $this->mode == 'direct';
    }

    public function payment_fields()
    {
        if ($this->is_direct()) {
            $params = array(
                'credit'                 => $this->credit,
                'postpay'                => $this->billet,
                'wallet'                 => $this->debit,
                'billet_message'         => $this->billet_message,
                'credit_max_installment' => $this->credit_max_installment,
                'static_path'            => plugin_dir_url(__DIR__).'img/',
                'installment_url'        => get_site_url().'?wc-ajax=boacompra_installments',
                'total'                  => $this->get_order_total(),
                'errors'                 => array(
                    'invalid_card'        => __('Invalid credit card number.', BOACOMPRA_DOMAIN),
                    'invalid_cvv'         => __('Invalid CVV.', BOACOMPRA_DOMAIN),
                    'invalid_name'        => __('Invalid name.', BOACOMPRA_DOMAIN),
                    'invalid_expiry'      => __('Invalid expiry date.', BOACOMPRA_DOMAIN),
                    'expired_card'        => __('Expired card.', BOACOMPRA_DOMAIN),
                    'invalid_installment' => __('Please choose an installment option.', BOACOMPRA_DOMAIN),
                ),
            );
            wc_get_template(
                'boacompra-payment-transparent.php', $params, '', WC_Boacompra_Payment::get_template_dir()
            );
        } else {
            echo $this->redirect_message;
        }
    }

    public function format_woo_version($version)
    {
        $aux = explode('.', $version);
        $formatted = $aux[0].'.'.(array_key_exists(1, $aux) ? $aux[1] : 0);
        return $formatted;
    }

    public function getBoaCompraMessage($status)
    {
        switch ($status) {
            case 'CANCELLED':
                $message = 'Transaction was cancelled by UOL BoaCompra';
                break;
            case 'COMPLETE':
                $message = 'Transaction was paid and approved. Products should be deliver to the End User';
                break;
            case 'CHARGEBACK':
                $message = 'An approved transaction was cancelled by the End User. Please consult your Account Manager for costs.';
                break;
            case 'EXPIRED':
                $message = 'Payment date of transaction expired';
                break;
            case 'NOT-PAID':
                $message = 'Payment confirmation of transaction was not received';
                break;
            case 'PENDING':
                $message = 'Transaction was created';
                break;
            case 'REFUNDED':
                $message = 'A partial or full refund was requested and accepted for the transaction';
                break;
            case 'UNDER-REVIEW':
                $message = 'Transaction is under review by UOL BoaCompra Analysis team. It will be approved or cancelled based on the analysis.';
                break;
            default:
                $message = 'Erro ao obter o status';
                break;
        }
        return $message;
    }

    public function updateOrderStatus($order, $status, $type = '')
    {
        $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
        $mensagem = '';
        $status_iniciado = $this->get_option('status_iniciado');
        $status_aprovado = $this->get_option('status_aprovado');
        $status_cancelado = $this->get_option('status_cancelado');
        $status_aguardando = $this->get_option('status_aguardando');
        $this->writeLog("updateOrderStatus()", false, 'info');
        $this->writeLog("OrderID: ".$order_id, false, 'info');
        $this->writeLog("Status BoaCompra: ".$status, false, 'info');

        switch ($status) {
            case 'PENDING':
                //Aguardando Pagamento
                $mensagem = 'Transaction was created.';
                $this->mudaStatus($order, $status_iniciado, __('BoaCompra: The buyer initiated the transaction, but so far the BoaCompra not received any payment information.', BOACOMPRA_DOMAIN));
                break;
            case 'UNDER-REVIEW':
                //Em Análise
                $mensagem = 'Transaction is under review by UOL BoaCompra Analysis team. It will be approved or cancelled based on the analysis.';
                $this->mudaStatus($order, $status_aguardando, __('BoaCompra: Payment under review.', BOACOMPRA_DOMAIN));
                break;
            case 'COMPLETE':
                //Paga
                $mensagem = 'Transaction was paid and approved. Products should be deliver to the End User.';
                $this->mudaStatus($order, $status_aprovado, __('BoaCompra: Payment approved.', BOACOMPRA_DOMAIN));
                break;
            case 'REFUNDED':
                //Devolvida
                $mensagem = 'A partial or full refund was requested and accepted for the transaction.';
                $this->mudaStatus($order, $status_cancelado, __('BoaCompra: Payment refunded.', BOACOMPRA_DOMAIN));
                break;
            case 'CANCELLED':
            case 'EXPIRED':
            case 'NOT-PAID':
                //Cancelada
                $mensagem = 'Transaction was cancelled by UOL BoaCompra.';
                $this->mudaStatus($order, $status_cancelado, __('BoaCompra: Payment canceled.', BOACOMPRA_DOMAIN));
                break;
            case 'CHARGEBACK':
                //Chargeback Debitado
                $mensagem = 'An approved transaction was cancelled by the End User. Please consult your Account Manager for costs.';
                $this->mudaStatus($order, $status_cancelado, __('BoaCompra: Payment refunded.', BOACOMPRA_DOMAIN));
                break;
            default:
                break;
        }
        if (!empty($mensagem)) {
            $mensagem = !empty($type) ? '['.strtoupper($type).'] '.$mensagem : $mensagem;
            $this->writeLog("Mensagem: ".$mensagem, false, 'info');
            $order->add_order_note($mensagem);
        }
    }

    public function reduceStock($order_id)
    {
        if (function_exists('wc_reduce_stock_levels')) {
            wc_reduce_stock_levels($order_id);
        }
    }

    public function increaseStock($order_id)
    {
        if (function_exists('wc_increase_stock_levels')) {
            wc_increase_stock_levels($order_id);
        }
    }

    public static function mudaStatus($order, $status, $mensagem)
    {
        if (!empty($status)) {
            $order->update_status($status, $mensagem.' - ');
        }
    }

    public function validate_fields()
    {
        return true;
    }

    public function direct_script()
    {
        return $this->static_path().'/payment.boacompra.min.js';
    }

    public function checkout_scripts()
    {
        if ($this->is_available()) {
            wp_enqueue_script('boacompra-directpayment-js', $this->direct_script(), array(), null, true);

            $total = $this->get_order_total();
            wp_localize_script(
                'boacompra-functions',
                'boacompra_payment_card_params',
                array(
                    'credit_max_installment' => $this->credit_max_installment,
                    'total'                  => $total,
                    'invalid_card'           => __('Invalid credit card number.', BOACOMPRA_DOMAIN),
                    'invalid_cvv'            => __('Invalid CVV.', BOACOMPRA_DOMAIN),
                    'invalid_name'           => __('Invalid name.', BOACOMPRA_DOMAIN),
                    'invalid_expiry'         => __('Invalid expiry date.', BOACOMPRA_DOMAIN),
                    'expired_card'           => __('Expired card.', BOACOMPRA_DOMAIN),
                    'invalid_installment'    => __('Please choose an installment option.', BOACOMPRA_DOMAIN),
                )
            );
        }
    }

    public function process_payment($order_id)
    {
        $this->writeLog('----- INÍCIO DO PAGAMENTO -----', false, 'info');
        global $woocommerce;
        global $wp_version;
        $order = new WC_Order($order_id);
        $erro = false;
        $total = (float) $order->get_total();
        $method = array_key_exists('bc-direct-payment-radio', $_POST) ? sanitize_text_field($_POST['bc-direct-payment-radio']) : '';
        $redirectLink = '';

        if ($method == 'credit-card') {
            $cc_token = (isset($_POST['bc_direct_card_token'])) ? sanitize_text_field($_POST['bc_direct_card_token']) : '';
            $cc_numero = (isset($_POST['bc_direct_card_num'])) ? sanitize_text_field($_POST['bc_direct_card_num']) : '';
            $cc_numero = preg_replace('/\s+/', '', $cc_numero);
            $cc_nome = (isset($_POST['bc_direct_name'])) ? sanitize_text_field($_POST['bc_direct_name']) : '';
            $cc_cvv = (isset($_POST['bc_direct_card_cvv'])) ? sanitize_text_field($_POST['bc_direct_card_cvv']) : '';
            $cc_vencto = (isset($_POST['bc_direct_card_expiry'])) ? sanitize_text_field($_POST['bc_direct_card_expiry']) : '';
            $cc_val_mes = substr($cc_vencto, 0, 2);
            $cc_val_ano = substr($cc_vencto, -4, 4);
            $cc_bandeira = (isset($_POST['bc_direct_card_type'])) ? sanitize_text_field($_POST['bc_direct_card_type']) : '';
            $cc_parcelas = (isset($_POST['bc_direct_installments'])) ? sanitize_text_field($_POST['bc_direct_installments']) : 0;
            $valor_parcela = (isset($_POST['bc_direct_installment_value'])) ? sanitize_text_field($_POST['bc_direct_installment_value']) : 0;
            $total_parcela = (isset($_POST['bc_direct_installment_total'])) ? sanitize_text_field($_POST['bc_direct_installment_total']) : 0;
            $cc_cpf = (isset($_POST['bc_direct_cpf'])) ? sanitize_text_field($_POST['bc_direct_cpf']) : '';
            $cc_cpf = preg_replace('/\D/', '', $cc_cpf);
            $maxparcelas = $this->credit_max_installment;
            $bin = substr($cc_numero, 0, 6);
            $last4 = substr($cc_numero, -4, 4);

            if ($this->cartaoValido($cc_numero) && !empty($cc_nome) && strlen($cc_cvv) > 2 && strlen($cc_numero) > 12 && $this->venctoOK($cc_val_mes, $cc_val_ano)) {
                $erro = false;
            } else {
                $erro = true;
                $additional = '';
                $extra = '';
                if (empty($cc_numero)) {
                    $error_message = __(' Preencha o número do cartão.', BOACOMPRA_DOMAIN);
                    $additional = 'O cliente não digitou o número do cartão';
                    wc_add_notice(__('Pagamento não autorizado:', BOACOMPRA_DOMAIN).$error_message, 'error');
                } elseif (strlen($cc_numero) < 13 || !$this->cartaoValido($cc_numero)) {
                    $error_message = __(' Invalid credit card. Please verify and try again.', BOACOMPRA_DOMAIN);
                    $additional = 'Número do cartão inválido (Luhn) ou menor que o permitido (13 dígitos)';
                    $extra = sprintf("(%d-****-%d)", $bin, $last4);
                    wc_add_notice(__('Pagamento não autorizado:', BOACOMPRA_DOMAIN).$error_message, 'error');
                } elseif (empty($cc_nome)) {
                    $error_message = __(' Preencha o nome do cartão!', BOACOMPRA_DOMAIN);
                    $additional = 'O nome do cartão não foi preenchido';
                    wc_add_notice(__('Pagamento não autorizado:', BOACOMPRA_DOMAIN).$error_message, 'error');
                } elseif (strlen($cc_cvv) < 3) {
                    $error_message = __(' Preencha o CVV do cartão corretamente!', BOACOMPRA_DOMAIN);
                    $additional = 'CVV inválido ou não preenchido';
                    wc_add_notice(__('Pagamento não autorizado:', BOACOMPRA_DOMAIN).$error_message, 'error');
                } elseif (!$this->venctoOK($cc_val_mes, $cc_val_ano)) {
                    $error_message = __(' Cartão expirado! Verifique a data de vencimento informada', BOACOMPRA_DOMAIN);
                    $additional = 'Cartão expirado';
                    $extra = sprintf("(%d/%d)", $cc_val_mes, $cc_val_ano);
                    wc_add_notice(__('Pagamento não autorizado:', BOACOMPRA_DOMAIN).$error_message, 'error');
                } else {
                    $error_message = __(' This card isn\'t accepted. Please choose another one!', BOACOMPRA_DOMAIN);
                    $additional = "Cartão de crédito não aceito ($cc_metodo)";
                    $extra = sprintf("(%d-****-%d)", $bin, $last4);
                    wc_add_notice(__('Pagamento não autorizado:', BOACOMPRA_DOMAIN).$error_message, 'error');
                }
                update_post_meta($order_id, '_transaction_message', "Cartão inválido. Pagamento não enviado ao gateway.");
                $errocompleto = sprintf("Erro: %s - Detalhes do erro: %s %s", $error_message, $additional, $extra);
                $order->add_order_note("Cartão inválido. Pagamento não enviado ao gateway.");
                $order->add_order_note($errocompleto);
                $this->writeLog('Cartão inválido. Pagamento não enviado ao gateway.', false, 'error');
                $this->writeLog($errocompleto, false, 'error');
            }
        } elseif ($method == 'e-wallet') {
            $debit_type = (isset($_POST['bc_direct_debit_type'])) ? sanitize_text_field($_POST['bc_direct_debit_type']) : '';
            $extra = '';
            if (empty($debit_type)) {
                $erro = true;
                $error_message = __(' Selecione o banco.', BOACOMPRA_DOMAIN);
                $additional = 'O cliente não selecionou o banco no checkout';
                wc_add_notice(__('Pagamento não autorizado:', BOACOMPRA_DOMAIN).$error_message, 'error');
                $errocompleto = sprintf("Erro: %s - Detalhes do erro: %s", $error_message, $additional);
                $order->add_order_note($errocompleto);
                update_post_meta($order_id, '_transaction_message', $errocompleto);
            }
        } else {
            //boleto

        }

        if($this->is_direct()) {
            $payload = $this->get_boacompra_payload($order, $method);
            $erro = (!$erro) ? !$this->validate_payload($payload, $order) : $erro;
        }
        if (!$erro && $this->is_direct()) {
            $merchantID = $this->get_merchantid();
            $secretKey = $this->get_secretkey();

            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $this->writeLog($jsonPayload, false, 'info');
            $url = $this->api_path()."/transactions";

            $payloadHash = md5($jsonPayload);
            $hashHmac = hash_hmac('sha256', "/transactions".$payloadHash, $secretKey);
            $authorizationHash = $merchantID.':'.$hashHmac;

            $headers = array(
                'Accept'        => 'application/vnd.boacompra.com.v2+json; charset=UTF-8',
                'Authorization' => $authorizationHash,
                'Content-MD5'   => $payloadHash,
                'Content-type'  => 'application/json',
            );

            $args = array(
                'body'    => $jsonPayload,
                'timeout' => 60,
                'headers' => $headers,
                'cookies' => array(),
            );
            $result = wp_remote_post($url, $args);
            if (is_wp_error($result)) {
                $this->writeLog('Erro WP_Error: '.$result->get_error_message(), false, 'error');
            } else {
                $rawJson = $result['body'];
                $responseCode = $result['response']['code'];

                $this->writeLog('Response Code: '.$responseCode, false, 'info');
                $this->writeLog($rawJson, false, 'info');

                $timeoutCodes = array(408, 504, 524, 598);
                $json = json_decode($rawJson);
                if ($responseCode == 201) {
                    //OK
                    $status = $json->transaction->status;
                    $tid = $json->transaction->code;
                    update_post_meta($order_id, '_bc_method', (string) $method);
                    update_post_meta($order_id, '_bc_status', (string) $status);
                    update_post_meta($order_id, '_bc_tid', (string) $tid);
                    $transactionString = "Pagamento enviado ao BoaCompra: ($status) ".$this->getBoaCompraMessage($status)." - TID: $tid";
                    if ($method == 'credit-card') {
                        $qty = $cc_parcelas;
                        $value = $valor_parcela;
                        $totalInstallment = $total_parcela;
                        $installmentInfo = $qty.'x de R$ '.$value.' = R$ '.$totalInstallment;
                        update_post_meta($order_id, '_bc_card_type', (string) $cc_bandeira);
                        update_post_meta($order_id, '_bc_card_bin', (string) $bin);
                        update_post_meta($order_id, '_bc_card_end', (string) $last4);
                        update_post_meta($order_id, '_bc_card_masked', (string) $bin.'******'.$last4);
                        update_post_meta($order_id, '_bc_card_exp_month', (string) $cc_val_mes);
                        update_post_meta($order_id, '_bc_card_exp_year', (string) $cc_val_ano);
                        update_post_meta($order_id, '_bc_card_name', (string) $cc_nome);
                        update_post_meta($order_id, '_bc_installment', (string) $installmentInfo);
                    } elseif ($method == 'postpay') {
                        //recuperar link
                        $link = $json->transaction->{'payment-url'};
                        $barcode = $json->transaction->{'barcode-number'};
                        $digitableline = $json->transaction->{'digitable-line'};
                        update_post_meta($order_id, '_bc_billet_url', (string) $link);
                        update_post_meta($order_id, '_bc_digitable_line', (string) $digitableline);
                        update_post_meta($order_id, '_bc_barcode_number', (string) $barcode);
                        $transactionString .= " - Link de pagamento: $link";
                    } else {
                        $link = $redirectLink = $json->transaction->{'payment-url'};
                        update_post_meta($order_id, '_bc_billet_url', (string) $link);
                        $transactionString .= " - Link de pagamento: $link";
                    }

                    $order->add_order_note($transactionString);
                    $this->updateOrderStatus($order, $status);
                    $this->writeLog($transactionString, false, 'error');
                    //iniciado (1)
                    //em análise (4)
                    //capturado (8)
                    //cancelado (3)
                    if (in_array($status, $this->reprovedStatuses)) {
                        $error_message = 'Favor entrar em contato com o Banco emissor do seu cartão de crédito';
                        wc_add_notice(__('Pagamento não autorizado: ', BOACOMPRA_DOMAIN).$error_message, 'error');
                        $order->update_status('failed', $error_message);
                        $this->writeLog('Pagamento não autorizado: '.$error_message, false, 'error');
                    }
                } elseif ($responseCode == 401) {
                    //erro autenticação
                    $erro = true;
                    wc_add_notice(__('Erro no pagamento, entre em contato com o lojista.', 'error'));
                    $error_message = "($responseCode) Erro de autenticação com o BoaCompra, confira suas credenciais.";
                    $additional = 'Ambiente selecionado: '.strtoupper($this->boacompra_environment);
                    $errocompleto = sprintf("Erro: %s - Detalhes do erro: %s", $error_message, $additional);
                    $order->add_order_note($errocompleto);
                    $this->writeLog($errocompleto, false, 'error');
                } elseif (in_array($responseCode, $timeoutCodes)) {
                    $status = 1;
                    $transactionString = __('Ocorreu um timeout na transação. Aguardar retorno BoaCompra.', BOACOMPRA_DOMAIN);

                    $order->add_order_note($transactionString);
                    $order->add_order_note($rawJson);
                    $this->updateOrderStatus($order, $status);
                    $this->writeLog($transactionString, false, 'error');
                } else {
                    //erro nos dados
                    $erro = true;
                    $errorCode = $xml->errors->code;
                    $errorMessage = $xml->errors->message;
                    if (!empty($errorCode)) {
                        $errorString = __('Pagamento não autorizado: ', BOACOMPRA_DOMAIN)."($errorCode) ".$errorMessage;
                        wc_add_notice($errorString, 'error');
                    } else {
                        $errorString = "Ocorreu um erro na comunicação com o BoaCompra: ($responseCode) $rawJson";
                    }
                    $order->add_order_note($errorString);
                    $this->writeLog($errorString, false, 'error');
                }
            }
        } elseif (!$erro) {
            //hosted checkout
            $order->set_total($total);
            $woocommerce->cart->empty_cart();

            $payload = $this->get_boacompra_payload_redirect($order);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $this->writeLog($jsonPayload, false, 'info');

            return array(
                'result'   => 'success',
                'redirect' => get_site_url().'/wc-api/wc_boacompra_hosted_request?oid='.$order_id,
            );
        }
        if (!$erro) {
            $order->set_total($total);
            $woocommerce->cart->empty_cart();
            return array(
                'result'   => 'success',
                'redirect' => !empty($redirectLink) ? $redirectLink : $this->get_return_url($order),
            );
        } else {
            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }
        $this->writeLog('----- FIM DO PAGAMENTO -----', false, 'info');
    }

    public function validate_payload($payload, $order)
    {
        $notAuthorised = __('Pagamento não autorizado:', BOACOMPRA_DOMAIN);

        //billing
        if (empty($payload['payer']['address']['street'])) {
            $error_message = __(' Preencha o Endereço de cobrança.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen($payload['payer']['address']['street']) > 160) {
            $error_message = __(' Tamanho máximo do endereço de cobrança excedido, limite: 160 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (strlen($payload['payer']['address']['number']) < 1) {
            $error_message = __(' Preencha o Número do endereço de cobrança.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen($payload['payer']['address']['number']) > 20) {
            $error_message = __(' Tamanho máximo do número do endereço de cobrança excedido, limite: 20 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (empty($payload['payer']['address']['district'])) {
            $error_message = __(' Preencha o Bairro do endereço de cobrança.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen($payload['payer']['address']['district']) > 60) {
            $error_message = __(' Tamanho máximo do bairro do endereço de cobrança excedido, limite: 60 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (strlen($payload['payer']['address']['complement']) > 40) {
            $error_message = __(' Tamanho máximo do complemento do endereço de cobrança excedido, limite: 60 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (empty($payload['payer']['address']['zip-code'])) {
            $error_message = __(' Preencha o CEP do endereço de cobrança.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen(preg_replace('/\D/', '', $payload['payer']['address']['zip-code'])) != 8) {
            $error_message = __(' CEP do endereço de cobrança inválido.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (empty($payload['payer']['address']['city'])) {
            $error_message = __(' Preencha a Cidade do endereço de cobrança.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen($payload['payer']['address']['city']) > 60) {
            $error_message = __(' Tamanho máximo da cidade do endereço de cobrança excedido, limite: 60 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (empty($payload['payer']['address']['state'])) {
            $error_message = __(' Preencha o Estado do endereço de cobrança.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen($payload['payer']['address']['state']) != 2) {
            $error_message = __(' Estado do endereço de cobrança inválido, limite: 2 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        //shipping
        if (array_key_exists('shipping', $payload)) {
            if (empty($payload['shipping']['address']['street'])) {
                $error_message = __(' Preencha o Endereço de entrega.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            } elseif (strlen($payload['shipping']['address']['street']) > 160) {
                $error_message = __(' Tamanho máximo do endereço de entrega excedido, limite: 160 caracteres.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }

            if (strlen($payload['shipping']['address']['number']) < 1) {
                $error_message = __(' Preencha o Número do endereço de entrega.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            } elseif (strlen($payload['shipping']['address']['number']) > 20) {
                $error_message = __(' Tamanho máximo do número do endereço de entrega excedido, limite: 20 caracteres.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }

            if (empty($payload['shipping']['address']['district'])) {
                $error_message = __(' Preencha o Bairro do endereço de entrega.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            } elseif (strlen($payload['shipping']['address']['district']) > 60) {
                $error_message = __(' Tamanho máximo do bairro do endereço de entrega excedido, limite: 60 caracteres.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }

            if (strlen($payload['shipping']['address']['complement']) > 40) {
                $error_message = __(' Tamanho máximo do complemento do endereço de entrega excedido, limite: 60 caracteres.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }

            if (empty($payload['shipping']['address']['zip-code'])) {
                $error_message = __(' Preencha o CEP do endereço de entrega.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            } elseif (strlen(preg_replace('/\D/', '', $payload['shipping']['address']['zip-code'])) != 8) {
                $error_message = __(' CEP do endereço de entrega inválido.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }

            if (empty($payload['shipping']['address']['city'])) {
                $error_message = __(' Preencha a Cidade do endereço de entrega.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            } elseif (strlen($payload['shipping']['address']['city']) > 60) {
                $error_message = __(' Tamanho máximo da cidade do endereço de entrega excedido, limite: 60 caracteres.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }

            if (empty($payload['shipping']['address']['state'])) {
                $error_message = __(' Preencha o Estado do endereço de entrega.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            } elseif (strlen($payload['shipping']['address']['state']) != 2) {
                $error_message = __(' Estado do endereço de entrega inválido, limite: 2 caracteres.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }
        }

        //payment
        if (empty($payload['payer']['name'])) {
            $error_message = __(' Preencha o Nome do comprador.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen($payload['payer']['name']) > 255) {
            $error_message = __(' Tamanho máximo do nome do comprador excedido, limite: 255 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (!$this->validaCpf($payload['payer']['document']['number'])) {
            $error_message = __(' CPF do comprador inválido.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        $phone = $payload['payer']['phone-number'];
        if (!preg_match('/^\+55((1[1-9])|([2-9][0-9]))(([0-9]{4}[0-9]{4})|(9[0-9]{3}[0-9]{5}))$/', $phone)) {
            $error_message = __(' Telefone do comprador inválido.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if (empty($payload['payer']['email'])) {
            $error_message = __(' Preencha o Email do comprador.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (!filter_var($payload['payer']['email'], FILTER_VALIDATE_EMAIL)) {
            $error_message = __(' Email do comprador inválido.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        } elseif (strlen($payload['payer']['email']) > 60) {
            $error_message = __(' Tamanho máximo do email do comprador excedido, limite: 60 caracteres.', BOACOMPRA_DOMAIN);
            wc_add_notice($notAuthorised.$error_message, 'error');
        }

        if ($payload['charge'][0]['payment-method']['type'] == 'credit-card') {
            if (empty($payload['charge'][0]['payment-info']['token']) && empty($payload['charge'][0]['payment-info']['code'])) {
                $error_message = __(' Dados do cartão de crédito inválidos.', BOACOMPRA_DOMAIN);
                wc_add_notice($notAuthorised.$error_message, 'error');
            }
        }

        if (!empty($error_message)) {
            $errocompleto = sprintf("Erro: %s", $error_message);
            $order->add_order_note($errocompleto);
            return false;
        }
        return true;
    }

    public function get_boacompra_payload($order, $method)
    {
        $payload = array();
        $order_id = $order->get_id();
        $items = $this->getDescricaoPedido($order);
        $args = $this->getOrderData($order);
        $documento = !empty($args['documento']) ? $args['documento'] : '';
        $documento = preg_replace('/[\D]/', '', $documento);
        $total = (float) $order->get_total();

        $transaction = array();
        $transaction['reference'] = "$order_id";
        $transaction['country'] = 'BR';
        $transaction['currency'] = 'BRL';
        $transaction['checkout-type'] = 'direct';
        $transaction['notification-url'] = home_url('/wc-api/wc_gateway_boacompra_ipn');
        $transaction['language'] = 'pt-BR';

        $charge = array();
        $charge['amount'] = (float) number_format($total, 2, '.', '');
        $charge['payment-method']['type'] = $method;
        if ($method == 'credit-card') {
            $charge['payment-method']['sub-type'] = (isset($_POST['bc_direct_card_type'])) ? sanitize_text_field($_POST['bc_direct_card_type']) : '';
            $charge['payment-info']['installments'] = (int) ((isset($_POST['bc_direct_installments'])) ? sanitize_text_field($_POST['bc_direct_installments']) : 1);
            $charge['payment-info']['token'] = (isset($_POST['bc_direct_card_token'])) ? sanitize_text_field($_POST['bc_direct_card_token']) : '';
        } elseif ($method == 'e-wallet') {
            $charge['payment-method']['sub-type'] = (isset($_POST['bc_direct_debit_type'])) ? sanitize_text_field($_POST['bc_direct_debit_type']) : '';
            $transaction['redirect-urls']['success'] = $this->get_return_url($order);
            $transaction['redirect-urls']['fail'] = $this->get_return_url($order);
        } else {
            $charge['payment-method']['sub-type'] = 'boleto';
        }

        $billingAddress['street'] = $args['billingAddress'];
        $billingAddress['number'] = preg_replace('/\D/', '', $args['billingNumber']);
        $billingAddress['complement'] = $args['billingAddress2'];
        $billingAddress['district'] = $args['billingNeighborhood'];
        $billingAddress['zip-code'] = preg_replace('/\D/', '', $args['billingPostcode']);
        $billingAddress['city'] = $args['billingCity'];
        $billingAddress['state'] = $args['billingState'];
        $billingAddress['country'] = 'BR';

        if (!empty($args['shippingPostcode'])) {
            $shippingAddress['street'] = $args['shippingAddress'];
            $shippingAddress['number'] = preg_replace('/\D/', '', $args['shippingNumber']);
            $shippingAddress['complement'] = $args['shippingAddress2'];
            $shippingAddress['district'] = $args['shippingNeighborhood'];
            $shippingAddress['zip-code'] = preg_replace('/\D/', '', $args['shippingPostcode']);
            $shippingAddress['city'] = $args['shippingCity'];
            $shippingAddress['state'] = $args['shippingState'];
            $shippingAddress['country'] = 'BR';
        } else {
            $shippingAddress = $billingAddress;
        }
        $shipping['address'] = $shippingAddress;
        $shipping['cost'] = (float) ($order->get_total_shipping() > 0 ? number_format($order->get_total_shipping(), 2, '.', '') : 0);

        $payer = array();
        $payer['name'] = trim((!empty($_POST['bc_direct_name'])) ? sanitize_text_field($_POST['bc_direct_name']) : $args['billingName']);
        $payer['email'] = $args['billingEmail'];
        $payer['birth-date'] = preg_replace('/^([\d]{2})\/([\d]{2})\/([\d]{4})$/', '$3-$2-$1', $args['billingBirthdate']);
        $payer['phone-number'] = '+55'.$args['billingPhone'];
        $payer['document']['type'] = 'cpf';
        $payer['document']['number'] = $documento;
        $payer['ip'] = $this->getClientIp(true);
        $payer['address'] = $billingAddress;

        $cart = array();
        foreach ($items as $key => $item) {
            $cartItem = array();
            $cartItem['description'] = $item['descr'];
            $cartItem['type'] = $item['virtual'] ? 'digital' : 'physical';
            $cartItem['quantity'] = (int) $item['quant'];
            $cartItem['unit-price'] = (float) $item['valor'];
            $cart[] = $cartItem;
        }

        $payload['transaction'] = $transaction;
        $payload['charge'][] = $charge;
        $payload['payer'] = $payer;
        $payload['shipping'] = $shipping;
        $payload['cart'] = $cart;

        return $payload;
    }

    public function get_boacompra_payload_redirect($order)
    {
        $items = $this->getDescricaoPedido($order);
        $descricao = '';
        $args = $this->getOrderData($order);
        $documento = !empty($args['documento']) ? $args['documento'] : '';
        $documento = preg_replace('/[\D]/', '', $documento);
        $total = (float) $order->get_total();

        foreach ($items as $key => $item) {
            $descricao .= $item['descr'] . ', ';
        }
        $descricao = rtrim($descricao, ', ');

        $store_raw_country = get_option('woocommerce_default_country');
        $split_country = explode(":", $store_raw_country);
        $store_country = $split_country[0];

        $groups = '';
        if ($this->hosted_groups) {
            $groups = implode(',', $this->hosted_groups);
        }

        $store_id = $this->get_merchantid();
        $secret = $this->get_secretkey();
        $return = $this->get_return_url($order);
        $notify_url = home_url('/wc-api/wc_gateway_boacompra_ipn');
        $currency_code = get_woocommerce_currency();
        $order_id = $order->get_id();
        $order_description = $descricao;
        $amount = (float) number_format($total, 2, '', '');
        $client_email = $args['billingEmail'];
        $client_name = $args['billingName'];
        $client_zip_code = preg_replace('/\D/', '', $args['billingPostcode']);
        $client_street = $args['billingAddress'];
        $client_suburb = $args['billingNeighborhood'];
        $client_number = preg_replace('/\D/', '', $args['billingNumber']);
        $client_city = $args['billingCity'];
        $client_state = $args['billingState'];
        $client_country = $args['billingCountry'];
        $client_telephone = '+55'.$args['billingPhone'];
        $client_cpf = $documento;
        //$language = "pt_BR";
        $country_payment = $store_country;
        $payment_group = $groups;
        $test_mode = $this->is_sandbox() ? '1' : '0';
        $mobile = wp_is_mobile() ? '1' : '0';

        $data = $store_id.$notify_url.$order_id.$amount.$currency_code;
        $hash_key = hash_hmac('sha256', $data, $secret);

        $parametros = array('store_id', 'return', 'notify_url', 'currency_code', 'order_id', 'order_description', 'amount', 'hash_key', 'client_email', 'client_name', 'client_zip_code', 'client_street', 'client_suburb', 'client_number', 'client_city', 'client_state', 'client_country', 'client_telephone', 'client_cpf', 'language', 'country_payment', 'test_mode', 'mobile', 'payment_group');

        $payload = compact($parametros);
        return $payload;
    }

    public static function request_hosted()
    {
        $order_id = (int) sanitize_text_field($_REQUEST['oid']);
        $order = new WC_Order($order_id);
        $method = is_callable(array($order, 'get_payment_method')) ? $order->get_payment_method() : $order->payment_method;
        $instance = new self();
        if ($method == $instance->id) {
            $payload = $instance->get_boacompra_payload_redirect($order);
            $html = '<form method="POST" name="hostedForm" action="https://billing.boacompra.com/payment.php" >';
            foreach ($payload as $key => $value) {
                $html .= '<input type="hidden" name="'.$key.'" id="'.$key.'" value="'.$value.'">';
            }
            $html .= '</form>';
            $html .= '<script language=Javascript>';
            $html .= 'document.hostedForm.submit();';
            $html .= '</script>';
            echo $html;
        }
        die();
    }

    public static function boacompra_installments()
    {
        $instance = new self();

        $merchantID = $instance->get_merchantid();
        $secretKey = $instance->get_secretkey();

        $bin = sanitize_text_field($_POST['bin']);
        $amount = sanitize_text_field($_POST['amount']);

        $amount = number_format($amount, 2, '.', '');

        $country = 'BR';
        $currency = 'BRL';

        $payload = compact('bin', 'country', 'amount', 'currency');
        $url = 'https://api.boacompra.com';
        $uri = '/card?';

        //var_dump($payload);
        foreach ($payload as $key => $value) {
            $uri .= "$key=$value&";
        }
        $uri = rtrim($uri, '&');
        $hashHmac = hash_hmac('sha256', $uri, $secretKey);
        $authorizationHash = $merchantID.':'.$hashHmac;

        $headers = array(
            'Accept'        => 'application/vnd.boacompra.com.v1+json; charset=UTF-8',
            'Authorization' => $authorizationHash,
            'Content-type'  => 'application/json',
        );

        $args = array('headers' => $headers);
        $response = wp_remote_get($url.$uri, $args);
        $json = $response['body'];
        $decode = json_decode($json, true);

        header('Content-Type: application/json');
        if (!$decode || array_key_exists('errors', $decode)) {
            header("HTTP/1.1 400");
        } else {
            echo $json;
        }
        die();
    }

    public function getOrderData($order)
    {
        $args = array();
        // WooCommerce 3.0 or later.
        if (method_exists($order, 'get_meta')) {
            $args['billingAddress'] = $order->get_billing_address_1();
            $args['billingNumber'] = $order->get_meta('_billing_number');
            if (empty($args['billingNumber'])) {
                $args['billingNumber'] = preg_replace('/\D/', '', $args['billingAddress']);
            }
            $cpf = $order->get_meta('_billing_cpf');
            $cnpj = $order->get_meta('_billing_cnpj');
            $documento = empty($cpf) ? $cnpj : $cpf;
            $args['userId'] = $order->get_user_id();
            $args['documento'] = $documento;
            $args['billingName'] = $order->get_billing_first_name().' '.$order->get_billing_last_name();
            $args['billingEmail'] = $order->get_billing_email();
            $args['billingPhone'] = $order->get_billing_phone();
            $args['billingCellphone'] = $order->get_meta('_billing_cellphone');
            $args['billingNeighborhood'] = $order->get_meta('_billing_neighborhood');
            $args['billingAddress2'] = $order->get_billing_address_2();
            $args['billingCity'] = $order->get_billing_city();
            $args['billingState'] = $order->get_billing_state();
            $args['billingCountry'] = $order->get_billing_country();
            $args['billingPostcode'] = $order->get_billing_postcode();
            $args['billingBirthdate'] = $order->get_meta('_billing_birthdate');
            $args['billingSex'] = $order->get_meta('_billing_sex');

            // Shipping fields.
            $args['shippingAddress'] = $order->get_shipping_address_1();
            $args['shippingNumber'] = $order->get_meta('_shipping_number');
            if (empty($args['shippingNumber'])) {
                $args['shippingNumber'] = preg_replace('/\D/', '', $args['shippingAddress']);
            }
            $args['shippingNeighborhood'] = $order->get_meta('_shipping_neighborhood');
            $args['shippingAddress2'] = $order->get_shipping_address_2();
            $args['shippingPostcode'] = $order->get_shipping_postcode();
            $args['shippingCity'] = $order->get_shipping_city();
            $args['shippingState'] = $order->get_shipping_state();
            $args['shippingCountry'] = $order->get_shipping_country();
        } else {
            $args['billingAddress'] = $order->billing_address_1;
            if (!empty($order->billing_number)) {
                $args['billingNumber'] = $order->billing_number;
            } else {
                $args['billingNumber'] = preg_replace('/\D/', '', $order->billing_address_1);
            }
            $cpf = $order->billing_cpf;
            $cnpj = $order->billing_cnpj;
            $documento = empty($cpf) ? $cnpj : $cpf;
            $args['userId'] = $order->user_id;
            $args['documento'] = $documento;
            $args['billingName'] = $order->billing_first_name.' '.$order->billing_last_name;
            $args['billingEmail'] = $order->billing_email;
            $args['billingPhone'] = $order->billing_phone;
            $args['billingCellphone'] = $order->billing_cellphone;
            $args['billingNeighborhood'] = $order->billing_neighborhood;
            $args['billingAddress2'] = $order->billing_address_2;
            $args['billingCity'] = $order->billing_city;
            $args['billingState'] = $order->billing_state;
            $args['billingCountry'] = $order->billing_country;
            $args['billingPostcode'] = $order->billing_postcode;
            $args['billingBirthdate'] = $order->billing_birthdate;
            $args['billingSex'] = $order->billing_sex;

            $args['shippingAddress'] = $order->shipping_address_1;
            if (!empty($order->shipping_number)) {
                $args['shippingNumber'] = $order->shipping_number;
            } else {
                $args['shippingNumber'] = preg_replace('/\D/', '', $order->shipping_address_1);
            }
            $args['shippingNeighborhood'] = $order->shipping_neighborhood;
            $args['shippingAddress2'] = $order->shipping_address_2;
            $args['shippingPostcode'] = $order->shipping_postcode;
            $args['shippingCity'] = $order->shipping_city;
            $args['shippingState'] = $order->shipping_state;
            $args['shippingCountry'] = $order->shipping_country;
        }
        $args['billingNumber'] = preg_replace('/[\D]/', '', $args['billingNumber']);
        $args['billingPhone'] = preg_replace('/[\D]/', '', $args['billingPhone']);
        if (strlen($args['billingNumber']) > 5) {
            $args['billingNumber'] = substr($args['billingNumber'], 0, 5);
        }
        if (empty($args['billingNumber'])) {
            $args['billingNumber'] = '00';
        }
        return $args;
    }

    public static function ipn_boacompra()
    {
        @ob_clean();
        $self = new WC_Boacompra_Payment_Gateway();

        $self->writeLog(wc_print_r($_POST, true), false, 'info');
        $ipn = $self->process_ipn_request($_POST, false);

        if ($ipn) {
            header('HTTP/1.1 200 OK');
            exit();
        } else {
            wp_die(esc_html__('BoaCompra Request Unauthorized', BOACOMPRA_DOMAIN), esc_html__('BoaCompra Request Unauthorized', 'woocommerce-boacompra'), array('response' => 401));
        }
    }

    public static function ipn_boacompra_refund()
    {
        @ob_clean();
        $self = new WC_Boacompra_Payment_Gateway();

        $json = file_get_contents('php://input');
        $self->writeLog(wc_print_r($json, true), false, 'info');
        $payload = json_decode($json, true);
        if(array_key_exists('transaction-id', $payload)) {
            $payload['transaction-code'] = $payload['transaction-id'];
        }
        $ipn = $self->process_ipn_request($payload, true);

        if ($ipn) {
            header('HTTP/1.1 200 OK');
            exit();
        } else {
            wp_die(esc_html__('BoaCompra Request Unauthorized', BOACOMPRA_DOMAIN), esc_html__('BoaCompra Request Unauthorized', 'woocommerce-boacompra'), array('response' => 401));
        }
    }

    public function process_ipn_request($data, $refund = false)
    {
        $this->writeLog('----- CHECANDO IPN -----', false, 'info');

        if (!array_key_exists('transaction-code', $data) && !array_key_exists('notification-type', $data)) {
            $this->writeLog('IPN INVÁLIDO', false, 'error');
            $this->writeLog(wc_print_r($data, true), false, 'error');
            return false;
        }

        // Checks the notification-type.
        $acceptableNotifications = array('transaction', 'refund');
        if (!in_array($data['notification-type'], $acceptableNotifications)) {
            $this->writeLog('IPN INVÁLIDO, notification-type não é transaction nem refund', false, 'error');
            $this->writeLog(wc_print_r($data, true), false, 'error');

            return false;
        }

        return $this->consultTransaction($data['transaction-code'], $refund);
    }

    public function update_order_status($payload, $type = '')
    {
        if (isset($payload['order-id'])) {
            $id = (string) $payload['order-id'];
            $status = $payload['status'];
            $can_refund = $payload['refundable'];
            $order = wc_get_order($id);

            // Check if order exists.
            if (!$order) {
                return;
            }

            $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

            // Checks whether the invoice number matches the order.
            // If true processes the payment.
            if ($order_id == $id) {
                $this->writeLog('Atualizando status do pedido '.$order->get_order_number().', novo status: '.$status, false, 'info');
                $this->updateOrderStatus($order, $status, $type);
                update_post_meta($order_id, '_bc_status', (string) $status);
                update_post_meta($order_id, '_bc_can_refund', (int) $can_refund);

                $tid = get_post_meta($order_id, '_bc_tid', true);
                $method = get_post_meta($order_id, '_bc_method', true);
                if (empty($tid) && !empty($payload['transaction-code'])) {
                    delete_post_meta($order_id, '_bc_tid');
                    update_post_meta($order_id, '_bc_tid', (string) $payload['transaction-code']);
                }
                if (empty($method) && !empty($payload['payment-name'])) {
                    delete_post_meta($order_id, '_bc_method');
                    update_post_meta($order_id, '_bc_method', (string) $payload['payment-name']);
                }
            } else {
                $this->writeLog('ReferenceID BoaCompra ('.$id.') diferente do número do pedido ('.$order->get_order_number().')', false, 'error');
            }
        }
    }

    public function getDescricaoPedido($order)
    {
        $cartItems = $order->get_items();
        $itemsData = array();
        $json = array();
        $i = 0;
        foreach ($cartItems as $item) {
            $itemsData["quant"] = $item['qty'];
            $itemsData["descr"] = preg_replace('/[<>\-&%\/]/', '', $item['name']);
            $itemsData["valor"] = number_format($item['line_subtotal'] / $item['qty'], 2, '.', '');
            $itemsData["id"] = $item['product_id'];
            $itemsData["virtual"] = (get_post_meta($item['product_id'], '_virtual', true) == 'yes');

            $json[++$i] = $itemsData;
        }
        return $json;
    }

    /**
     * Can the order be refunded via BoaCompra?
     *
     * @param  WC_Order $order Order object.
     * @return bool
     */
    public function can_refund_order($order)
    {
        $order_id = $order->get_id();
        $tid = get_post_meta($order_id, '_bc_tid', true);
        $status = get_post_meta($order_id, '_bc_status', true);
        $can_refund = get_post_meta($order_id, '_bc_can_refund', true);

        $can_refund_statuses = array('COMPLETE', 'REFUNDED');

        return $order && !empty($tid) && in_array($status, $can_refund_statuses) && $can_refund == true;
    }

    /**
     * Process a refund if supported.
     *
     * @param  int    $order_id Order ID.
     * @param  float  $amount Refund amount.
     * @param  string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $this->writeLog('----- SOLICITAÇÃO REFUND -----', false, 'info');
        $this->writeLog('OrderID: '.$order_id, false, 'info');
        $this->writeLog('Amount: '.$amount, false, 'info');
        $order = wc_get_order($order_id);
        $order_id = $order->get_id();
        $transid = get_post_meta($order_id, '_bc_tid', true);
        $status = get_post_meta($order_id, '_bc_status', true);
        $can_refund_statuses = array('COMPLETE', 'REFUNDED');
        $params = array(
            'transaction-id' => $transid,
            'notify-url'     => home_url('/wc-api/wc_gateway_boacompra_ipn_refund'),
            'test-mode'      => $this->is_sandbox() ? 1 : 0
        );

        if (is_null($amount)) {
            $amount = $order->get_total();
        }
        if ($amount < $order->get_total()) {
            //não enviar amount caso seja igual ao total
            $params['amount'] = (float) number_format($amount, 2, '.', '');
        }

        //orderdate + 90 > today
        $orderDate = $order->order_date;
        $newDate = date('Y-m-d', strtotime($orderDate.' +90 days'));
        if ($newDate < date('Y-m-d')) {
            $message = __('O prazo máximo para solicitar um reembolso é de até 90 dias após a data de compra.', 'woocommerce');
            $this->writeLog($message, false, 'error');
            return new WP_Error('error', $message);
        }

        //empty transactionCode
        if (empty($transid)) {
            $message = __('Transação não possui transaction-code. Por favor, consulte a transação através do botão "Consulta" e tente novamente.', 'woocommerce');
            $this->writeLog($message, false, 'error');
            return new WP_Error('error', $message);
        }


        if (in_array($status, $can_refund_statuses)) {
            $merchantID = $this->get_merchantid();
            $secretKey = $this->get_secretkey();
            $jsonPayload = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $this->writeLog($jsonPayload, false, 'info');
            $url = $this->api_path()."/refunds";
            $payloadHash = md5($jsonPayload);
            $hashHmac = hash_hmac('sha256', "/refunds".$payloadHash, $secretKey);
            $authorizationHash = $merchantID.':'.$hashHmac;

            $headers = array(
                'Accept'        => 'application/vnd.boacompra.com.v2+json; charset=UTF-8',
                'Authorization' => $authorizationHash,
                'Content-MD5'   => $payloadHash,
                'Content-type'  => 'application/json',
            );

            $args = array(
                'body'    => $jsonPayload,
                'timeout' => 60,
                'headers' => $headers,
                'cookies' => array(),
            );
            $result = wp_remote_post($url, $args);
            if (is_wp_error($result)) {
                $this->writeLog('Erro WP_Error: '.$result->get_error_message(), false, 'error');
            } else {
                $rawJson = $result['body'];
                $responseCode = $result['response']['code'];

                $this->writeLog('Response Code: '.$responseCode, false, 'info');
                $this->writeLog($rawJson, false, 'info');

                if ($responseCode == 201) {
                    $order->add_order_note('Identificação Reembolso: '.$rawJson);
                    return true;
                } else {
                    $json = json_decode($rawJson);
                    if(is_array($json->errors)) {
                        $error = array_shift($json->errors);
                        $code = $error->code;
                        $message = $error->description;
                    }
                    else {
                        $code = $json->errors->code;
                        $message = $json->errors->description;
                    }
                    $error = sprintf(__('Ocorreu um erro ao efetuar o reembolso. (%1$s) %2$s', 'woocommerce'), $code, $message);
                    $order->add_order_note('Resposta do Reembolso: '.$error);
                    $order->add_order_note('Detalhes: '.$rawJson);

                    $this->writeLog($error, false, 'error');
                    return new WP_Error('error', $error);
                }
            }
        } else {
            $message = sprintf(__('Status (%1$s) não permite refund.', 'woocommerce'), $status);
            $this->writeLog($message, false, 'error');
            return new WP_Error('error', $message);
        }
    }

    public static function consultTransaction($tid = '', $refundConsult = false)
    {
        if(!empty($tid)) {
            $type = 'IPN';
            $order_id = '';
            $transid = $tid;
        }
        else {
            $type = 'CONSULTA';
            $order_id = sanitize_text_field($_REQUEST['order']);
            $transid = sanitize_text_field($_REQUEST['transid']);
        }

        $instance = new WC_Boacompra_Payment_Gateway();
        $instance->writeLog('----- '.$type.' -----', false, 'info');
        if(!empty($order_id)) {
            $order = new WC_Order($order_id);
            $instance->writeLog('OrderID: '.$order_id, false, 'info');
        }
        $instance->writeLog('TransactionCode: '.$transid, false, 'info');

        if (!empty($transid)) {
            $merchantID = $instance->get_merchantid();
            $secretKey = $instance->get_secretkey();

            $url = $instance->api_path();
            $uri = '/transactions/'.$transid;

            $hashHmac = hash_hmac('sha256', $uri, $secretKey);
            $authorizationHash = $merchantID.':'.$hashHmac;

            $headers = array(
                'Accept'        => 'application/vnd.boacompra.com.v1+json; charset=UTF-8',
                'Authorization' => $authorizationHash,
                'Content-type'  => 'application/json',
            );

            $args = array('headers' => $headers);
            $result = wp_remote_get($url.$uri, $args);
        } else {
            $result = array();
        }

        // Check to see if the request was valid.
        if (is_wp_error($result)) {
            if ('yes' == $instance->debug) {
                $instance->writeLog('Erro WP_Error: '.$result->get_error_message(), false, 'error');
            }
        } else {
            try {
                $rawJson = $result['body'];
                $instance->writeLog($rawJson, false, 'info');
                $json = json_decode($rawJson, true);
                $instance->writeLog(print_r($json, true), false, 'info');
            } catch (Exception $e) {
                $json = '';
            }
        }

        if (!empty($json)) {
            $instance->update_order_status($json['transaction-result']['transactions'][0], $type);
        }
        $status = (string) $json['transaction-result']['transactions'][0]['status'];
        $mensagem = (string) $instance->getBoaCompraMessage($status);
        $retorno = array('status' => $status, 'mensagem' => $mensagem);

        if($refundConsult == true) {
            $order = new WC_Order($json['transaction-result']['transactions'][0]['order-id']);
            $refunds = $json['transaction-result']['transactions'][0]['refunds'];
            $lastRefund = array_pop($refunds);
            $order->add_order_note('Response refund: '.json_encode($lastRefund, JSON_PRETTY_PRINT));
        }

        $instance->writeLog(wc_print_r($retorno, true), false, 'info');
        $instance->writeLog('----- FIM '.$type.' -----', false, 'info');
        if($type == 'IPN') {
            return $retorno;
        }
        else {
            echo json_encode($retorno);
            wp_die();
        }
    }

    public function writeLog($msg, $force = false, $level = 'info')
    {
        // debug
        // info
        // notice
        // warning
        // error
        // critical
        // alert
        // emergency
        if ('yes' == $this->debug || $force) {
            $this->log->$level($msg, $this->context);
        }
    }

    public function link_log()
    {
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.2', '>=')) {
            return '<a href="'.esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file='.esc_attr($this->id).'-'.sanitize_file_name(wp_hash($this->id)).'.log')).'">'.__('Logs', BOACOMPRA_DOMAIN).'</a>';
        }

        return '<code>woocommerce/logs/'.esc_attr($this->id).'-'.sanitize_file_name(wp_hash($this->id)).'.txt</code>';
    }

    public function venctoOK($mes, $ano)
    {
        $vencto = DateTime::createFromFormat('Ym', $ano.$mes);
        $now = new DateTime();
        return ($vencto >= $now);
    }

    public function cartaoValido($number)
    {
        settype($number, 'string');
        $sumTable = array(
            array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9),
            array(0, 2, 4, 6, 8, 1, 3, 5, 7, 9));
        $sum = 0;
        $flip = 0;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += $sumTable[$flip++ & 0x1][$number[$i]];
        }
        return $sum % 10 === 0;
    }

    public function retiracento($texto)
    {
        $trocarIsso = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú', 'Ÿ');
        $porIsso = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'Y');
        $titletext = str_replace($trocarIsso, $porIsso, $texto);
        return $titletext;
    }

    public function validaCpf($cpf)
    {
        // Extrai somente os números
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }
        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf{$c} * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf{$c} != $d) {
                return false;
            }
        }

        return true;
    }
}
