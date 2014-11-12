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

/*
 * This is an EXAMPLE configuration for your gateway.
 * You can use this as a Template.
 */

return
        [
            [   //Application Endpoint
                'endpoint.url' => '/application', // The Path on which the Endpoint should listen
                'endpoint.role' => 'application', //This is the client side     client <-> application-GW <-> service-GW <-> server
                'endpoint.disabled' => FALSE,
                'endpoint.policy' => ['ENCRYPTION_IS_REQUIRED', 'RECEPTION_RECEIPT_REQUIRED', 'DELIVERY_RECEIPT_REQUIRED'], // Flags which define the Policy of this gateway. Can be a combination of transporflags listet in config.conf.php
                'endpoint.postbox.address' => '',
                'endpoint.service.connecturl' => '', //The URL of the counterpart of this application (a Service Gateway) without protocol specification.
                'pgp.password' => '', //If the private key requires a password, it can be entered here.
                //pathphraseprotected keys are currently not supported.
                'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
                // also not supported right now.
                'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
                'pgp.keyid' => '', //The Key-ID of this Service-Endpoint. Determines Which Key from the Keyring should be used.
                'pgp.keydir' => '', //Relative Path starting from the script directory. Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
                'pgp.accepted.keys' => [''],
            ],
            [   //Service Endpoint
                'endpoint.url' => '/service', // The Path on which the Endpoint should listen
                'endpoint.role' => 'service', //This is a server side     client <-> application-GW <-> service-GW <-> server
                'endpoint.disabled' => FALSE,
                'endpoint.policy' => ['ENCRYPTION_IS_REQUIRED', 'RECEPTION_RECEIPT_REQUIRED'], // Flags which define the Policy of this gateway. Can be a combination of transporflags listet in config.conf.php
                'endpoint.postbox.address' => '',
                'endpoint.service.connecturl' => '',//The URL of the counterpart of this application (a Service Gateway) without protocol specification.
                'pgp.password' => '', //If the private key requires a password, it can be entered here.
                //pathphraseprotected keys are currently not supported.
                'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
                // also not supported right now.
                'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
                'pgp.keyid' => '', //The Key-ID of this Service-Endpoint. Determines Which Key from the Keyring should be used.
                'pgp.keydir' => '', //Relative Path starting from the script directory. Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
            ]
];
