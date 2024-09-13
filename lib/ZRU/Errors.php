<?php

namespace ZRU;

if ( ! defined( 'ABSPATH' ) ) exit;

use Exception;

/**
 * ZRU Error - class used to manage the exceptions related with zru library
 */
class ZRUError extends Exception
{
    protected $jsonBody;                // Response from server
    protected $resource;                // Class resource used when the error raised
    protected $resourceId;              // Resource id requested when the error raised

    /**
     * @param string    $message
     * @param string    $jsonBody
     * @param array     $resource
     * @param string    $resourceId
     */
    public function __construct($message, $jsonBody = null, $resource = null, $resourceId = null) 
    {
        $this->jsonBody = $jsonBody;
        $this->resource = $resource;        
        $this->resourceId = $resourceId;        
        
        parent::__construct($message);
    }

    /**
     * @return $jsonBody
     */
    public function getJsonBody() 
    {
        return $this->jsonBody;
    }

    /**
     * @return string Error type and response
     */
    public function __toString() 
    {
        return "{$this->message} {$this->jsonBody}";
    }
}


class InvalidRequestZRUError extends ZRUError {}


class BadUseZRUError extends ZRUError {}
