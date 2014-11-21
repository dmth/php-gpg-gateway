<?php

/*
 * The MIT License
 *
 * Copyright 2014 Dustin Demuth <mail@dmth.eu>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
require_once('httpinputsilo.php');
require 'vendor/autoload.php'; //composer

/**
 * Description of gateway
 *
 * @author Dustin Demuth <mail@dmth.eu>
 */
class gateway {

    protected $httpinputsilo;
    protected $endpointconfig;
    protected $httpgetparamname;

    function __construct(httpinputsilo $silo, $endpointconfig, $httpgetparamname){
        $this->setSilo($silo);
        $this->setEndpointConfig($endpointconfig);
        $this->httpgetparamname = $httpgetparamname;
        syslog(LOG_DEBUG, 'New '. $this->endpointconfig['endpoint.role']. ' Gateway: '. $this->endpointconfig['endpoint.url']);
    }
    
    function __destruct() {
        syslog(LOG_DEBUG, $this->endpointconfig['endpoint.role']. ' Gateway '. $this->endpointconfig['endpoint.url'] . ' was destroyed');
    }
    
    public function setSilo(httpinputsilo $silo) {
        $this->httpinputsilo = $silo;
    }

    public function setEndpointConfig($config) {
        $this->endpointconfig = $config;
    }

    //Read the Values from HTTP-Silo and retrun as an array.
    function startGW(){
        $headers = $this->httpinputsilo->returnHeaders();
        $getvalue = $this->httpinputsilo->returnGet();
        $postvalues = $this->httpinputsilo->returnPost();
        
        $query = [
            'headers'  => $headers,
            'get'   => $getvalue,
            'post'  => $postvalues
        ];
        
        return $query;
    }

    public function encode($queryarray) {
        $encoded = base64_encode(serialize($queryarray));
        return $encoded;
    }

    public function decode($serializedb64query) {
        $decoded = base64_decode($serializedb64query);
        return unserialize($decoded);
    }

    public function send($data) {
        echo $data;
    }

}
