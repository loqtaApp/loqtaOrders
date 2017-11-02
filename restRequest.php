<?php

//namespace Tidy;

class restRequest {

    protected $url;
    protected $verb;
    protected $requestBody;
    protected $requestLength;
    protected $username;
    protected $password;
    protected $acceptType;
    protected $responseBody;
    protected $responseInfo;
    protected $contentType;
    protected $async;
    protected $customHeaders;
    protected $customCurlParams;

    public function __construct($url = null, $verb = 'GET', $requestBody = null) {
        $this->url = $url;
        $this->verb = $verb;
        $this->requestBody = $requestBody;
        $this->requestLength = 0;
        $this->username = null;
        $this->password = null;
        $this->acceptType = 'application/json';
        $this->responseBody = null;
        $this->responseInfo = null;
        $this->contentType = 'application/json';
        $this->async = false;
        $this->customHeaders = $this->customCurlParams = array();


        if ($this->requestBody !== null) {
            $this->buildPostBody();
        }
    }

    public function flush() {
        $this->requestBody = null;
        $this->requestLength = 0;
        $this->verb = 'GET';
        $this->responseBody = null;
        $this->responseInfo = null;
        $this->async = false;
        $this->customHeaders = array();
    }

    public function execute() {
        $ch = curl_init();
        $this->setAuth($ch);

        try {
            switch (strtoupper($this->verb)) {
                case 'GET':
                    $this->executeGet($ch);
                    break;
                case 'POST':
                    $this->executePost($ch);
                    break;
                case 'PUT':
                    $this->executePut($ch);
                    break;
                case 'DELETE':
                    $this->executeDelete($ch);
                    break;
                default:
                    throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
            }
        } catch (InvalidArgumentException $e) {
            curl_close($ch);
            throw $e;
        } catch (Exception $e) {
            curl_close($ch);
            throw $e;
        }
    }

    public function buildPostBody($data = null) {
        $data = ($data !== null) ? $data : $this->requestBody;

        if (is_array($data)) {
            $data = http_build_query($data, '', '&');
            //throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
        }

        $this->requestBody = $data;
    }

    protected function executeGet($ch) {
        $this->doExecute($ch);
    }

    protected function executePost($ch) {
        if (!is_string($this->requestBody)) {
            $this->buildPostBody();
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
        curl_setopt($ch, CURLOPT_POST, 1);

        $this->doExecute($ch);
    }

    protected function executePut($ch) {
        if (!is_string($this->requestBody)) {
            $this->buildPostBody();
        }

        $this->requestLength = strlen($this->requestBody);

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, $this->requestBody);
        rewind($fh);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
        curl_setopt($ch, CURLOPT_PUT, true);

        $this->doExecute($ch);

        fclose($fh);
    }

    protected function executeDelete($ch) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $this->doExecute($ch);
    }

    protected function doExecute(&$curlHandle) {
        $this->setCurlOpts($curlHandle);
        $this->responseBody = curl_exec($curlHandle);
        $this->responseInfo = curl_getinfo($curlHandle);

        curl_close($curlHandle);
    }

    protected function setCurlOpts(&$curlHandle) {
        curl_setopt($curlHandle, CURLOPT_URL, $this->url);
        if ($this->async) {
            //curl_setopt($curlHandle, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 1);
        } else {
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        }
        $headers = array('Content-Type:' . $this->contentType, 'Accept: ' . $this->acceptType);
        if (sizeof($this->customHeaders) > 0) {
            $headers = array_merge($headers, $this->customHeaders);
        }
        // add headers to curl channel
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        if (sizeof($this->customCurlParams) > 0) {
            foreach ($this->customCurlParams as $key => $value) {
                curl_setopt($curlHandle, constant($key), $value);
            }
        }
    }

    protected function setAuth(&$curlHandle) {
        if ($this->username !== null && $this->password !== null) {
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }
    }

    public function getAcceptType() {
        return $this->acceptType;
    }

    public function setAcceptType($acceptType) {
        $this->acceptType = $acceptType;
    }

    public function getContentType() {
        return $this->contentType;
    }

    public function setContentType($contentType) {
        $this->contentType = $contentType;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getResponseBody() {
        return $this->responseBody;
    }

    public function getResponseInfo() {
        return $this->responseInfo;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getVerb() {
        return $this->verb;
    }

    public function setVerb($verb) {
        $this->verb = $verb;
    }

    public function setAsync($async) {
        $this->async = $async;
    }

    public function setCustomHeaders($customHeaders) {
        $this->customHeaders = is_array($customHeaders) ? array_merge($customHeaders, $this->customHeaders) : array();
    }

    public function getCustomHeaders() {
        return $this->customHeaders;
    }

    public function clearCustomHeaders() {
        $this->customHeaders = array();
    }

    public function setCustomeCurlParams($curlParams) {
        $this->customCurlParams = $curlParams;
    }

    public function clearCustomeCurlParams() {
        $this->customCurlParams = array();
    }

}
