<?php
/**
 * This is the ZRU - WooCommerce Payment Gateway Class.
 */
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('ZRU_WOO_Gateway')) {
	class ZRU_WOO_Gateway extends WC_Payment_Gateway
	{
		/**
		 * Private variables.
		 *
		 * @var		string	$include_path			Path to where this class's includes are located. Populated in the class constructor.
		 * @var		string	$WAY_REDIRECT			Redirect way
		 * @var		string	$WAY_IFRAME				Iframe way
		 */
		private $include_path, $WAY_REDIRECT, $WAY_IFRAME;

		/**
		 * Protected static variables.
		 *
		 * @var		ZRU_WOO_Gateway	$instance		A static reference to a singleton instance of this class.
		 */
		protected static $instance = null;

        public $icon;

		/**
		 * Class constructor. Called when an object of this class is instantiated.
		 *
		 * @since	1.0.0
		 * @uses	plugin_basename()					Available as part of the WordPress core since 1.5.
		 * @uses	WC_Payment_Gateway::init_settings()	If the user has not yet saved their settings, it will extract the
		 *												default values from $this->form_fields defined in an ancestral class
		 *												and overridden below.
		 */
		public function __construct() {
			$this->include_path			= dirname( __FILE__ ) . '/ZRU_WOO_Gateway';

            $this->WAY_REDIRECT         = 'redirect';
            $this->WAY_IFRAME           = 'iframe';

			$this->id					= 'zru';
            $this->icon                 = ZRU_WOO_GATEWAY_URL . '/assets/images/icons/zru.png';
			$this->has_fields        	= false;
			$this->method_title			= __( 'ZRU', 'zru-for-woocommerce' );
			$this->method_description	= __( 'Accept all payment methods using a single platform in WooCommerce.', 'zru-for-woocommerce' );
			$this->supports				= array('products', 'subscriptions', 'refunds');

			$this->init_form_fields();
			$this->init_settings();

            // Define user set variables
	        $this->enabled              = $this->get_option( 'enabled' );
            $this->key                  = $this->get_option( 'key' );
            $this->secret_key           = $this->get_option( 'secret_key' );
            $this->title                = $this->get_option( 'title' );
            $this->description          = $this->get_option( 'description' );
            $this->thank_you_text       = $this->get_option( 'thank_you_text', $this->description );
            $this->way                  = $this->get_option( 'way', $this->WAY_REDIRECT );
            $this->set_completed        = $this->get_option( 'set_completed', 'N' );
            $this->icon                 = $this->get_option( 'icon', $this->icon );

            $this->notify_url = add_query_arg( 'wc-api', 'ZRU_WOO_Gateway', home_url( '/' ) );
            if ( is_ssl() ) {
                $this->notify_url   = str_ireplace( 'http:', 'https:', $this->notify_url );
            } else {
                $this->notify_url   = str_ireplace( 'https:', 'http:',  $this->notify_url );
            }
		}

		/**
		 * Checks if the payment method is enabled. Based on the enabled prop inherited from WC_Payment_Gateway.
		 *
		 * @since 1.0.0
		 * @return Boolean
		 */
		public function is_enabled() {
			return $this->enabled === 'yes';
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = include "{$this->include_path}/form_fields.php";
		}

		/**
		 * Instantiate the class if no instance exists. Return the instance.
		 *
		 * @since	1.0.0
		 * @return	ZRU_WOO_Gateway
		 */
		public static function getInstance()
		{
			if (is_null(self::$instance)) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Is the gateway configured? This method returns true if any of the credentials fields are not empty.
		 *
		 * @since	1.0.0
		 * @return	bool
		 */
		private function is_configured() {
			if (!empty($this->key) ||
				!empty($this->secret_key))
			{
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Add the ZRU gateway to WooCommerce.
		 *
		 * Note:	Hooked onto the "woocommerce_payment_gateways" Filter.
		 *
		 * @since	1.0.0
		 * @see		ZRU_WOO_Plugin::__construct()	For hook attachment.
		 * @param	array	$methods				Array of Payment Gateways.
		 * @return	array							Array of Payment Gateways, with ZRU added.
		 **/
		public function add_zru_gateway($methods) {
			$methods[] = 'ZRU_WOO_Gateway';
			return $methods;
		}

		/**
		 * Admin Panel Options. Overrides the method defined in the parent class.
		 *
		 * @since	1.0.0
		 * @see		WC_Payment_Gateway::admin_options()			For the method that this overrides.
		 * @uses	WC_Settings_API::generate_settings_html()
		 */
		public function admin_options() {
			?>
			<h3><?php esc_html_e( 'ZRU Gateway', 'zru-for-woocommerce' ); ?></h3>

			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
			<?php
		}


        /**
         * Output for the order received page.
		 * @since	1.0.0
         */
        public function thankyou_page() {
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
                $woocommerce->cart->empty_cart();
            } else {
                WC()->cart->empty_cart();
            }

            if ( $this->thank_you_text ) {
                echo esc_html( wpautop( wptexturize( $this->thank_you_text ) ) );
            }
        }

		/**
		 * @since	1.0.0
         */
		private function cart_total_is_positive() {
			return WC()->cart->total > 0;
		}

		/**
		 * This is called by the WooCommerce checkout via AJAX, if ZRU was the selected payment method.
		 *
		 * Note:	This overrides the method defined in the parent class.
		 *
		 * @since	1.0.0
		 * @see		WC_Payment_Gateway::process_payment()	For the method we are overriding.
		 * @param	int	$order_id					The ID of the order.
		 * @return	array
		 */
		public function process_payment($order_id) {			

            if ( $this->way == $this->WAY_REDIRECT ) {
                return $this->start_process_payment( $order_id );
            }

            $order = wc_get_order( $order_id );

            if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) {
                $redirect_url = add_query_arg('order', $order->get_id(), add_query_arg('key', $order->get_order_key(), get_permalink(woocommerce_get_page_id('pay'))));
            } else {
                $redirect_url = $order->get_checkout_payment_url( true );
            }

            return array(
                    'result'        => 'success',
                    'redirect'      => $redirect_url
            );
		}

		/**
		* @since	1.0.0
		*/
        function receipt_page( $order_id ) {
            return $this->start_process_payment( $order_id );
        }

		/**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */
        public function start_process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            $zru = new ZRU\ZRUClient($this->key, $this->secret_key);

            // Language
            $customer_language = substr( get_bloginfo("language"), 0, 2 );
            switch ( $customer_language ) {
                case 'es':
                    $language = 'es';
                    break;
                case 'en':
                    $language = 'en';
                    break;
                case 'pt':
                    $language = 'pt';
                    break;
                case 'ca':
                    $language = 'ca';
                    break;
                case 'de':
                    $language = 'de';
                    break;
                case 'it':
                    $language = 'it';
                    break;
                case 'fr':
                    $language = 'fr';
                    break;
                case 'gl':
                    $language = 'gl';
                    break;
                case 'eu':
                    $language = 'eu';
                    break;
            }

            if( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) {
                $obj = $this->process_subscription_payment( $zru, $order, $language, $order_id );
            } else {
                $obj = $this->process_regular_payment( $zru, $order, $language, $order_id );
            }

            if ( $this->way == $this->WAY_IFRAME ) {
				echo '<iframe allowpaymentrequest sandbox="allow-forms allow-popups allow-pointer-lock allow-same-origin allow-scripts allow-top-navigation" src="'.esc_html( $obj->getIframeUrl() ).'" frameBorder="0" style="width: 100%; height: 700px"></iframe>';
            } else {
				return array(
					'result' 	=> 'success',
					'redirect'	=> $obj->getPayUrl()
				);
			}
        }

        /**
         * Process regular payment and return the result
         *
         * @param object $zru
         * @param object $order
         * @param string $language
         * @return array
         */
        public function process_regular_payment( $zru, $order, $language, $order_id ) {
            $first_name = '';
            $last_name = '';
            $email = '';
            $postal_code = '';
            $country = '';
            $city = '';
            $line1 = '';
            $line2 = '';
            $line3 = '';
            $state = '';
            $phone = '';
            $ship_city = '';
            $ship_country = '';
            $ship_line1 = '';
            $ship_line2 = '';
            $ship_line3 = '';
            $ship_postal_code = '';
            $ship_state = '';
            $user_id = '';
			if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {                
                $first_name = $order->billing_first_name;
                $last_name = $order->billing_last_name;
                $this->check_fill_first_last_name($first_name, $last_name);
                $email = $order->billing_email;
                $postal_code = $order->billing_postcode;
                $country = $order->billing_country;
                $city = $order->billing_city;
                $line1 = $order->billing_address_1;
                $line2 = $order->billing_address_2;
                $state = $order->billing_state;
                $phone = $order->billing_phone;
                $ship_city = $order->shipping_city;
                $ship_country = $order->shipping_country;
                $ship_line1 = $order->shipping_address_1;
                $ship_line2 = $order->shipping_address_2;
                $ship_postal_code = $order->shipping_postcode;
                $ship_state = $order->shipping_state;
                $user_id = $order->user_id;
            } else {
                $first_name = $order->get_billing_first_name();
                $last_name = $order->get_billing_last_name();
                $this->check_fill_first_last_name($first_name, $last_name);
                $email = $order->get_billing_email();
                $postal_code = $order->get_billing_postcode();
                $country = $order->get_billing_country();
                $city = $order->get_billing_city();
                $line1 = $order->get_billing_address_1();
                $line2 = $order->get_billing_address_2();
                $state = $order->get_billing_state();
                $phone = $order->get_billing_phone();
                $ship_city = $order->get_shipping_city();
                $ship_country = $order->get_shipping_country();
                $ship_line1 = $order->get_shipping_address_1();
                $ship_line2 = $order->get_shipping_address_2();
                $ship_postal_code = $order->get_shipping_postcode();
                $ship_state = $order->get_shipping_state();
                $user_id = $order->get_user_id();
            }

            // Create transaction
            $transaction = $zru->Transaction(
                array(
                    "order_id" => $order_id,
                    "currency" => get_woocommerce_currency(),
                    "return_url"  => $this->get_return_url( $order ),
                    "cancel_url" => $order->get_cancel_order_url(),
                    "notify_url" => $this->notify_url,
                    "language" => $language,
                    "products" => array(
                        array(
                            "amount" => 1,
                            "product" => array(
                                "name" => __('Payment of order ', 'zru-for-woocommerce').$order_id,
                                "price" => $order->get_total()
                            )
                        )
                    ),
					"extra" => array(
						"first_name" => $first_name,
						"last_name" => $last_name,
						"email" => $email,
                        "phone_number" => $phone,
						"country" => $country,
                        "billing_street_name" => $line1.' '.$line2.' '.$line3,
                        "billing_postal_code" => $postal_code,
                        "billing_country_code" => $country,
                        "billing_city" => $city,
                        "billing_province" => $state,
                        "shipping_street_name" => $ship_line1.' '.$ship_line2.' '.$ship_line3,
                        "shipping_postal_code" => $ship_postal_code,
                        "shipping_country_code" => $ship_country,
                        "shipping_city" => $ship_city,
                        "shipping_province" => $ship_state,
                        "user_id" => $user_id,
                        "zru_lib" => array(
                            "name" => "woocommerce",
                            "version" => "1.0.1"
                        )
					)
                )
            );
            $transaction->save();

            return $transaction;
        }

        /**
         * Process subscription payment and return the result
         *
         * @param object $zru
         * @param object $order
         * @param string $language
         * @return array
         */
        public function process_subscription_payment( $zru, $order, $language, $order_id ) {
            $first_name = '';
            $last_name = '';
            $email = '';
            $postal_code = '';
            $country = '';
            $city = '';
            $line1 = '';
            $line2 = '';
            $line3 = '';
            $state = '';
            $phone = '';
            $ship_city = '';
            $ship_country = '';
            $ship_line1 = '';
            $ship_line2 = '';
            $ship_line3 = '';
            $ship_postal_code = '';
            $ship_state = '';
            $user_id = '';
			if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {                
                $first_name = $order->billing_first_name;
                $last_name = $order->billing_last_name;
                $this->check_fill_first_last_name($first_name, $last_name);
                $email = $order->billing_email;
                $postal_code = $order->billing_postcode;
                $country = $order->billing_country;
                $city = $order->billing_city;
                $line1 = $order->billing_address_1;
                $line2 = $order->billing_address_2;
                $state = $order->billing_state;
                $phone = $order->billing_phone;
                $ship_city = $order->shipping_city;
                $ship_country = $order->shipping_country;
                $ship_line1 = $order->shipping_address_1;
                $ship_line2 = $order->shipping_address_2;
                $ship_postal_code = $order->shipping_postcode;
                $ship_state = $order->shipping_state;
                $user_id = $order->user_id;
            } else {
                $first_name = $order->get_billing_first_name();
                $last_name = $order->get_billing_last_name();
                $this->check_fill_first_last_name($first_name, $last_name);
                $email = $order->get_billing_email();
                $postal_code = $order->get_billing_postcode();
                $country = $order->get_billing_country();
                $city = $order->get_billing_city();
                $line1 = $order->get_billing_address_1();
                $line2 = $order->get_billing_address_2();
                $state = $order->get_billing_state();
                $phone = $order->get_billing_phone();
                $ship_city = $order->get_shipping_city();
                $ship_country = $order->get_shipping_country();
                $ship_line1 = $order->get_shipping_address_1();
                $ship_line2 = $order->get_shipping_address_2();
                $ship_postal_code = $order->get_shipping_postcode();
                $ship_state = $order->get_shipping_state();
                $user_id = $order->get_user_id();
            }

            $unconverted_periods = array(
                'period'        => WC_Subscriptions_Order::get_subscription_period( $order ),
                'trial_period'  => WC_Subscriptions_Order::get_subscription_trial_period( $order )
            );

            $converted_periods = array();
            foreach ( $unconverted_periods as $key => $period ) {
                switch( strtolower( $period ) ) {
                    case 'day':
                        $converted_periods[$key] = 'D';
                        break;
                    case 'week':
                        $converted_periods[$key] = 'W';
                        break;
                    case 'year':
                        $converted_periods[$key] = 'Y';
                        break;
                    case 'month':
                    default:
                        $converted_periods[$key] = 'M';
                        break;
                }
            }

            $period = $converted_periods['period'];
            $duration = WC_Subscriptions_Order::get_subscription_interval( $order );
            $price = WC_Subscriptions_Order::get_total_initial_payment( $order );

            // Create transaction
            $subscription = $zru->Subscription(
                array(
                    "order_id" => $order_id,
                    "currency" => get_woocommerce_currency(),
                    "return_url"  => $this->get_return_url( $order ),
                    "cancel_url" => $order->get_cancel_order_url(),
                    "notify_url" => $this->notify_url,
                    "language" => $language,
                    "plan" => array(
                        "name" => __('Subscription of order ', 'zru-for-woocommerce').$order_id,
                        "price" => $price,
                        "duration" => $duration,
                        "unit" => $period,
                        "recurring" => True
                    ),
					"extra" => array(
						"first_name" => $first_name,
						"last_name" => $last_name,
						"email" => $email,
                        "phone_number" => $phone,
						"country" => $country,
                        "billing_street_name" => $line1.' '.$line2.' '.$line3,
                        "billing_postal_code" => $postal_code,
                        "billing_country_code" => $country,
                        "billing_city" => $city,
                        "billing_province" => $state,
                        "shipping_street_name" => $ship_line1.' '.$ship_line2.' '.$ship_line3,
                        "shipping_postal_code" => $ship_postal_code,
                        "shipping_country_code" => $ship_country,
                        "shipping_city" => $ship_city,
                        "shipping_province" => $ship_state,
                        "user_id" => $user_id,
                        "zru_lib" => array(
                            "name" => "woocommerce",
                            "version" => "1.0.1"
                        )
					)
                )
            );
            $subscription->save();

            return $subscription;
        }

        private function check_fill_first_last_name(&$first_name, &$last_name){
            if(empty($last_name)){
                $fn_parts = explode(' ', $first_name);
                
                if(count($fn_parts)>1){
                    $first_name = array_shift($fn_parts);
                    $last_name = implode(' ', $fn_parts);
                }else{
                    $first_name = $fn_parts[0];
                    $last_name = $first_name;
                }
            }
        }
        
        /**
         * Process refund
         *
         * Overriding refund method
         *
         * @param       int $order_id
         * @param       float $amount
         * @param       string $reason
         * @return      mixed True or False based on success, or WP_Error
         */
        public function process_refund( $order_id, $amount = null, $reason = '' ) {
            $order = wc_get_order( $order_id );

            $sale_id = $order->get_transaction_id();
            if ( !$sale_id ) {
                return new WP_Error( 'zru_gateway_wc_refund_error',
                    sprintf(
                        /* translators: %s: class */
                        __( 'Refund %s failed because Transaction ID is empty.', 'zru-for-woocommerce' ),
                        get_class( $this )
                    )
                );
            }

            $error_message = sprintf(
                /* translators: %s: class */
                __( 'Refund %s failed', 'zru-for-woocommerce' ),
                get_class( $this )
            );

            try {
                $zru = new ZRU\ZRUClient($this->key, $this->secret_key);
                $sale = $zru->Sale(
                    array(
                        'id' => $sale_id
                    )
                );
                $sale->retrieve();

                $refund_data = array();
                // If the amount is set, refund that amount, otherwise the entire amount is refunded
                if ( $amount ) {
                    $refund_data['amount'] = $amount;
                } else {
                    $refund_data['amount'] = $order->get_total();
                }
                $response = $sale->refund($refund_data);
                if ( $response->success ) {
                    $order->add_order_note( __( 'ZRU Refund Amount: ', 'zru-for-woocommerce' ).$refund_data['amount'] );
                    return true;
                } else {
                    $order->add_order_note($error_message);
                    return new WP_Error( 'zru_gateway_wc_refund_error', $error_message );
                }
            } catch ( Exception $e ) {
                $order->add_order_note($error_message);
                // Something failed somewhere, send a message.
                return new WP_Error( 'zru_gateway_wc_refund_error', $error_message );
            }
            return false;
        }

		/**
		 * This is triggered when customers confirm payment and return from the gateway
		 * Note:	Hooked onto the "woocommerce_api_zru_woo_gateway" action.
		 * @since	1.0.0
		 */
		public function check_notification() {
			global $woocommerce;

            if ( !empty( $_REQUEST ) ) {
                $zru = new ZRU\ZRUClient($this->key, $this->secret_key);
                $notification_data = $zru->getNotificationData();

                if ( !empty( $notification_data ) ) {
                    @ob_clean();

                    if ( $notification_data->status == 'D' ) {
                        $order = new WC_Order( $notification_data->order_id );

                        if ( $order->status == 'completed' ) {
                             wp_die();
                        }

                        $sale_id = $notification_data->sale_id;
                        $gateway = $notification_data->_gateway;
                        $charge_id = $notification_data->_charge_id;
                        $auth_code = null;
                        $gateway_id = null;
                        $gateway_code = null;
                        if ( !empty($gateway) ) {
                            if ( isset( $gateway['auth_code'] ) ) {
                                $auth_code = $gateway['auth_code'];
                            }
                            if ( isset( $gateway['identification'] ) ) {
                                $gateway_id = $gateway['identification'];
                            }
                            if ( isset( $gateway['code'] ) ) {
                                $gateway_code = $gateway['code'];
                            }
                        }

                        // Payment completed
                        $order->add_order_note( __('ZRU payment completed', 'zru-for-woocommerce') );
                        if ( !empty($sale_id) ) {
                            $order->add_order_note( __('ZRU Sale ID: ', 'zru-for-woocommerce').$sale_id );
                        }
                        if ( !empty($charge_id) ) {
                            $order->add_order_note( __('ZRU Charge ID: ', 'zru-for-woocommerce').$charge_id );
                        }
                        if ( !empty($gateway_code) ) {
                            $order->add_order_note( __('ZRU Gateway Code: ', 'zru-for-woocommerce').$gateway_code );
                        }
                        if ( !empty($gateway_id) ) {
                            $order->add_order_note( __('ZRU Gateway ID: ', 'zru-for-woocommerce').$gateway_id );
                        }
                        if ( !empty($auth_code) ) {
                            $order->add_order_note( __('ZRU Gateway Authorization Code: ', 'zru-for-woocommerce').$auth_code );
                        }                        
                        
                        $order->payment_complete( $sale_id );

                        // Set order as completed if user did set up it
                        if ( 'Y' == $this->set_completed ) {
                            $order->update_status( 'completed' );
                        }
                    } else if ( $notification_data->status == 'C' ) {
                        // Order failed
                        $message = __('ZRU payment cancelled', 'zru-for-woocommerce');
                        $order->update_status('failed', $message );
                    }
                }
            }
		}

		/**
		 * Return the current settings for ZRU Plugin
		 *
		 * @since	1.0.0
		 * @return 	array 	settings array values
		 */
		public function getSettings() {
			return $this->settings;
		}

		public function frontend_is_ready(){
			return
				$this->is_enabled()
				&& $this->is_configured();
		}

		public function extend_store_api() {
			if ( ! function_exists('woocommerce_store_api_register_endpoint_data') ) {
				return;
			}

			woocommerce_store_api_register_endpoint_data([
				'endpoint' => CartItemSchema::IDENTIFIER,
				'namespace' => 'zru-for-woocommerce',
			]);
		}
	}
}
