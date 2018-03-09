<?php
/**
 * B2W Digital - Companhia Digital
 *
 * Do not edit this file if you want to update this SDK for future new versions.
 * For support please contact the e-mail bellow:
 *
 * sdk@e-smart.com.br
 *
 * @category  SkuHub
 * @package   SkuHub
 *
 * @copyright Copyright (c) 2018 B2W Digital - BSeller Platform. (http://www.bseller.com.br).
 *
 * @author    Tiago Sampaio <tiago.sampaio@e-smart.com.br>
 */

namespace SkyHub\Api\Service;

use GuzzleHttp\Client as HttpClient;
use SkyHub\Api;
use SkyHub\Api\Helpers;
use SkyHub\Api\Handler\Response\HandlerDefault;
use SkyHub\Api\Log\Loggerable;
use SkyHub\Api\Log\TypeInterface\Request;
use SkyHub\Api\Log\TypeInterface\Response;
use SkyHub\Api\Handler\Response\HandlerException;

abstract class ServiceAbstract implements ServiceInterface
{
    
    use Loggerable, Helpers;
    
    
    const REQUEST_METHOD_GET    = 'GET';
    const REQUEST_METHOD_POST   = 'POST';
    const REQUEST_METHOD_PUT    = 'PUT';
    const REQUEST_METHOD_HEAD   = 'HEAD';
    const REQUEST_METHOD_DELETE = 'DELETE';
    const REQUEST_METHOD_PATCH  = 'PATCH';

    
    /** @var HttpClient */
    protected $client = null;
    
    /** @var array */
    protected $headers = [];
    
    /** @var int */
    protected $timeout = 15;
    
    /** @var int */
    protected $requestId = null;
    
    
    /**
     * Service constructor.
     *
     * @param string $baseUri
     * @param array  $headers
     * @param array  $options
     */
    public function __construct($baseUri = null, array $headers = [], array $options = [], $log = true)
    {
        $this->headers = array_merge($this->headers, $headers);
        
        $defaults = [
            'headers' => $headers,
        ];

        if (empty($baseUri)) {
            $baseUri = 'https://api.skyhub.com.br';
        }
    
        foreach ($options as $key => $value) {
            $defaults[$key] = $value;
        }
        
        $this->prepareHttpClient($baseUri, $defaults);
    
        return $this;
    }
    
    
    /**
     * @param bool $renew
     *
     * @return int
     */
    public function getRequestId($renew = false)
    {
        if (empty($this->requestId) || $renew) {
            $this->requestId = rand(1000000000, 9999999999);
        }
        
        return $this->requestId;
    }
    
    
    /**
     * @param string $method
     * @param string $uri
     * @param null   $body
     * @param array  $options
     *
     * @return Api\Handler\Response\HandlerInterfaceException|Api\Handler\Response\HandlerInterfaceSuccess
     */
    public function request($method, $uri, $body = null, $options = [], $debug = false)
    {
        $options[\GuzzleHttp\RequestOptions::TIMEOUT] = $this->getTimeout();
        $options[\GuzzleHttp\RequestOptions::HEADERS] = $this->headers;
        $options[\GuzzleHttp\RequestOptions::DEBUG]   = (bool) $debug;
        
        $options = $this->prepareRequestBody($body, $options);
        
        /** Log the request before sending it. */
        $logRequest = new Request(
            $this->getRequestId(),
            $method,
            $uri,
            $body,
            $this->protectedHeaders($this->headers),
            $this->protectedOptions($options)
        );
        
        $this->logger()->logRequest($logRequest);

        try {
            /** @var \Psr\Http\Message\ResponseInterface $response */
            $response = $this->httpClient()->request($method, $uri, $options);
    
            /** @var Api\Handler\Response\HandlerInterfaceSuccess $responseHandler */
            $responseHandler = new HandlerDefault($response);
    
            /** Log the request response. */
            $logResponse = (new Response($this->getRequestId()))->importResponseHandler($responseHandler);
        } catch (\Exception $e) {
            /** @var Api\Handler\Response\HandlerInterfaceException $responseHandler */
            $responseHandler = new HandlerException($e);
            
            /** Log the request response. */
            $logResponse = (new Response($this->getRequestId()))->importResponseExceptionHandler($responseHandler);
        }
        
        $this->logger()->logResponse($logResponse);
        
        return $responseHandler;
    }
    
    
    /**
     * @param string|array $bodyData
     * @param array        $options
     *
     * @return array
     */
    protected function prepareRequestBody($bodyData, array &$options = [])
    {
        $options[\GuzzleHttp\RequestOptions::BODY] = $bodyData;
        return $options;
    }
    
    
    /**
     * A private __clone method prevents this class to be cloned by any other class.
     *
     * @return void
     */
    private function __clone()
    {
    }
    
    
    /**
     * A private __wakeup method prevents this object to be unserialized.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
    
    
    /**
     * @return HttpClient
     */
    protected function httpClient()
    {
        return $this->client;
    }
    
    
    /**
     * @param null  $baseUri
     * @param array $defaults
     *
     * @return HttpClient
     */
    protected function prepareHttpClient($baseUri = null, array $defaults = [])
    {
        if (null === $this->client) {
            $this->client = new HttpClient([
                'base_uri' => $baseUri,
                'base_url' => $baseUri,
                'defaults' => $defaults
            ]);
        }
    
        return $this->client;
    }
    
    
    /**
     * @return array
     */
    public function getHeaders()
    {
        return (array) $this->headers;
    }
    
    
    /**
     * @param array $headers
     * @param bool  $append
     *
     * @return $this
     */
    public function setHeaders(array $headers = [], $append = true)
    {
        if (!$append) {
            $this->headers = $headers;
            return $this;
        }
        
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
        
        return $this;
    }
    
    
    /**
     * @return int
     */
    public function getTimeout()
    {
        return (int) $this->timeout;
    }
    
    
    /**
     * @param integer $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
        return $this;
    }
    
    
    /**
     * @param $options
     *
     * @return mixed
     */
    protected function protectedOptions($options)
    {
        $headers = $this->arrayExtract($options, 'headers');
        
        if (empty($headers)) {
            return $options;
        }
    
        $headers = $this->protectedHeaders($headers);
        $options['headers'] = $headers;
        
        return $options;
    }
    
    
    /**
     * @return array
     */
    protected function protectedHeaders(array $headers = [])
    {
        if (empty($headers)) {
            $headers = $this->headers;
        }
    
        if (isset($headers[Api::HEADER_USER_EMAIL])) {
            $headers[Api::HEADER_USER_EMAIL] = $this->protectString($headers[Api::HEADER_USER_EMAIL]);
        }
    
        if (isset($headers[Api::HEADER_API_KEY])) {
            $headers[Api::HEADER_API_KEY] = $this->protectString($headers[Api::HEADER_API_KEY]);
        }
    
        if (isset($headers[Api::HEADER_ACCOUNT_MANAGER_KEY])) {
            $headers[Api::HEADER_ACCOUNT_MANAGER_KEY] = $this->protectString($headers[Api::HEADER_ACCOUNT_MANAGER_KEY]);
        }
        
        return $headers;
    }
}
