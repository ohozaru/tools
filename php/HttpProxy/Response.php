<?php
namespace HttpProxy;

class Response
{
    private $_headers = array();
    private $_body;

    public function __construct($body = null, array $headers = array())
    {
        $this->_body = $body;
        $this->addHeaders($headers);
    }

    public function addHeaders(array $headers)
    {
        foreach ($headers as $header) {
            $this->addHeader($header);
        }
        return $this;
    }

    public function addHeader($header)
    {
        $this->_headers[] = (string)$header;
        return $this;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function setBody($body)
    {
        $this->_body = (string)$body;
        return $this;
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function flush()
    {
        foreach ($this->getHeaders() as $header) {
            header($header);
        }

        if ($this->_body) {
            echo $this->_body;
        }
    }

    public function toArray()
    {
        return array(
            'headers' => $this->_headers,
            'body' => $this->_body,
        );
    }
}
