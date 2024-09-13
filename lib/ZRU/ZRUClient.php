<?php

namespace ZRU;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once('Request.php');
require_once('Objects.php');
require_once('Resources.php');
require_once('Notification.php');

/**
 * ZRU - class used to manage the communication with ZRU API
 */
class ZRUClient
{
    protected $apiRequest;

    /**
     * @param string   $key
     * @param string   $secret
     */
    public function __construct($key, $secret) 
    {
        $this->apiRequest = new APIRequest($key, $secret);

        $this->product = new ProductResource($this->apiRequest, '/product/', 'ZRU\Product');
        $this->plan = new PlanResource($this->apiRequest, '/plan/', 'ZRU\Plan');
        $this->tax = new TaxResource($this->apiRequest, '/tax/', 'ZRU\Tax');
        $this->shipping = new ShippingResource($this->apiRequest, '/shipping/', 'ZRU\Shipping');
        $this->coupon = new CouponResource($this->apiRequest, '/coupon/', 'ZRU\Coupon');
        $this->transaction = new TransactionResource($this->apiRequest, '/transaction/', 'ZRU\Transaction');
        $this->subscription = new SubscriptionResource($this->apiRequest, '/subscription/', 'ZRU\Subscription');
        $this->authorization = new AuthorizationResource($this->apiRequest, '/authorization/', 'ZRU\Authorization');
        $this->currency = new CurrencyResource($this->apiRequest, '/currency/', 'ZRU\Currency');
        $this->gateway = new GatewayResource($this->apiRequest, '/gateway/', 'ZRU\Gateway');
        $this->payData = new PayDataResource($this->apiRequest, '/pay/', 'ZRU\PayData');
        $this->sale = new SaleResource($this->apiRequest, '/sale/', 'ZRU\Sale');
        $this->client = new ClientResource($this->apiRequest, '/client/', 'ZRU\Client');
        $this->wallet = new WalletResource($this->apiRequest, '/wallet/', 'ZRU\Wallet');
    }

    /**
     * @param string    $class; Object class name
     * @param object    $resource; Resource instance
     * @param array     $payload
     */
    private function __wrapper ($class, $resource, $payload) 
    {
        return new $class ($payload, $resource, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Product ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Product', $this->product, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Plan ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Plan', $this->plan, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Tax ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Tax', $this->tax, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Shipping ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Shipping', $this->shipping, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Coupon ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Coupon', $this->coupon, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Transaction ($payload = array()) 
    {   
        return $this->__wrapper('ZRU\Transaction', $this->transaction, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Subscription ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Subscription', $this->subscription, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Authorization ($payload = array()) 
    {   
        return $this->__wrapper('ZRU\Authorization', $this->authorization, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Sale ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Sale', $this->sale, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Currency ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Currency', $this->currency, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Gateway ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Gateway', $this->gateway, $payload);
    }

    /**
     * @param array   $payload
     */
    public function PayData ($payload = array()) 
    {
        return $this->__wrapper('ZRU\PayData', $this->payData, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Client ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Client', $this->client, $payload);
    }

    /**
     * @param array   $payload
     */
    public function Wallet ($payload = array()) 
    {
        return $this->__wrapper('ZRU\Wallet', $this->wallet, $payload);
    }

    /**
     * @param array   $payload
     */
    public function NotificationData ($payload = array()) 
    {
        return $this->__wrapper('ZRU\NotificationData', $this, $payload);
    }

    public function getNotificationData(){
        $sanitizedData = [];
        $errors = [];

        $expectedFields = [
            'id' => [
                'filter' => FILTER_SANITIZE_STRING,
                'required' => true
            ],
            'sale_id' => [
                'filter' => FILTER_SANITIZE_STRING,
                'required' => false
            ],
            'status' => [
                'filter' => FILTER_SANITIZE_STRING,
                'required' => true
            ],
            'status' => [
                'filter' => FILTER_SANITIZE_STRING,
                'sanitize' => FILTER_SANITIZE_STRING,
                'required' => true
            ],
            'subscription_status' => [
                'filter' => FILTER_SANITIZE_STRING,
                'sanitize' => FILTER_SANITIZE_STRING,
                'required' => true
            ],
            'type' => [
                'filter' => FILTER_SANITIZE_STRING,
                'sanitize' => FILTER_SANITIZE_STRING,
                'required' => true
            ],
            'order_id' => [
                'filter' => FILTER_VALIDATE_INT,
                'sanitize' => FILTER_SANITIZE_NUMBER_INT,
                'required' => true
            ],
            'action' => [
                'filter' => FILTER_SANITIZE_STRING,
                'sanitize' => FILTER_SANITIZE_STRING,
                'required' => true
            ],
            'sale_action' => [
                'filter' => FILTER_SANITIZE_STRING,
                'sanitize' => FILTER_SANITIZE_STRING,
                'required' => true
            ],
        ];

        $json = file_get_contents('php://input');
        $data = json_decode($json, true); // decode as associative array

        if (json_last_error() !== JSON_ERROR_NONE) {
            die('Invalid JSON input');
        }

        foreach ($expectedFields as $field => $rules) {
            if (isset($data[$field])) {
                // Sanitize the input
                if (isset($rules['sanitize'])) {
                    $data[$field] = filter_var($data[$field], $rules['sanitize']);
                }

                // Validate the input
                $sanitizedValue = filter_var($data[$field], $rules['filter']);
                if ($sanitizedValue === false && $rules['filter'] !== FILTER_SANITIZE_STRING) {
                    $errors[] = "Invalid value for $field.";
                } else {
                    // Escape the sanitized value
                    $sanitizedData[$field] = htmlspecialchars($sanitizedValue, ENT_QUOTES, 'UTF-8');
                }
            } else {
                if ($rules['required']) {
                    $errors[] = "$field is required.";
                }
            }
        }

        if (!empty($errors)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo wp_json_encode(['errors' => $errors]);
            exit;
        }

        return $this->NotificationData($sanitizedData);
    }
}
