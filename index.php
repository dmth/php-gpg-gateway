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

        $requestCapsule = new transportcapsule();
        $requestCapsule->setCapsule($encodedquery);
        $md5hashOfTC = md5($requestCapsule->serialise());
        $content = $requestCapsule->getContent(); // retrieve posted data
        $signature = $requestCapsule->getSignature(); // retrieve posted signature of the data
        $publickey = $requestCapsule->getPublickey(); // retrieve the public key
        $flags = $requestCapsule->getFlags(); // retrieve flags sent by the applicationgateway
        //start GPG and encryption:
        $gpg = new gpgphelper();
        $gpg->init($calledEndpoint);
        $rcvpublickey = $gpg->importpublickey($publickey);

        //Check if the signature was valid.
        if ($gpg->checkdetachedsigned($content, $signature) > 0) {
            $decodedcontent = $gw->decode($content); //de-base64, deserialise
            // ToDo: Content might be encrypted. This has to be checked. Then it has to be decrypted.

            $response = $gw->connect($decodedcontent); //connect to configured endpoint (geoservice)

            if (in_array('RECEPTION_RECEIPT_REQUIRED', $flags)) {
                //IF Required generate and send ReceptionReceipt
                $receiptMSG = "The Service Gateway forwarded your request to a Geoservice and received a response from it.";
                $receiptMSG .= "\nThis implies that the ultimate recipient received your request.";
                $receiptMSG .= "\nYour Response should be on its way right now.";
                $receiptMSG .= "\n\n";
                $receiptMSG .= "The MD5-Hash of the request the Service Gateway has received is:";
                $receiptMSG .= "\n";
                $receiptMSG .= $md5hashOfTC;
                $receiptMSG .= "\n";
                $receiptMSG .= "You should have received an other E-Mail. Please compare the MD5 Hashes.";
                $receiptMSG .= "\n";
                $receiptMSG .= "\nTo verify the validity of this message, you might need to import the Public-Key of the Service Gateway.";
                $receiptMSG .= "\nIt is attached to this message.";
                $receiverMail = $gpg->getEmailAddress($rcvpublickey['fingerprint']);
                $senderMail = $calledEndpoint['endpoint.postbox.address'];

                $signedMSG = $gpg->signMessage($receiptMSG);
                $rH = new receiptHandler($receiverMail, $senderMail, $signedMSG, $config['mail']);
                $rH->sendReceipt('Confirmation of receipt', $gpg->exportArmoredPublickey());
            }
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


        $responseCapsule = new transportcapsule();

        //If encryptiopn was required in the request.
        //ToDo: THE 'TRUE' ENFORCES ENCRYPTION ON THE BACKCHANNEL
        if (in_array('ENCRYPTION_IS_REQUIRED', $flags) || TRUE) {
            $responsecontent = $gpg->encrypt($gw->encode($responsearray), $rcvpublickey['fingerprint']);
            $responseCapsule->setFlag('MESSAGE_IS_ENCRYPTED');
        } else {
            $responsecontent = $gw->encode($responsearray);
        }


        $responseCapsule->setContent($responsecontent);
        $responseCapsule->setSignature($gpg->sign($responsecontent));
        $responseCapsule->setPublickey($gpg->exportPublickey());

        //Set Flags for the response.
        if (in_array('RECEPTION_RECEIPT_REQUIRED', $calledEndpoint['endpoint.policy'])) {
            $responseCapsule->setFlag('RECEPTION_RECEIPT_REQUIRED');
        }
        if (in_array('DELIVERY_RECEIPT_REQUIRED', $calledEndpoint['endpoint.policy'])) {
            $responseCapsule->setFlag('DELIVERY_RECEIPT_REQUIRED');
        }

        $gw->send($responseCapsule->serialise());
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

        //Create a new Transportcapsule which will get hold of content signature and publickey
        $requestCapsule = new transportcapsule();

        $content = $gw->encode($request);
        $requestCapsule->setContent($content);
        $requestCapsule->setSignature($gpg->sign($content));
        $requestCapsule->setPublickey($gpg->exportPublickey());
        $requestCapsule->setFlags($calledEndpoint['endpoint.policy']);

        //Use this MD5 Hash to validate the Receipts.
        $md5hashOfTC = md5($requestCapsule->serialise());
        syslog(LOG_DEBUG, "Application Gateway: Created new Transport Capsule with MD5Hash: ".$md5hashOfTC);
        
        
        //Send E-Mail to Application-Gateway E-Mail address to inform about the MD5Hash of the Request.
        if (in_array('RECEPTION_RECEIPT_REQUIRED', $requestCapsule->getFlags())) {
                //IF Required generate and send ReceptionReceipt
                $receiptMSG = "The Application Gateway will forwarded your request to a Service Gateway.";
                $receiptMSG .= "\n\n";
                $receiptMSG .= "The MD5-Hash of your request is:";
                $receiptMSG .= "\n";
                $receiptMSG .= $md5hashOfTC;
                $receiptMSG .= "\n";
                $receiptMSG .= "You should receive an E-Mail from the Service Gateway soon. Please compare the MD5 Hashes.";
                $receiptMSG .= "\n";
                $receiptMSG .= "\nTo verify the validity of this message, you might need to import the Public-Key of the Application Gateway.";
                $receiptMSG .= "\nIt is attached to this message.";
                $receiverMail = $gpg->getEmailAddress($calledEndpoint['pgp.keyid']);
                $senderMail = $calledEndpoint['endpoint.postbox.address'];

                $signedMSG = $gpg->signMessage($receiptMSG);
                $rH = new receiptHandler($receiverMail, $senderMail, $signedMSG, $config['mail']);
                $rH->sendReceipt('Your new request is beeing processed', $gpg->exportArmoredPublickey());
        }
        
        //connect to the configured service-endpoint of this gateway, 
        //and send the transportcapsule away.
        $r = $gw->connect($requestCapsule->getCapsule()); // this returns an other transporcapsule as a string r

        $responseCapsule = new transportcapsule();
        $responseCapsule->deserialise($r); //unserializes the string $r and parse into the transportcapsule.
        //ToDo this should not always be required
        $gpg->importpublickey($responseCapsule->getPublickey());

        $responsecontent = $responseCapsule->getContent();

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
