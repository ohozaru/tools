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

    protected $_uri;
    protected $_backend;
    protected $_cacheTime = 0;
    protected $_lockTime = 1;
    protected $_response;
    protected $_requestTimeout = 10000; //in miliseconds

    public function __construct($backend)
    {
        $this->_backend = $backend;
    }

    /**
     * @param $timeout (int) miliseconds
     */
    public function setRequestTimeout($timeout)
    {
        $this->_requestTimeout = (int)$timeout;
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
        curl_setopt($ch, CURLOPT_URL, $this->_uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->_requestTimeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:")); //override HTTP/1.1 Except: 100-continue header to not allow chunked transfer
        $this->_response = new Response;
        $response = curl_exec($ch);
        if ($response === false) {
            $this->_response
                ->addHeader('HTTP/1.1 408 Request Time-out');
        }
        else {
            $headerLength = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->_response
                ->setBody(substr($response, $headerLength))
                ->addHeaders(explode("\n", trim(substr($response, 0, $headerLength))));
        }
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
