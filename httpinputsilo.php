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

/**
 * When called this class retrieves all HTTP HEADERS, GET and POST parameters
 *
 * @author Dustin Demuth <mail@dmth.eu>
 */
class httpinputsilo {

    //put your code here

    private $getrequest; //The data which was transmitted as HTTP Get to the Service
    private $postrequest; //The data which was transmitted as HTTP Post to the Service
    private $headers; //The Headers which have been transmitted to the Service
    private $config;
    private $endpointurl;
    
   function __construct($config, $endpointurl) {
       $this->config = $config;
       $this->endpointurl = $endpointurl;
       $this->retrieveRequest();
   }
    
    function retrieveRequest() {
        // see http://php.net/manual/en/function.getallheaders.php
        if (!function_exists('getallheaders')) {

            function getallheaders() {
                $headers = '';
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }

        }

        $config = $this->config;
        $this->getrequest = filter_input_array(INPUT_GET);
        $this->postrequest = filter_input_array(INPUT_POST);
        $this->headers = getallheaders();
        
        //sanitize the arrays, as the endpoint which was called might still be stored in it.
        //this is necessary as http://myurl/service was parsed to http://myurl/?endpoint=service
        //we don't want this information in our GET array.
        //
        //unset($this->getrequest[$config['httpgetparametername']]);
        // AAAAAAand this is wrong... information is lost because:
        // when application/test.html is called,
        // all would be deleted but test.html might still be needed...
        // the correct way would be to erase the called endpoint from this string.
        // remove the endpoint from the string.
        $s = preg_replace('/'.preg_quote($this->endpointurl, '/').'/', '', $this->getrequest[$config['httpgetparametername']], 1);
        $this->getrequest[$config['httpgetparametername']] = $s;
        unset($config);
    }

    function returnGet() {
        return $this->getrequest;
    }

    function returnPost() {
        return $this->postrequest;
    }

    function returnHeaders() {
        return $this->headers;
    }

}
