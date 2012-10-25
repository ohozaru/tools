<?php
namespace HttpProxy;

class Response
{
    private $_headers = array();
    private $_body;

    public function __construct($body, array $headers = array())
    {
        $this->_body = $body;
        foreach ($headers as $header) {
            $this->addHeader($header);
        }
    }

    public function addHeader($header)
    {
        $this->_headers[] = $header;
        return $this;
    }

    public function getHeaders()
    {
        return $this->_headers;
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
