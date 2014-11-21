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
require_once('receiptHandler.php');


$config = include('conf/config.conf.php');

//load endpoints
//get endpoint from http-get
$invokedendpoint = filter_input(INPUT_GET, $config['httpgetparametername']);
//Try to find the invoked enpoint in the config
$calledEndpoint = get_endpoint_config($config['endpointsconfig'], $invokedendpoint);

if (!is_array($calledEndpoint)) {
    //ToDo Throw 404 or similar valid exception!
    echo "ERROR: The provided endpoint was not yet configured.";
} else if ($calledEndpoint['endpoint.disabled'] == FALSE) {
    //A config for the endpoint was found,
    //Determine the role of the enpoint.
    //This can be either 'service' or 'application', as defined in $config
    if (strcasecmp($calledEndpoint['endpoint.role'], $config['allowedendpointroles']['ser']) == 0) {
        /*
         * S E R V I C E  - Gateway
         * ToDo: encapsulate this code into a function or similar. 
         */
        $silo = new httpinputsilo($config, $calledEndpoint['endpoint.url']); //retrieve http-input         
        $gw = new servicegateway($silo, $calledEndpoint, $config['httpgetparametername']);  //create a new gateway with access to the HTTP Parametersand configure it.

        $encodedquery = $gw->startGW()['post']; //We are only interested in the values which were posted to this gateway

        $requestCapsule = new transportcapsule();
        $requestCapsule->setCapsule($encodedquery);
        $md5hashOfTC = md5($requestCapsule->serialise());
        $content = $requestCapsule->getContent(); // retrieve posted data
        $signature = $requestCapsule->getSignature(); // retrieve posted signature of the data
        $publickey = $requestCapsule->getPublickey(); // retrieve the public key
        $flags = $requestCapsule->getFlags(); // retrieve flags sent by the applicationgateway
        //start GPG and encryption:
        $gpg = new gpgphelper($config);
        $gpg->init($calledEndpoint);
        $rcvpublickey = $gpg->importpublickey($publickey);

        //Check if the signature was valid.
        if ($gpg->checkdetachedsigned($content, $signature) > 0) {
            $decodedcontent = $gw->decode($content); //de-base64, deserialise
            // ToDo: Content might be encrypted. This has to be checked. Then it has to be decrypted.
            //If the Requestor asked for a delivery receipt...
            $deliveryreceipt = "";
            if (in_array('DELIVERY_RECEIPT_REQUIRED', $flags)) {
                $deliveryreceipt = $gpg->signMessage($md5hashOfTC);
            }


            $response = $gw->connect($decodedcontent); //connect to configured endpoint (geoservice)
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
            'headers' => $response->_parseHeaders(
                    str_replace(
                            $calledEndpoint['endpoint.service.connecturl'],
                            $_SERVER['HTTP_HOST'].$calledEndpoint['endpoint.url'],
                            $response->raw_headers
                            )
                    ), //replace all occurences of the endpoint.service.connecturl with the URL of this endpoint
            //Todo Check whether / is needed."/"
            'body' => str_replace($calledEndpoint['endpoint.service.connecturl'],$_SERVER['HTTP_HOST'].$calledEndpoint['endpoint.url']."/",$response->raw_body) //replace all occurences of the endpoint.service.connecturl with the URL of this endpoint
                //the replacements are required to obfuscate the original service
        ];
        
        $responseCapsule = new transportcapsule();

        //If encryptiopn was required in the request.
        //ToDo: THE 'TRUE' ENFORCES ENCRYPTION ON THE BACKCHANNEL
        if (in_array('ENCRYPTION_IS_REQUIRED', $flags) && FALSE) {
            $responsecontent = $gpg->encrypt($gw->encode($responsearray), $rcvpublickey['fingerprint']);
            $responseCapsule->setFlag('MESSAGE_IS_ENCRYPTED');
        } else {
            $responsecontent = $gw->encode($responsearray);
        }


        $responseCapsule->setContent($responsecontent);
        $responseCapsule->setSignature($gpg->sign($responsecontent));
        $responseCapsule->setPublickey($gpg->exportPublickey());

        if (!empty($deliveryreceipt)) {
            $responseCapsule->setDeliveryReceipt($deliveryreceipt);
        }

        //Set Flags for the response.
        if (in_array('RECEPTION_RECEIPT_REQUIRED', $calledEndpoint['endpoint.policy'])) {
            $responseCapsule->setFlag('RECEPTION_RECEIPT_REQUIRED');
        }
        /*
         * The Application Gateway can not answer on Delivery-Receipt Requests.
         */
        //if (in_array('DELIVERY_RECEIPT_REQUIRED', $calledEndpoint['endpoint.policy'])) {
        //    $responseCapsule->setFlag('DELIVERY_RECEIPT_REQUIRED');
        //}
        
        $serialisedResponse = $responseCapsule->serialise();       
        $gw->send($serialisedResponse);

        // In Case the Service Gateway has set the RECEPTION_RECEIPT_REQUIRED Flag, send an Email to the Service-gateway E-Mail address and inform about MD5 Hash
        if (in_array('RECEPTION_RECEIPT_REQUIRED', $requestCapsule->getFlags())) {
            $md5hashOfTCresp = md5($serialisedResponse);
            $receiverMail = $gpg->getEmailAddress($calledEndpoint['pgp.keyid']);
            $senderMail = $calledEndpoint['endpoint.postbox.address'];

            $rH = new receiptHandler($receiverMail, $senderMail, $config['mail']);
            $rH->messageID($md5hashOfTCresp); //Start a New E-mail Thread!
            $rH->sendNotice($calledEndpoint, $gpg, $md5hashOfTCresp);

            unset($rH, $receiptMSG, $signedMSG);
        }

        // If the Requestor asked for a reception receipt...
        // This should normally be done by the Ultimate Receipient.
        // As the Ultimate Receipient does not support this feature, it is augmeneted by the Servcie Gateway
        if (in_array('RECEPTION_RECEIPT_REQUIRED', $flags)) {
            //If Required generate and send ReceptionReceipt          

            $receiverMail = $gpg->getEmailAddress($rcvpublickey['fingerprint']);
            $senderMail = $calledEndpoint['endpoint.postbox.address'];

            $rH = new receiptHandler($receiverMail, $senderMail, $config['mail']);
            $rH->inReplyTo($md5hashOfTC); //Reply to a Mail...
            $rH->sendReceptionReceipt($calledEndpoint, $gpg, $md5hashOfTC);
        }
    } elseif (strcasecmp($calledEndpoint['endpoint.role'], $config['allowedendpointroles']['app']) == 0) {
        /*
         * A P P L I C A T I O N  - Gateway
         * ToDo: encapsulate this code into a function or similar. 
         */
        $silo = new httpinputsilo($config, $calledEndpoint['endpoint.url']);
        $gw = new applicationgateway($silo, $calledEndpoint, $config['httpgetparametername']); //create a new gateway with access to the HTTP Parameters and configure it

        $request = $gw->startGW();

        //start GPG and encryption:
        $gpg = new gpgphelper($config);
        $gpg->init($calledEndpoint);

        //Create a new Transportcapsule which will get hold of content signature and publickey
        $requestCapsule = new transportcapsule();

        $content = $gw->encode($request);
        $requestCapsule->setContent($content);
        $requestCapsule->setSignature($gpg->sign($content));
        $requestCapsule->setPublickey($gpg->exportPublickey());
        $requestCapsule->setFlags($calledEndpoint['endpoint.policy']);

        //Use this MD5 Hash to validate the Receipts.
        $md5hashOfTC = md5($requestCapsule->serialise());

        //Send E-Mail to Application-Gateway E-Mail address to inform about the MD5Hash of the Request.
        if (in_array('RECEPTION_RECEIPT_REQUIRED', $requestCapsule->getFlags())) {
            //IF Required generate and send ReceptionReceipt

            $receiverMail = $gpg->getEmailAddress($calledEndpoint['pgp.keyid']);
            $senderMail = $calledEndpoint['endpoint.postbox.address'];

            $rH = new receiptHandler($receiverMail, $senderMail, $config['mail']);
            $rH->messageID($md5hashOfTC); //Start a New E-mail Thread!
            $rH->sendNotice($calledEndpoint, $gpg, $md5hashOfTC);

            unset($rH, $receiptMSG, $signedMSG);
        }

        //connect to the configured service-endpoint of this gateway, 
        //and send the transportcapsule away.
        $r = $gw->connect($requestCapsule->getCapsule()); // this returns an other transportcapsule as a string r

        $responseCapsule = new transportcapsule();
        $md5hashOfRespTC = md5($r);
        $responseCapsule->deserialise($r); //unserializes the string $r and parse into the transportcapsule.
        
        //ToDo this should not always be required
        $rsppublickey = $gpg->importpublickey($responseCapsule->getPublickey());

        $responsecontent = $responseCapsule->getContent();

        $deliveryreceipt = $responseCapsule->getDeliveryReceipt();

        if (!empty($deliveryreceipt)) {
            $validrcpt = $gpg->checkclearsigned($deliveryreceipt);
            if ($validrcpt > 0) {
                $receiverMail = $gpg->getEmailAddress($calledEndpoint['pgp.keyid']);
                $senderMail = $calledEndpoint['endpoint.postbox.address'];
                $rH = new receiptHandler($receiverMail, $senderMail, $config['mail']);
                //Differentiate now. If RECEPTION_RECEIPT_REQUIRED is set, An E-Mail was already sent. So we need to say in-Reply-to. Else: New E-mail Thread
                if (in_array('RECEPTION_RECEIPT_REQUIRED', $requestCapsule->getFlags())) {
                    $rH->inReplyTo($md5hashOfTC); //Reply to existing E-mail Thread!
                } else {
                    $rH->messageID($md5hashOfTC); //Start a New E-mail Thread!
                }
                $rH->sendDeliveryReceipt($calledEndpoint, $gpg, $deliveryreceipt);
                unset($rH);
            } else {
                //ToDo
            }
        }

        //Check if the signature was valid.
        if ($gpg->checkdetachedsigned($responsecontent, $responseCapsule->getSignature()) > 0) {

            // If we are here, there is at least one valid signature
            $verify = $gpg->verify($responsecontent, $responseCapsule->getSignature());

            //ok. The Response might be encrypted. We have to check this first.
            if (in_array('MESSAGE_IS_ENCRYPTED', $responseCapsule->getFlags())) {
                //Message was encrypted.
                //Decrypt
                $responsearray = $gw->decode($gpg->decrypt($responsecontent));
                //Add a header which stets that transport was 
                $responsearray['headers']['X-PGP-EncryptedResponse'] = "TRUE";
            } else {
                $responsearray = $gw->decode($responsecontent);
                $responsearray['headers']['X-PGP-EncryptedResponse'] = "FALSE";
            }

            // lets test if the data was signed by a trusted signature form the config file:
            foreach ($verify as $signatureobject) {
                //ToDo: This might be a feature to inform the client wheter the Communication was successfull and secure.
                if (in_array($signatureobject->getKeyFingerprint(), $calledEndpoint['pgp.accepted.keys']) && ($signatureobject->isValid() == TRUE)) {
                    //Modify HTTP-Headers to include this Trustinformation
                    $responsearray['headers']['X-PGP-SignatureOf'] = $signatureobject->getKeyFingerprint();
                    $responsearray['headers']['X-PGP-SignatureDate'] = $signatureobject->getCreationDate();
                    $responsearray['headers']['X-PGP-SignatureValid'] = $signatureobject->isValid();
                }
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
        // ToDo: The Service-Gateway url is obfuscated... it might be required to:
        //   Recalculate Content-Length-Header after Replacement   
        //return the headers which were retrieved from the http-client.
        
        //Replace traces of the Service Gateway URL in Headers
        foreach ($responsearray['headers'] as $key => $value) {
            //Todo Check whether / is needed
            $v = str_replace($calledEndpoint['endpoint.service.connecturl'],$_SERVER['HTTP_HOST'].$calledEndpoint['endpoint.url']."/",$value); //obfuscate gateway url
            header($key . ":" . $v);
        }
        
        //Replace traces of the Service Gateway URL in the Body
        //Todo Check whether / is needed
        $bdy = str_replace($calledEndpoint['endpoint.service.connecturl'],$_SERVER['HTTP_HOST'].$calledEndpoint['endpoint.url']."/",$responsearray['body']);//obfuscate gateway url
        //now send the received data to the client
        $gw->send($bdy);

        //if the service gateway asked for a reception-receipt we should send this now...
        if (in_array('RECEPTION_RECEIPT_REQUIRED', $responseCapsule->getFlags())) {
            //If Required generate and send ReceptionReceipt          

            $receiverMail = $gpg->getEmailAddress($rsppublickey['fingerprint']);
            $senderMail = $calledEndpoint['endpoint.postbox.address'];

            $rH = new receiptHandler($receiverMail, $senderMail, $config['mail']);
            $rH->inReplyTo($md5hashOfRespTC); //Reply to a Mail...
            $rH->sendReceptionReceipt($calledEndpoint, $gpg, $md5hashOfRespTC);
        }
    } else {
        //ToDo throw 50x or similar valid exception
        echo "ERROR: The endpoint " . $calledEndpoint['endpoint.url'] . " is misconfigured.\n The configured role " . $calledEndpoint['endpoint.role'] . " is unknown.";
    }
} else {
    //ToDo Throw 404 or similar valid exception!
    echo "ERROR: The provided endpoint exists, but was disabled.";
}

/*
 * get_endpoint_config
 * checks if the enpoint $invokedendpoint is present in the configuration
 */
function get_endpoint_config(array $endpointsconfig, $invokedendpoint) {
    //Split the invoked Enpoint into parts.
    // i.e. /application/test.html => "","application",test.html"
    $ep = explode("/",$invokedendpoint);
    
    //test if the invoked endpoint matches the config.
    foreach ($endpointsconfig as $value) {
       //with preceeding /
        if (strcmp($value['endpoint.url'], "/".$ep[1]) == 0) {
            return $value;
        }
        //without preceeding /
        if (strcmp($value['endpoint.url'], $ep[1]) == 0) {
            return $value;
        }
    }
    return null;
}
