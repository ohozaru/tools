<?php
/**
 * Example of usage:
 * 
    $request
        ->setCacheTime(10)  //request response will be cached for 10s
        ->setLockTime(2)    //setup lock for 2s to not allow second call to origin
                            //for next request with the same uri
        ->init();           

    if ($request->isInProgress()) {
        //what to do when lock is setup for this uri
    }
    else {
        $request->getResponse()->flush();
    }
 */
namespace HttpProxy;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Response.php';

class Request
{
    const IN_PROGRESS_SIGNAL = 'INPROGRESS';

    protected $_httpVersion = CURL_HTTP_VERSION_1_1;
    protected $_uri;
    protected $_backend;
    protected $_cacheTime = 0;
    protected $_lockTime = 1;
    protected $_response;
    protected $_requestString;
    protected $_requestTimeout = 10;
    protected $_requestTimeoutInMilliseconds = false; //Added in cURL 7.16.2. Available since PHP 5.2.3.

    public function __construct($backend)
    {
        $this->_backend = $backend;
    }

    /**
     * @version (float) 1.0 or 1.0
     */
    public function setHttpVersion($version)
    {
        if (!is_float($version)) {
            throw new \InvalidArgumentException('Http version parameter must be float type');
        }
        else if ($version === 1.0) {
            $this->_httpVersion = CURL_HTTP_VERSION_1_0;
        }
        else if ($version === 1.1) {
            $this->_httpVersion = CURL_HTTP_VERSION_1_1;
        }
        else {
            throw new \InvalidArgumentException(sprintf('Trying to setup invalid http version %s available: (1.0, 1.1)', $version));
        }
        return $this;
    }

    public function setRequestTimeout($timeout, $inMilliseconds = false)
    {
        $this->_requestTimeout = (int)$timeout;
        $this->_requestTimeoutInMilliseconds = (bool)$inMilliseconds;
        return $this;
    }

    public function setCacheTime($timeInSeconds)
    {
        $this->_cacheTime = (int)$timeInSeconds;
        return $this;
    }

    public function setLockTime($timeInSeconds)
    {
        $this->_lockTime = (int)$timeInSeconds;
        return $this;
    }

    public function setUri($uri)
    {
        $this->_uri = $uri;
        return $this;
    }

    public function isInProgress()
    {
        return $this->_response === self::IN_PROGRESS_SIGNAL;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function getRequestString()
    {
        return $this->_requestString;
    }

    public function init()
    {
        $cacheValue = $this->_getFromCache();

        if ($cacheValue === false) {
            $this->_lockRequest();
            $this->getFromOrigin();
            $this->_saveResultToCache();
        }
        else if ($cacheValue === self::IN_PROGRESS_SIGNAL) {
            $this->_response = self::IN_PROGRESS_SIGNAL;
        }
        else if (is_array($cacheValue)) {
            $this->_response = new Response($cacheValue['body'], $cacheValue['headers']);
            $this->_response->addHeader('X-Cache-Hit: yes');
        }
        else {
            throw new RuntimeException('Unknown data structure fetched from cache');
        }
    }

    public function getFromOrigin()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, $this->_httpVersion);
        curl_setopt($ch, CURLOPT_URL, $this->_uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        if ($this->_requestTimeoutInMilliseconds) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->_requestTimeout);
        }
        else {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_requestTimeout);
        }
        $this->_response = new Response;
        $response = curl_exec($ch);
        if ($response === false) {
            $httpVersion = ($this->_httpVersion === CURL_HTTP_VERSION_1_0) ? '1.0' : 1.1;
            $this->_response
                ->addHeader("HTTP/$httpVersion 408 Request Time-out");
        }
        else {
            $headerLength = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            $body = substr($response, $headerLength);
            $headers = explode("\n", trim(substr($response, 0, $headerLength)));

            $this->_response
                ->setBody($body)
                ->addHeaders($headers);
        }
        $this->_requestString = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        curl_close($ch);
    }

    protected function _getFromCache()
    {
        return $this->_backend->load($this->_generateKeyForRequest());
    }

    /**
     * To mark request as locked we are storing in cache NULL value assigned to request key
     */
    protected function _lockRequest()
    {
        if ($this->_lockTime === 0) {
            return;
        }
        $this->_backend->save(
            self::IN_PROGRESS_SIGNAL,
            $this->_generateKeyForRequest(),
            array(),
            $this->_lockTime
        );
    }

    protected function _saveResultToCache()
    {
        $this->_backend->save(
            $this->_response->toArray(),
            $this->_generateKeyForRequest(),
            array(),
            $this->_cacheTime
        );
    }

    /**
     * @returns unique key for request
     */
    protected function _generateKeyForRequest()
    {
        return 'proxy_' . md5($this->_uri);
    }
}
