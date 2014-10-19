<?php
error_reporting(E_ALL | E_STRICT);

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
    require_once('servicegateway.php');
    require_once('applicationgateway.php');


    $config=include('config.conf.php');
   
    //load endpoints
    //get endpoint from http-get
    $invokedendpoint = filter_input(INPUT_GET, $config['httpgetparametername']);
    //Try to find the invoked enpoint in the config
    $calledEndpoint = get_endpoint_config($config['endpointsconfig'], $invokedendpoint);
   
    if (!is_array($calledEndpoint)){
        echo "ERROR: The provided enpoint was not yet configured.";
    }else{
        //A config for the endpoint was found,
        //Determine the role of the enpoint.
        //This can be either 'service' or 'application', as defined in $config
        if(strcasecmp($calledEndpoint['endpoint.role'], $config['allowedendpointroles']['ser']) == 0){
            //echo "Success for ".$calledEndpoint['endpoint.url'].": Role found: ".$config['allowedendpointroles']['ser'];
            $silo = new httpinputsilo(); //retrieve http-input   
            $silo->retrieveRequest();
            $gw = new servicegateway(); //create a new gateway
            $gw->setSilo($silo);  // make http-input available for the gateway
            $gw->setEndpointConfig($calledEndpoint);
            //$gwresp = $gw->startClient(); //this is a httpful-response see http://phphttpclient.com/docs/namespace-Httpful.Response.html
            $encodedquery = $gw->startGW();
            $request = $gw->decode($encodedquery[$config['httppostparametername']]);
                       
            $response = $gw->connect($calledEndpoint['service.url'], $request);
            //return the headers which were retrieved from the http-client.
            foreach($response->headers->toArray() as $key => $value){
                header($key.":".$value);
            }

            //repond to the request
            echo($response);
            
        }elseif(strcasecmp($calledEndpoint['endpoint.role'], $config['allowedendpointroles']['app']) == 0){
            //echo "Success for ".$calledEndpoint['endpoint.url'].": Role found: ".$config['allowedendpointroles']['app'];
            $silo = new httpinputsilo();
            $silo->retrieveRequest();
            $gw = new applicationgateway(); //create a new gateway
            $gw->setSilo($silo);  // make http-input available for the gateway
            $gw->setEndpointConfig($calledEndpoint);
            $query = $gw->startGW();
            
            $response = $gw->connect(array($config['httppostparametername'] => $gw->encode($query)));
            
            //return the headers which were retrieved from the http-client.
            foreach($response->headers->toArray() as $key => $value){
               header($key.":".$value);
            }
            
            echo $response;
            
        }else{
            echo "ERROR: The endpoint ".$calledEndpoint['endpoint.url']." is misconfigured.\n The configured role ".$calledEndpoint['endpoint.role']." is unknown.";
        }
                
    }

    /*
     * get_endpoint_config
     * checks if the enpoint $invokedendpoint is present in the configuration
     */
    function get_endpoint_config(array $endpointsconfig, $invokedendpoint){
        foreach ($endpointsconfig as $value) {
            if(strcmp($value['endpoint.url'], $invokedendpoint) == 0){
                return $value;
            }
        }
        return null;
    }