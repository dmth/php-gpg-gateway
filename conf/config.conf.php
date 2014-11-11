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

return [
    /* $httpgetparametername
     * the name of the parameter which defines the enpoint. 
     * The webserver has to define this,
     * i.e. in nginx by 
     *   rewrite  ^(/.*)$ /index.php?endpoint=$1 last;
     */
    'gpgbinpath' => '/usr/bin/gpg',
    'httpgetparametername' => 'endpoint',
    'contentname' => 'content',
    'signaturename' => 'signature',
    'publickeyname' => 'pubkey',
    /*
     * Where are the enpoints configured?
     */
    'endpointsconfig' => include('endpoints.conf.php'),
    /*
     * Two roles are allowed as endpoints:
     */
    'allowedendpointroles' => [
        'ser' => 'service',
        'app' => 'application'
    ],
    'transporflags' => [ //Possible Flags within the Flags-Array of a Transportcapsule
        'DELIVERY_RECEIPT_REQUIRED'     => 1, //If this Flag is present, a Delivery Receipt has to be sent
        'RECEPTION_RECEIPT_REQUIRED'    => 2, //If this Flag is present, a Reception Receipt has to be sent
        'ENCRYPTION_IS_REQUIRED'        => 4, // If this Flag is Present, the content MUST be encrypted
        'MESSAGE_IS_ENCRYPTED'          => 8// If this Flag is present, the content-data is encrypted
    ],
    'mail' => include('smtp.conf.php')
];


