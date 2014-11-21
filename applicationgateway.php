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
require_once('gateway.php');
/**
 * Description of applicationgateway
 *
 * @author Dustin Demuth <mail@dmth.eu>
 */
class applicationgateway extends gateway{
    
    function __construct(httpinputsilo $silo, $config, $httpgetparametername) {
       parent::__construct($silo, $config, $httpgetparametername);
    }
    function __destruct() {
        parent::__destruct();
    }
       
    //Connect to a servicegateway and send an encoded query
    function connect($encodedquery){
        $url = $this->endpointconfig['endpoint.service.connecturl'];
        
        //echo "ApplicationGW Connect Connect to URL: ".$url."\n";
        //echo "ApplicationGW Connect Sending: content=".$encodedquery['content']."\n";
        
        $response =  \Httpful\Request::post($url, $encodedquery, 'application/x-www-form-urlencoded')->send();
        
        return $response;
    }
}
