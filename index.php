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
require 'vendor/autoload.php'; //composer
require_once('httpinputsilo.php');
require_once('servicegateway.php');
require_once('applicationgateway.php');
require_once('gpghelper.php');


$config = include('config.conf.php');

//load endpoints
//get endpoint from http-get
$invokedendpoint = filter_input(INPUT_GET, $config['httpgetparametername']);
//Try to find the invoked enpoint in the config
$calledEndpoint = get_endpoint_config($config['endpointsconfig'], $invokedendpoint);

if (!is_array($calledEndpoint)) {
    //ToDo Throw 404 or similar valid exception!
    echo "ERROR: The provided enpoint was not yet configured.";
} else {
    //A config for the endpoint was found,
    //Determine the role of the enpoint.
    //This can be either 'service' or 'application', as defined in $config
    if (strcasecmp($calledEndpoint['endpoint.role'], $config['allowedendpointroles']['ser']) == 0) {
        /*
         * Service - Gateway
         * ToDo: encapsulate this code into a function or similar. 
         */
        $silo = new httpinputsilo(); //retrieve http-input   
        $silo->retrieveRequest();
        $gw = new servicegateway(); //create a new gateway
        $gw->setSilo($silo);  // make http-input available for the gateway
        $gw->setEndpointConfig($calledEndpoint);
        
        //$gwresp = $gw->startClient(); //this is a httpful-response see http://phphttpclient.com/docs/namespace-Httpful.Response.html
        $encodedquery = $gw->startGW();
        
        $request = $encodedquery[$config['httppostparametername']];
        $signature = $encodedquery[$config['signaturename']];
        $publickey = $encodedquery[$config['publickeyname']];
        
        //syslog(LOG_INFO, $signature);
        //syslog(LOG_INFO, $publickey);
        
        //start GPG and encryption:
        $gpg = new gpgphelper();
        $gpg->init($calledEndpoint);
        $gpg->importpublickey($publickey);
        
        $decodedrequest = $gw->decode($request);
        
        //When checkdetachedsigned returns an array, at least one valid signature exists.
        if ($gpg->checkdetachedsigned($request, $signature) > 0){
            $response = $gw->connect($calledEndpoint['service.url'], $decodedrequest);
        }else{
            // The Signature of the Request was invalid!
            // Respond with an error!
            // ToDo: Error response
        }
        
        // Response Object is a Httpful\Response Object which consists of body, raw_body and headers and raw_headers
        // also the original rquest is accessible within the response object.
        // we are interested in raw_headers and raw_body to create a response object.          
        $responsearray = [
            'headers' => $response->_parseHeaders($response->raw_headers),
            'body' => $response->raw_body
        ];

        // ToDo: Replace Server-URL with applicationgateway-url in Response
        //  If: 'service.MITM.urlReplacement' => TRUE
        //  Then: Replace all original-server-address-URLs with the URL of the application gateway.
        //  
        // ToDo: Application-Gateway needs to send its URL with the request.
        // ToDo: Recalculate Content-Length-Header after Replacement
        // encode array and headers.
        $responseencoded = $gw->encode($responsearray);
        
        $responsesignature = $gpg->sign($responseencoded);
        $responsepublickey = $gpg->exportpublickey();
        
        
        //now send the encoded data to the client
        $gw->send($responseencoded);
    } elseif (strcasecmp($calledEndpoint['endpoint.role'], $config['allowedendpointroles']['app']) == 0) {
        /*
         * Application - Gateway
         * ToDo: encapsulate this code into a function or similar. 
         */

        $silo = new httpinputsilo();
        $silo->retrieveRequest();
        $gw = new applicationgateway(); //create a new gateway
        $gw->setSilo($silo);  // make http-input available for the gateway
        $gw->setEndpointConfig($calledEndpoint);
        $query = $gw->startGW();

        //start GPG and encryption:
        $gpg = new gpgphelper();
        $gpg->init($calledEndpoint);
        
        $encodeddata = $gw->encode($query);
        
        $signature = $gpg->sign($encodeddata);
        $publickey = $gpg->exportpublickey();
        
        
        //connect to the respective service-endpoint of this gateway
        $response = $gw->connect(array(
            $config['httppostparametername'] => $encodeddata,
            $config['signaturename'] => $signature,
            $config['publickeyname'] => $publickey
                ));

        // Response Object is a Httpful\Response Object which consists of body, raw_body and headers and raw_headers
        // also the original request is accessible within the response object.
        // we are interested in raw_headers and raw_body to create a response object.       

        #$verifiedresponse = $gpg->checkclearsigned($response);
        $verifiedresponse = $response; //TODO backchannel!
        
        $responsearray = $gw->decode($verifiedresponse);

        //return the headers which were retrieved from the http-client.
        foreach ($responsearray['headers'] as $key => $value) {
            header($key . ":" . $value);
        }

        //now send the received data to the client
        $gw->send($responsearray['body']);
    } else {
        //ToDo throw 50x or similar valid exception
        echo "ERROR: The endpoint " . $calledEndpoint['endpoint.url'] . " is misconfigured.\n The configured role " . $calledEndpoint['endpoint.role'] . " is unknown.";
    }
}

/*
 * get_endpoint_config
 * checks if the enpoint $invokedendpoint is present in the configuration
 */

function get_endpoint_config(array $endpointsconfig, $invokedendpoint) {
    foreach ($endpointsconfig as $value) {
        if (strcmp($value['endpoint.url'], $invokedendpoint) == 0) {
            return $value;
        }
    }
    return null;
}
