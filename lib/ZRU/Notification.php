<?php

namespace ZRU;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class to manage notification from ZRU
 */
class NotificationData 
{
    protected $payload;        // Content of request from ZRU         
    protected $zru;           // ZRUClient

    /**
     * @param array $payload
     * @param object $zru
     */
    public function __construct($payload, $zru) 
    {
        $this->payload = $payload;
        $this->zru = $zru;        
    }

    /**
     * Magic method to dynamically access payload properties
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'transaction':
                return $this->getTransaction();
            case 'subscription':
                return $this->getSubscription();
            case 'authorization':
                return $this->getSubscription();
            case 'sale':
                return $this->getSale();
            default:
                if (array_key_exists($name, $this->payload)) {
                    return $this->payload[$name];
                }

                $trace = debug_backtrace();
                trigger_error(
                    'Undefined property via __get(): ' . $name .
                    ' in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
                return null;
        }
    }

    /**
     * @return object Transaction generated when payment was created
     */
    public function getTransaction() 
    {
        if ($this->type != 'P') {
            return null;
        }

        $id = $this->id;
        if (isset($id)) {
            $transaction = $this->zru->Transaction(['id' => $id]);
            $transaction->retrieve();
            return $transaction;
        }
        return null;
    }

    /**
     * @return object Subscription generated when payment was created
     */
    public function getSubscription() 
    {
        if ($this->type != 'S') {
            return null;
        }

        $id = $this->id;
        if (isset($id)) {
            $subscription = $this->zru->Subscription(['id' => $id]);
            $subscription->retrieve();
            return $subscription;
        }
        return null;
    }

    /**
     * @return object Authorization generated when payment was created
     */
    public function getAuthorization() 
    {
        if ($this->type != 'A') {
            return null;
        }

        $id = $this->id;
        if (isset($id)) {
            $authorization = $this->zru->Authorization(['id' => $id]);
            $authorization->retrieve();
            return $authorization;
        }
        return null;
    }

    /**
     * @return object Sale generated when payment was paid
     */
    public function getSale() 
    {
        if (isset($this->payload['sale_id'])) {
            $sale = $this->zru->Sale(['id' => $this->payload['sale_id']]);
            $sale->retrieve();
            return $sale;
        }
        return null;
    }
}
