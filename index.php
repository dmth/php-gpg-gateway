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
require_once('transportcapsule.php');


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
         * S E R V I C E  - Gateway
         * ToDo: encapsulate this code into a function or similar. 
         */
        $silo = new httpinputsilo(); //retrieve http-input         
        $gw = new servicegateway($silo, $calledEndpoint);  //create a new gateway with access to the HTTP Parametersand configure it.

        $encodedquery = $gw->startGW()['post']; //We are only interested in the values which were posted to this gateway

        $request = $encodedquery[$config['contentname']]; // retrieve posted data
        $signature = $encodedquery[$config['signaturename']]; // retrieve posted signature of the data
        $publickey = $encodedquery[$config['publickeyname']]; // retrieve the public key
        //start GPG and encryption:
        $gpg = new gpgphelper();
        $gpg->init($calledEndpoint);
        $gpg->importpublickey($publickey);

        //Check if the signature was valid.
        if ($gpg->checkdetachedsigned($request, $signature) > 0) {
            $decodedrequest = $gw->decode($request); //de-base64, deserialise
            $response = $gw->connect($calledEndpoint['service.url'], $decodedrequest);
        } else {
            // The Signature of the Request was invalid!
            // Respond with an error!
            // ToDo: Error response
            exit(1);
        }

        // Response Object is a Httpful\Response Object which consists of body, raw_body and headers and raw_headers
        // also the original rquest is accessible within the response object.
        // we are interested in raw_headers and raw_body to create a response object.
        // ToDo: Response-Object might not exist due to errors!
        $responsearray = [
            'headers' => $response->_parseHeaders($response->raw_headers),
            'body' => $response->raw_body
        ];


        $resp = new transportcapsule();

        $resp->setContent($gw->encode($responsearray));
        $resp->setSignature($gpg->sign($resp->getContent()));
        $resp->setPublickey($gpg->exportpublickey());

        $gw->send($resp->serialise());
    } elseif (strcasecmp($calledEndpoint['endpoint.role'], $config['allowedendpointroles']['app']) == 0) {
        /*
         * A P P L I C A T I O N  - Gateway
         * ToDo: encapsulate this code into a function or similar. 
         */

        $silo = new httpinputsilo();
        $gw = new applicationgateway($silo, $calledEndpoint); //create a new gateway with access to the HTTP Parameters and configure it

        $request = $gw->startGW();

        //start GPG and encryption:
        $gpg = new gpgphelper();
        $gpg->init($calledEndpoint);



        $encodeddata = $gw->encode($request); //serialise an base64
        $signature = $gpg->sign($encodeddata);
        $publickey = $gpg->exportpublickey();


        //connect to the configured service-endpoint of this gateway, 
        //and send the data, the signature of the data and this gateways publickey
        $r = $gw->connect(array(
            $config['contentname'] => $encodeddata,
            $config['signaturename'] => $signature,
            $config['publickeyname'] => $publickey
        )); // this returns a http-response as a string r

        $resp = new transportcapsule();
        $resp->deserialise($r); //unserializes the string $r and parse into the transportcapsule.
        //ToDo this should not always be required
        $gpg->importpublickey($resp->getPublickey());

  
        //Check if the signature was valid.
        if ($gpg->checkdetachedsigned($resp->getContent(), $resp->getSignature()) > 0) {
            $responsearray = $gw->decode($resp->getContent());
            //nice now lets test if the data was signed by a trusted signature form the config file:
            $verify = $gpg->verify($resp->getContent(), $resp->getSignature());
            foreach ($verify as $signatureobject) {
                //ToDo: This might be a feature to inform the client wheter the Communication was successfull and secure.
                //if (in_array($signatureobject->getKeyFingerprint(), $calledEndpoint['application.pgp.accepted.keys']) && ($signatureobject->isValid() == TRUE)) {
                    //Modify HTTP-Headers to include this Trustinformation
                    //$responsearray['headers']['X-PGP-Signed-By'] = $signatureobject->getKeyFingerprint();
                    //$responsearray['headers']['X-PGP-Signed-On'] = $signatureobject->getCreationDate();
                    //$responsearray['headers']['X-PGP-Valid'] = $signatureobject->isValid();
                //}
            }
        } else {
            // The Signature of the Request was invalid!
            // Respond with an error!
            // ToDo: Error response
            exit(1);
        }

        // Response Object is a Httpful\Response Object which consists of body, raw_body and headers and raw_headers
        // also the original request is accessible within the response object.
        // we are interested in raw_headers and raw_body to create a response object.     
        
        // ToDo: Response-Object might not exist due to errors!
        // ToDo: Replace URLs with applicationgateway-url in Response
        //  If: 'service.MITM.urlReplacement' => TRUE
        //  Then: Replace all original-server-address-URLs with the URL of the application gateway.
        //  
        // ToDo: Recalculate Content-Length-Header after Replacement   
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
