<?php
/**
 * Plugin Name: ZRU for WooCommerce
 * Description: WooCommerce library for the ZRU Platform.
 * Author: ZRU
 * Author URI: https://www.zrupay.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.0.3
 * Text Domain: zru-for-woocommerce
 *
 * Copyright: (c) 2024 ZRU
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if (!defined('ABSPATH')) {
	exit;
}

define( 'ZRU_WOO_GATEWAY_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'ZRU_WOO_GATEWAY_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

if (!class_exists('ZRU_WOO_Plugin')) {
	class ZRU_WOO_Plugin
	{
		/**
		 * @var		ZRU_WOO_Plugin		$instance	A static reference to an instance of this class.
		 */
		protected static $instance;

		/**
		 * @var		int					$version	A reference to the plugin version, which will match
		 *											the value in the comments above.
		 */
		public static $version = '1.0.3';

		/**
		 * Import required classes.
		 *
		 * @since	1.0.0
		 * @used-by	self::init()
		 * @used-by	self::deactivate_plugin()
		 */
		public static function load_classes()
		{
			$autoloader_param = __DIR__ . '/lib/ZRU/ZRUClient.php';
			// Load up the ZRU library
			try {
				require_once $autoloader_param;
			} catch (\Exception $e) {
				throw new \Exception('The ZRU plugin was not installed correctly or the files are corrupt. Please reinstall the plugin. If this message persists after a reinstall, contact hola@zrupay.com with this message.');
			}
			if (class_exists('WC_Payment_Gateway')) {
				require_once dirname(__FILE__) . '/class/ZRU_WOO_Gateway.php';
			}
		}

		/**
		 * Class constructor. Called when an object of this class is instantiated.
		 *
		 * @since	1.0.0
		 * @see		ZRU_WOO_Plugin::init()						For where this class is instantiated.
		 * @see		WC_Settings_API::process_admin_options()
		 * @uses	ZRU_WOO_Gateway::getInstance()
		 */
		public function __construct()
		{
			$gateway = ZRU_WOO_Gateway::getInstance();

			/**
			 * Actions.
			 */
			add_action( "woocommerce_update_options_payment_gateways_{$gateway->id}", array($gateway, 'process_admin_options'), 10, 0 ); # process_admin_options() is defined in ZRU_WOO_Gateway's grandparent class: WC_Settings_API.
		
			add_action( 'wp_enqueue_scripts', array($this, 'init_website_assets'), 10, 0 );
			add_action( 'woocommerce_api_zru_woo_gateway', array($gateway, 'check_notification') );
			add_action( 'woocommerce_receipt_zru', array( $gateway, 'receipt_page' ) );

			/**
			 * Filters.
			 */
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this, 'filter_action_links'), 10, 1 );
			add_filter( 'woocommerce_payment_gateways', array($gateway, 'add_zru_gateway'), 10, 1 );
			add_filter(
				'__experimental_woocommerce_blocks_add_data_attributes_to_namespace',
				function ( $allowed_namespaces ) {
					$allowed_namespaces[] = 'zru-for-woocommerce';
					return $allowed_namespaces;
				},
				10, 1
			);
		}

		/**
		 *
		 * @since	1.0.0
		 * @see		self::__construct()		For hook attachment.
		 * @param	array	$links
		 * @return	array
		 */
		public function filter_action_links($links)
		{
			$additional_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zru' ) . '">' . __( 'Settings', 'zru-for-woocommerce' ) . '</a>',
			);

			return array_merge($additional_links, $links);
		}

		/**
		 * Note: Hooked onto the "wp_enqueue_scripts" Action to avoid the Wordpress Notice warnings
		 *
		 * @since	1.0.0
		 * @see		self::__construct()		For hook attachment.
		 */
		public function init_website_assets()
		{
			if ( ! self::init() ) { return; }
		}

		/**
		 * Initialise the class and return an instance.
		 *
		 * Note:	Hooked onto the "plugins_loaded" Action.
		 *
		 * @since	1.0.0
		 * @uses	self::load_classes()
		 * @return	ZRU_WOO_Plugin
		 * @used-by	self::activate_plugin()
		 */
		public static function init()
		{
			self::load_classes();
			if (!class_exists('ZRU_WOO_Gateway')) {
				return false;
			}
			if (is_null(self::$instance)) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Callback for when this plugin is activated. Schedules the cron jobs.
		 *
		 * @since	1.0.0
		 * @uses	self::init()
		 */
		public static function activate_plugin()
		{
			if (!current_user_can( 'activate_plugins' )) {
				return;
			}

			self::init(); # Can't just use load_classes() here because the cron schedule is setup in the filter, which attaches inside the class constructor. Have to do a full init.
		}

		/**
		 * Callback for when this plugin is deactivated. Deletes the scheduled cron jobs.
		 *
		 * @since	1.0.0
		 * @uses	self::load_classes()
		 */
		public static function deactivate_plugin()
		{
			if (!current_user_can( 'activate_plugins' )) {
				return;
			}

			self::load_classes();
		}

		/**
		 * Callback for when the plugin is uninstalled. Remove all of its data.
		 *
		 * Note:	This function is called when this plugin is uninstalled.
		 *
		 * @since	1.0.0
		 */
		public static function uninstall_plugin()
		{
			if (!current_user_can( 'activate_plugins' )) {
				return;
			}
		}

		public static function extend_store_api() {
			if ( ! self::init() ) { return; }

			$gateway = ZRU_WOO_Gateway::getInstance();
			$gateway->extend_store_api();
		}

		public static function add_woocommerce_blocks_support(){
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				require_once dirname(__FILE__) . '/class/ZRU_WOO_Blocks_Support.php';
				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function( PaymentMethodRegistry $payment_method_registry ) {
						$payment_method_registry->register( new ZRU_WOO_Blocks_Support );
					}
				);
			}
		}

		/**
		 * This function runs when WordPress completes its upgrade process
		 * It iterates through each plugin updated to see if ours is included
		 * @param $upgrader_object Array
		 * @param $options Array
		 */
		public static function upgrade_complete($upgrader_object, $options)
		{
			// If an update has taken place and the updated type is plugins and the plugins element exists
			if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
				// The path to our plugin's main file
				$our_plugin = plugin_basename( __FILE__ );

				// Iterate through the plugins being updated and check if ours is there
				foreach ($options['plugins'] as $plugin) {
	 				if ($plugin == $our_plugin) {
						if (function_exists('is_multisite') && is_multisite() && function_exists('get_sites')) {
							foreach (get_sites() as $site) {
								switch_to_blog($site->blog_id);
								self::init();
								restore_current_blog();
							}
						} else {							
							self::init();
						}
	 				}
				}
   			}
		}

		public static function register_blocks() {
			if ( ! function_exists( 'register_block_type' ) ) {
				// Block editor is not available.
				return;
			}

			if ( ! self::init() ) {
				// WooCommerce is not active.
				return;
			}

			$instance = ZRU_WOO_Gateway::getInstance();
		}

		public static function plugin_dependencies() {
			if ( ! function_exists('WC') ) {
				// show notice if WooCommerce plugin is not active
				add_action('admin_notices', array(get_called_class(), 'admin_notice_dependency_error'));
			}
		}

		public static function admin_notice_dependency_error() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'ZRU Gateway for WooCommerce requires WooCommerce to be installed and active!', 'zru-for-woocommerce' ); ?></p>
			</div>
			<?php
		}
	}

	register_activation_hook( __FILE__, array('ZRU_WOO_Plugin', 'activate_plugin') );
	register_deactivation_hook( __FILE__, array('ZRU_WOO_Plugin', 'deactivate_plugin') );
	register_uninstall_hook( __FILE__, array('ZRU_WOO_Plugin', 'uninstall_plugin') );

	add_action( 'init', array('ZRU_WOO_Plugin', 'register_blocks') );
	add_action( 'plugins_loaded', array('ZRU_WOO_Plugin', 'plugin_dependencies') );
	add_action( 'plugins_loaded', array('ZRU_WOO_Plugin', 'init'), 10, 0 );
	add_action( 'upgrader_process_complete', array('ZRU_WOO_Plugin', 'upgrade_complete'), 10, 2 );
	add_action( 'woocommerce_blocks_loaded', array('ZRU_WOO_Plugin', 'extend_store_api') );
	add_action( 'woocommerce_blocks_loaded', array('ZRU_WOO_Plugin', 'add_woocommerce_blocks_support') );
	// Declare compatibility with custom order tables for WooCommerce.
	add_action( 'before_woocommerce_init', function() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}
	);
}
