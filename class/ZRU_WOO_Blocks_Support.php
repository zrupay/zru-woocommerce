<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ZRU payment method integration
 *
 * @since 1.0.0
 */
final class ZRU_WOO_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * Name of the payment method.
	 *
	 * @var string
	 */
	protected $name = 'zru';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_zru_settings', [] );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$asset_path   = ZRU_WOO_GATEWAY_PATH . '/build/zru-blocks/index.asset.php';
		$version      = ZRU_WOO_Plugin::$version;
		$dependencies = [];
		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = is_array( $asset ) && isset( $asset['version'] )
				? $asset['version']
				: $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
				? $asset['dependencies']
				: $dependencies;
		}
		wp_register_script(
			'zru-woo-blocks-integration',
			ZRU_WOO_GATEWAY_URL . '/build/zru-blocks/index.js',
			$dependencies,
			$version,
			true
		);
		return [ 'zru-woo-blocks-integration'];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$instance = ZRU_WOO_Gateway::getInstance();
		return [
			'key' => $this->get_setting('key'),
			'title' => $this->get_setting('title'),
			'description' => $this->get_setting('description'),
			'icon' => $this->get_setting('icon'),
			'currency' => get_woocommerce_currency(),
			'supports' => $this->get_supported_features(),
			'frontend_is_ready' => $instance->frontend_is_ready(),
		];
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$features = [];
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		if (array_key_exists('zru', $payment_gateways)) {
			$features = $payment_gateways['zru']->supports;
		}
		return $features;
	}
}
