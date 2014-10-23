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
        'pgp.password'  => '', //If the private key requires a password, it can be entered here.
        'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
        'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
        'pgp.keyid' => '',//The Key-ID of this Service-Endpoint.
        'pgp.keydir' => '', //Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
        'application.serviceendpoint.url' => 'http://127.0.0.1/service', //The Url of the counterpart of this application
        'application.require.receipt' => '',
        'postbox.address'   => '',
    ],
    [   //Service Endpoint
        'endpoint.url'  => '/service',
        'endpoint.role' => 'service',//This is a server side
        'pgp.password'  => '', //If the private key requires a password, it can be entered here.
        'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
        'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
        'pgp.keyid' => '',//The Key-ID of this Service-Endpoint.
        'pgp.keydir' => '', //Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
        'service.url' => 'http://maverick.arcgis.com/arcgis/services/USA/MapServer/WMSServer', //Which service shall receive the request?
        //bsp i.e. http://maverick.arcgis.com/arcgis/services/USA/MapServer/WMSServer?SERVICE=WMS&REQUEST=GetMap&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=default&VERSION=1.3.0&LAYERS=0&WIDTH=1920&HEIGHT=752&CRS=EPSG:4326&BBOX=25.0536602794336,-127.92427191904663,49.803660279433615,-64.73278255734446
        'service.type' => '', //not required
        'service.MITM.urlReplacement' => TRUE,
        'service.require.receipt' => '',
        'postbox.address'   => '',
    ],
    [   //Service Endpoint
        'endpoint.url'  => '/wps',
        'endpoint.role' => 'service',//This is a server side
        'pgp.password'  => '', //If the private key requires a password, it can be entered here.
        'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
        'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
        'pgp.keyid' => '',//The Key-ID of this Service-Endpoint.
        'pgp.keydir' => '', //Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
        'service.url' => 'http://demo.opengeo.org/geoserver/wps', //Which service shall receive the request?
        'service.type' => '', //not required
        'service.MITM.urlReplacement' => TRUE,
        'service.require.receipt' => '',
        'postbox.address'   => '',
    ],
    [   //Application Endpoint
        'endpoint.url'  => '/wpsapp',
        'endpoint.role' => 'application',//This is the client side
        'pgp.password'  => '', //If the private key requires a password, it can be entered here.
        'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
        'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
        'pgp.keyid' => '',//The Key-ID of this Service-Endpoint.
        'pgp.keydir' => '', //Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
        'application.serviceendpoint.url' => 'http://127.0.0.1/wps', //The Url of the counterpart of this application
        'application.require.receipt' => '',
        'postbox.address'   => '',
    ]

];
