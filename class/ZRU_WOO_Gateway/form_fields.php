<?php
/**
* Default values for the WooCommerce ZRU Plugin Admin Form Fields
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	'enabled' => array(
		'title'				=> __( 'Enable/Disable', 'zru-for-woocommerce' ),
		'type'				=> 'checkbox',
		'label'				=> __( 'Enable ZRU', 'zru-for-woocommerce' ),
		'default'			=> 'yes'
	),
	'key' => array(
		'title'       => __( 'Public Key', 'zru-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Public Key obtained in panel.zrupay.com', 'zru-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'secret_key' => array(
		'title'       => __( 'Secret Key', 'zru-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Secret Key obtained in panel.zrupay.com', 'zru-for-woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'title' => array(
		'title'				=> __( 'Title', 'zru-for-woocommerce' ),
		'type'				=> 'text',
		'description'		=> __( 'This controls the payment method title which the user sees during checkout.', 'zru-for-woocommerce' ),
		'default'			=> __( 'ZRU', 'zru-for-woocommerce' )
	),
	'description' => array(
		'title'       => __( 'Description', 'zru-for-woocommerce' ),
		'type'        => 'textarea',
		'description' => __( 'Payment method description that the customer will see on your checkout', 'zru-for-woocommerce' ),
		'default'     => __( 'Select among several payment methods the one that works best for you in ZRU', 'zru-for-woocommerce' ),
		'desc_tip'    => true,
	),
	'thank_you_text' => array(
		'title'       => __( 'Text in thank you page', 'zru-for-woocommerce' ),
		'type'        => 'textarea',
		'description' => __( 'Text that will be added to the thank you page', 'zru-for-woocommerce' ),
		'default'     => __( 'Successful payment using ZRU', 'zru-for-woocommerce' ),
		'desc_tip'    => true,
	),
	'way' => array(
		'title'       => __( 'Integration', 'zru-for-woocommerce' ),
		'type'        => 'select',
		'description' => __( 'Way to integrate ZRU.', 'zru-for-woocommerce' ),
		'options'     => array(
			'redirect' => __( 'Redirect', 'zru-for-woocommerce' ),
			'iframe' => __( 'iFrame', 'zru-for-woocommerce' )
		),
		'default'     => 'redirect'
	),
	'set_completed' => array(
		'title'       => __( 'Set order as completed after payment?', 'zru-for-woocommerce' ),
		'type'        => 'select',
		'description' => __( 'After payment, should the order be set as completed? Default is "processing".', 'zru-for-woocommerce' ),
		'desc_tip'    => false,
		'options'     => array(
			'N' => __( 'No', 'zru-for-woocommerce' ),
			'Y' => __( 'Yes', 'zru-for-woocommerce' ),
		),
		'default'     => 'N'
	),
	'icon' => array(
		'title'   => __( 'Icon', 'zru-for-woocommerce' ),
		'type'    => 'text',
		'label'   => __( 'Icon to show in the order page', 'zru-for-woocommerce' ),
		'default' => ZRU_WOO_GATEWAY_URL . '/assets/images/icons/zru.png'
	),
);
