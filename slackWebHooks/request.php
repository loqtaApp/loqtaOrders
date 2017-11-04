<?php

class Request {

    protected $url;
    protected $headers = array();
    protected $data = array();
    protected $filePointer;
    protected $method = 'GET';
    protected $timeOut = 15;

    function __construct($url, $method = 'GET', $data = NULL) {
        $this->url = $url;
        $this->data = $data;
        $this->method = $method;
        $this->headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );
    }

    public function setURL($url) {
        $this->url = $url;
    }
    public function setData($data) {
        $this->data = $data;
    }
    public function setMethod($method) {
        $this->method = $method;
    }    
    public function putHeaders(&$ch) {
        $fp = fopen('php://temp/maxmemory:256000', 'w');
        if (!$fp) {
            die('could not open temp memory data');
        }
        fwrite($fp, $this->data);
        fseek($fp, 0);
        curl_setopt($ch, CURLOPT_PUT, 1);
        curl_setopt($ch, CURLOPT_INFILE, $fp); // file pointer
        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($this->data));
    }

    public function getHeaders(&$ch) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function postHeaders(&$ch) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
    }

    public function deleteHeaders(&$ch) {
        
    }

    public function setHeaders($headers) {
        $this->headers = $headers;
    }

    public function execute($arrayResponse = true) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $methodHeaders = (strtolower($this->method) . 'Headers');
        $this->$methodHeaders($ch);

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $xml_response = curl_exec($ch);
        curl_close($ch);
        return ($arrayResponse) ? json_decode($xml_response, true) : $xml_response;
    }

}
