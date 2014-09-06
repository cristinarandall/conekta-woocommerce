<?php
    if (!class_exists('Conekta')) {
        require_once("lib/conekta-php/lib/Conekta.php");
    }
    /*
     * Title   : Conekta Payment extension for WooCommerce
     * Author  : Cristina Randall
     * Url     : http://cristinarandall.com/
     * License : http://cristinarandall.com/
     */
    
    class ConektaCheckout extends WC_Payment_Gateway
    {
        protected $GATEWAY_NAME               = "ConektaCheckout";
        protected $usesandboxapi              = true;
        protected $order                      = null;
        protected $transactionId              = null;
        protected $transactionErrorMessage    = null;
        protected $conektaTestApiKey           = '';
        protected $conektaLiveApiKey           = '';
        protected $publishable_key            = '';
        
        public function __construct()
        {
            $this->id              = 'ConektaCheckout';
            $this->has_fields      = true;            
            $this->init_form_fields();
            $this->init_settings();
            $this->title              = $this->settings['title'];
            $this->description        = '';
            $this->icon 		      = $this->settings['alternate_imageurl'] ? $this->settings['alternate_imageurl']  : WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/credits.png';
            $this->usesandboxapi      = strcmp($this->settings['debug'], 'yes') == 0;
            $this->testApiKey 		  = $this->settings['test_api_key'  ];
            $this->liveApiKey 		  = $this->settings['live_api_key'  ];
            $this->testPublishableKey = $this->settings['test_publishable_key'  ];
            $this->livePublishableKey = $this->settings['live_publishable_key'  ];
            $this->useUniquePaymentProfile = strcmp($this->settings['enable_unique_profile'], 'yes') == 0;
            $this->publishable_key    = $this->usesandboxapi ? $this->testPublishableKey : $this->livePublishableKey;
            $this->secret_key         = $this->usesandboxapi ? $this->testApiKey : $this->liveApiKey;
            
            add_action('woocommerce_update_options_payment_gateways_' . $this->id , array($this, 'process_admin_options'));
            add_action('admin_notices'                              , array(&$this, 'perform_ssl_check'    ));
            if($this->useInterval)
            {
                wp_enqueue_script('the_conektacheckout_js', plugins_url('/conekta_checkout.js',__FILE__) );
            }
            wp_enqueue_script('the_conekta_js', 'https://conektaapi.s3.amazonaws.com/v0.3.0/js/conekta.js' );
            
        }
        
        public function perform_ssl_check()
        {
            if (!$this->usesandboxapi && get_option('woocommerce_force_ssl_checkout') == 'no' && $this->enabled == 'yes') :
                echo '<div class="error"><p>'.sprintf(__('%s sandbox testing is disabled and can performe live transactions but the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woothemes'), $this->GATEWAY_NAME, admin_url('admin.php?page=settings')).'</p></div>';
            endif;
        }
        
        public function init_form_fields()
        {
            $this->form_fields = array(
                                       'enabled' => array(
                                                          'type'        => 'checkbox',
                                                          'title'       => __('Enable/Disable', 'woothemes'),
                                                          'label'       => __('Enable Credit Card Payment', 'woothemes'),
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
                                                        'default'     => __('Credit Card Payment', 'woothemes')
                                                        ),
                                       'test_api_key' => array(
                                                               'type'        => 'text',
                                                               'title'       => __('Conekta API Test Private key', 'woothemes'),
                                                               'default'     => __('', 'woothemes')
                                                               ),
                                       'test_publishable_key' => array(
                                                                       'type'        => 'text',
                                                                       'title'       => __('Conekta API Test Public key', 'woothemes'),
                                                                       'default'     => __('', 'woothemes')
                                                                       ),
                                       'live_api_key' => array(
                                                               'type'        => 'text',
                                                               'title'       => __('Conekta API Live Private key', 'woothemes'),
                                                               'default'     => __('', 'woothemes')
                                                               ),
                                       'live_publishable_key' => array(
                                                                       'type'        => 'text',
                                                                       'title'       => __('Conekta API Live Public key', 'woothemes'),
                                                                       'default'     => __('', 'woothemes')
                                                                       ),
                                       'alternate_imageurl' => array(
                                                                     'type'        => 'text',
                                                                     'title'       => __('Alternate Image to display on checkout, use fullly qualified url, served via https', 'woothemes'),
                                                                     'default'     => __('', 'woothemes')
                                                                     ),
                                       'enable_unique_profile' => array(
                                                                        'type'        => 'checkbox',
                                                                        'title'       => __('Enable Payment Profile Creation', 'woothemes'),
                                                                        'label'       => __('Use this to always create a Payment Profile in Conekta (always creates new profile, regardless of logged in user), and associate the charge with the profile. This allows you more easily identify order, credit, or even make an additional charge (from Conekta admin) at a later date.', 'woothemes'),
                                                                        'default'     => 'no'
                                                                        ),
                                       
                                       
                                       );
        }
        
        public function admin_options()
        {
            include_once('templates/admin.php');
        }
        
        public function payment_fields()
        {
            include_once('templates/payment.php');
        }
        
        protected function send_to_conekta()
        {
            global $woocommerce;
            
            Conekta::setApiKey($this->secret_key);
            Conekta::setLocale("es");	
            $data = $this->getRequestData();
            
            try {
               
		$details = array(
					"email" => $data['card']['email'], 
					"name" => $data['card']['name'], 
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
 
                if($this->useUniquePaymentProfile)
                {
                    // Create the user as a customer on Conekta servers
                    $customer = Conekta_Customer::create(array(
                                                               "email" => $data['card']['email'],
                                                               "description" => $data['card']['name'],
                                                               "name" => $data['card']['name'],
                                                               "cards"  => array($data['token'])
                                                               ));
                    $charge = Conekta_Charge::create(array(
                                                           "amount"      => $data['amount'],
                                                           "currency"    => $data['currency'],
                                                           "description" => $data['card']['name'],
							   "reference_id" => $this->order->id,
                                                           "card"    => $customer->id,
                                                           "details"     => $details
                                                           ));
                } else {
                    
                    $charge = Conekta_Charge::create(array(
                                                           "amount"      => $data['amount'],
                                                           "currency"    => $data['currency'],
                                                           "card"        => $data['token'],
							   "reference_id" => $this->order->id,
                                                           "description" => $data['card']['name'],
                                                           "details"     => $details
                                                           ));
                }
                $this->transactionId = $charge->id;
                
                update_post_meta( $this->order->id, 'transaction_id', $this->transactionId);
                return true;
                
            } catch(Conekta_Error $e) {
                $description = $e->message_to_purchaser;
                error_log('Gateway Error:' . $description . "\n");
                $woocommerce->add_error(__('Error: ', 'woothemes') . $description);
                return false;
            }
        }
        
        public function process_payment($order_id)
        {
            global $woocommerce;
            $this->order        = new WC_Order($order_id);
            if ($this->send_to_conekta())
            {
                $this->completeOrder();
                
                $result = array(
                                'result' => 'success',
                                'redirect' => $this->get_return_url($this->order)
                                );
                return $result;
            }
            else
            {
                $this->markAsFailedPayment();
            }
        }
        
        protected function markAsFailedPayment()
        {
            $this->order->add_order_note(
                                         sprintf(
                                                 "%s Credit Card Payment Failed : '%s'",
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
                             "token"       => $_POST['conektaToken'],
                             "description" => sprintf("Charge for %s", $this->order->billing_email),
                             "card"        => array(
                                                    "name"            => sprintf("%s %s", $this->order->billing_first_name, $this->order->billing_last_name),
                                                    "address_line1"   => $this->order->billing_address_1,
                                                    "phone"   => $this->order->billing_phone,
                                                    "email"   => $this->order->billing_email,
                                                    "address_line2"   => $this->order->billing_address_2,
                                                    "address_zip"     => $this->order->billing_postcode,
                                                    "address_city"     => $this->order->billing_city,
                                                    "address_state"   => $this->order->billing_state,
                                                    "address_country" => $this->order->billing_country
                                                    )
                             );
            }
            return false;
        }
        
    }
    
    function conekta_card_order_status_completed($order_id = null)
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
    
    
    
    function conektacheckout_add_card_gateway($methods)
    {
        array_push($methods, 'ConektaCheckout');
        return $methods;
    }
    
    add_filter('woocommerce_payment_gateways',                      'conektacheckout_add_card_gateway');
    add_action('woocommerce_order_status_processing_to_completed',  'conekta_card_order_status_completed' );
