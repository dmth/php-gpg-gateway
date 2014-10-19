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

return 
[
    [   //Application Endpoint
        'endpoint.url'  => '/application',
        'endpoint.role' => 'application',//This is the client side
        'pgp.password'  => '',
        'pgp.pathtopassword' => '',
        'pgp.usepasswordfile' => FALSE,
        'pgp.pathtokey.private' => 'keys/example.asc',
        'pgp.pathtokey.public' => 'keys/example.pub.asc',
        'application.serviceendpoint.url' => 'http://127.0.0.1/service', //The Url of the counterpart of this application
        'application.require.receipt' => '',
        'postbox.address'   => '',
    ],
    [   //Service Endpoint
        'endpoint.url'  => '/service',
        'endpoint.role' => 'service',//This is a server side
        'pgp.password'  => '',
        'pgp.pathtopassword' => '',
        'pgp.usepasswordfile' => FALSE,
        'pgp.pathtokey.private' => 'keys/example2.asc',
        'pgp.pathtokey.public' => 'keys/example2.pub.asc',
        'service.url' => 'http://maverick.arcgis.com/arcgis/services/USA/MapServer/WMSServer', //Which service shall receive the request?
        'service.type' => '', //not required
        'service.require.receipt' => '',
        'postbox.address'   => '',
    ]
];
