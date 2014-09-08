<?php
    if (!class_exists('Conekta')) {
        require_once("lib/conekta-php/lib/Conekta.php");
    }
    /*
     * Title   : Conekta Payment extension for WooCommerce
     * Author  : Cristina Randall
     * Url     : https://github.com/cristinarandall/conekta-woocommerce
     */
    
    class WC_Conekta_Cash_Gateway extends WC_Payment_Gateway
    {
        protected $GATEWAY_NAME               = "WC_Conekta_Cash_Gateway";
        protected $usesandboxapi              = true;
        protected $order                      = null;
        protected $transactionId              = null;
        protected $transactionErrorMessage    = null;
        protected $conektaTestApiKey           = '';
        protected $conektaLiveApiKey           = '';
        protected $publishable_key            = '';
        
        public function __construct()
        {
            $this->id              = 'ConektaCash';
            $this->has_fields      = true;            
            $this->init_form_fields();
            $this->init_settings();
            $this->title              = $this->settings['title'];
            $this->description        = '';
            $this->icon 		      = $this->settings['alternate_imageurl'] ? $this->settings['alternate_imageurl']  : WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/cash.png';
            $this->usesandboxapi      = strcmp($this->settings['debug'], 'yes') == 0;
            $this->testApiKey 		  = $this->settings['test_api_key'  ];
            $this->liveApiKey 		  = $this->settings['live_api_key'  ];
            $this->secret_key         = $this->usesandboxapi ? $this->testApiKey : $this->liveApiKey;
            add_action('woocommerce_update_options_payment_gateways_' . $this->id , array($this, 'process_admin_options'));
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_barcode' ) );
            add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'webhook_handler' ) );  
        }

        /**
        * Updates the status of the order.
        * Webhook needs to be added to Conekta account tusitio.com/wc-api/WC_Conekta_Cash_Gateway
        */
	public function webhook_handler() 
	{
	    header('HTTP/1.1 200 OK');
	    $body = @file_get_contents('php://input');		
	    $event = json_decode($body);
	    $charge = $event->data->object;
	    $order_id = $charge->reference_id;
	    $paid_at = date("Y-m-d", $charge->paid_at);
	    $order = new WC_Order( $order_id );
		if (strpos($event->type, "charge.paid") !== false) 
		{
			update_post_meta( $order->id, 'conekta-paid-at', $paid_at);
			$order->payment_complete();
			$order->add_order_note(sprintf("Payment completed in Oxxo and notification of payment received"));
		}
	}
   
        public function init_form_fields()
        {
            $this->form_fields = array(
                                       'enabled' => array(
                                                          'type'        => 'checkbox',
                                                          'title'       => __('Enable/Disable', 'woothemes'),
                                                          'label'       => __('Enable Conekta Cash Payment', 'woothemes'),
                                                          'default'     => 'yes'
                                                          ),
                                       'debug' => array(
                                                        'type'        => 'checkbox',
                                                        'title'       => __('Testing', 'woothemes'),
                                                        'label'       => __('Turn on testing', 'woothemes'),
                                                        'default'     => 'no'
                                                        ),
                                       'title' => array(
                                                        'type'        => 'text',
                                                        'title'       => __('Title', 'woothemes'),
                                                        'description' => __('This controls the title which the user sees during checkout.', 'woothemes'),
                                                        'default'     => __('Cash Payment', 'woothemes')
                                                        ),
                                       'test_api_key' => array(
                                                               'type'        => 'text',
                                                               'title'       => __('Conekta API Test Private key', 'woothemes'),
                                                               'default'     => __('', 'woothemes')
                                                               ),
                                       'live_api_key' => array(
                                                               'type'        => 'text',
                                                               'title'       => __('Conekta API Live Private key', 'woothemes'),
                                                               'default'     => __('', 'woothemes')
                                                               ),
                                       'alternate_imageurl' => array(
                                                                     'type'        => 'text',
                                                                     'title'       => __('Alternate Image to display on checkout, use fullly qualified url, served via https', 'woothemes'),
                                                                     'default'     => __('', 'woothemes')
                                                                     ),
                                       'description' => array(
                                                              'title' => __( 'Description', 'woocommerce' ),
                                                              'type' => 'textarea',
                                                              'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
                                                              'default' =>__( 'Por favor realiza el pago en el OXXO más cercano utilizando la clave que mandaremos a tu e-mail.', 'woocommerce' ),
                                                              'desc_tip' => true,
                                                              ),
                                       'instructions' => array(
                                                               'title' => __( 'Instructions', 'woocommerce' ),
                                                               'type' => 'textarea',
                                                               'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
                                                               'default' =>__( 'Por favor realiza el pago en el OXXO más cercano utilizando la clave que mandaremos a tu e-mail.', 'woocommerce' ),
                                                               'desc_tip' => true,
                                                               ),
                                       
                                       );
        }
        
        /**
         * Output for the order received page.
         * @param string $order_id
         */
        function thankyou_page($order_id) {
            $order = new WC_Order( $order_id );
            echo '<p><strong>'.__('Código de Barra').':</strong> <img src="' . get_post_meta( $order->id, 'conekta-barcodeurl', true ). '" /></p>';
            echo '<p><strong>'.__('Referencia').':</strong> ' . get_post_meta( $order->id, 'conekta-barcode', true ). '</p>';
        }
        
        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         */
        function email_barcode($order) {
            echo '<strong>'.__('Código Barra').':</strong> <img src="' . get_post_meta( $order->id, 'conekta-barcodeurl', true ). '" />';
            echo '<p><strong>'.__('Referencia').':</strong> ' . get_post_meta( $order->id, 'conekta-barcode', true ). '</p>';
        }
        
        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && 'on-hold' === $order->status ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }
        
        public function admin_options()
        {
            include_once('templates/cash_admin.php');
        }
        
        public function payment_fields()
        {
            include_once('templates/cash.php');
        }
        
        protected function send_to_conekta()
        {
            global $woocommerce;
            Conekta::setApiKey($this->secret_key);
            Conekta::setLocale("es");
            $data = $this->getRequestData();
                $line_items = array();
                $items = $this->order->get_items();
                foreach ($items as $item) {
                        $line_items = array_merge($line_items, array(array(
                        'name' => $item['name'],
                        'unit_price' => $item['line_total'],
                        'description' =>$item['name'],
                        'quantity' =>$item['qty'],
                        'type' => $item['type']
                        ))
                        );
                }
            $details = array(
                                        "email" => $data['card']['email'],
                                        "name" => $data['card']['name'],
                                        "line_items"  =>$line_items,
                                        "billing_address"  => array(
                                                                "street1" => $data['card']['address_line1'],
                                                                "street2" => $data['card']['address_line2'],
                                                                "zip" => $data['card']['address_zip'],
                                                                "city" => $data['card']['address_city'],
                                                                "phone" => $data['card']['phone'],
                                                                "country" => $data['card']['address_country'],
                                                                "state" => $data['card']['address_state']
                                                                )
			    );
 
            try {
  
                $charge = Conekta_Charge::create(array(
                                                       "amount"=> $data['amount'],
                                                       "currency"=> $data['currency'],
                                                       "reference_id" => $this->order->id,
                                                       "description"=> "Recibo de pago para orden # ". $this->order->id,
                                                       "cash"=> array(
                                                                      "type"=>"oxxo"                        
                                                                      ),
			                               "details"=>$details
                                                       ));
                
                $this->transactionId = $charge->id;
                update_post_meta( $this->order->id, 'conekta-id', $charge->id );
                update_post_meta( $this->order->id, 'conekta-creado', $charge->created_at );
                update_post_meta( $this->order->id, 'conekta-expira', $charge->payment_method->expiry_date );
                update_post_meta( $this->order->id, 'conekta-barcode', $charge->payment_method->barcode );
                update_post_meta( $this->order->id, 'conekta-barcodeurl', $charge->payment_method->barcode_url );
                return true;
                
            } catch(Conekta_Error $e) {
                $description = $e->message_to_purchaser;
                error_log('Gateway Error:' . $description . "\n");
                $woocommerce->add_error(__('Payment error:', 'woothemes') . $description);
                return false;
            }
        }
        
        public function process_payment($order_id)
        {
            global $woocommerce;
            $this->order        = new WC_Order($order_id);
            if ($this->send_to_conekta())
            {
                // Mark as on-hold (we're awaiting the notification of payment)
             $this->order->update_status('on-hold', __( 'Awaiting the conekta OXOO payment', 'woocommerce' ));
                
                // Remove cart
                $woocommerce->cart->empty_cart();
                unset($_SESSION['order_awaiting_payment']);
                
                $result = array(
                                'result' => 'success',
                                'redirect' => $this->get_return_url($this->order)
                                );
                return $result;
            }
            else
            {
                $this->markAsFailedPayment();
                $woocommerce->add_error(__('Transaction Error: Could not complete the payment'), 'woothemes');
            }
        }
        
        protected function markAsFailedPayment()
        {
            $this->order->add_order_note(
                                         sprintf(
                                                 "%s Cash Payment Failed : '%s'",
                                                 $this->GATEWAY_NAME,
                                                 $this->transactionErrorMessage
                                                 )
                                         );
        }
        
        protected function completeOrder()
        {
            global $woocommerce;
            
            if ($this->order->status == 'completed')
                return;
            
            $this->order->payment_complete();
            $woocommerce->cart->empty_cart();
            
            $this->order->add_order_note(
                                         sprintf(
                                                 "%s payment completed with Transaction Id of '%s'",
                                                 $this->GATEWAY_NAME,
                                                 $this->transactionId
                                                 )
                                         );
            
            unset($_SESSION['order_awaiting_payment']);
        }
        
        
        protected function getRequestData()
        {
            if ($this->order AND $this->order != null)
            {
                return array(
                             "amount"      => (float)$this->order->get_total() * 100,
                             "currency"    => strtolower(get_woocommerce_currency()),
                             "description" => sprintf("Charge for %s", $this->order->billing_email),
                             "card"        => array(
                                                    "name"            => sprintf("%s %s", $this->order->billing_first_name, $this->order->billing_last_name),
                                                    "address_line1"   => $this->order->billing_address_1,
                                                    "address_line2"   => $this->order->billing_address_2,
                                                    "phone"   => $this->order->billing_phone,
                                                    "email"   => $this->order->billing_email,
                                                    "address_zip"     => $this->order->billing_postcode,
                                                    "address_state"   => $this->order->billing_state,
                                                    "address_country" => $this->order->billing_country
                                                    )
                             );
            }
            return false;
        }
        
    }
    
    function conekta_cash_order_status_completed($order_id = null)
    {
        global $woocommerce;
        if (!$order_id)
            $order_id = $_POST['order_id'];
        
        $data = get_post_meta( $order_id );
        $total = $data['_order_total'][0] * 100;
        
        $params = array();
        if(isset($_POST['amount']) && $amount = $_POST['amount'])
        {
            $params['amount'] = round($amount);
        }
    }
   
    function conektacheckout_add_cash_gateway($methods)
    {
        array_push($methods, 'WC_Conekta_Cash_Gateway');
        return $methods;
    }
    
    add_filter('woocommerce_payment_gateways',                      'conektacheckout_add_cash_gateway');
    add_action('woocommerce_order_status_processing_to_completed',  'conekta_cash_order_status_completed' );
