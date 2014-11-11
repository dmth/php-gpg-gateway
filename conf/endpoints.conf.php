<?php

return
        [
            [   //Application Endpoint
                'endpoint.url' => '/application',
                'endpoint.role' => 'application', //This is the client side
                'endpoint.disabled' => FALSE,
                'endpoint.policy' => ['ENCRYPTION_IS_REQUIRED', 'RECEPTION_RECEIPT_REQUIRED', 'DELIVERY_RECEIPT_REQUIRED'],
                'endpoint.postbox.address' => 'application@demuth.mobi',
                'endpoint.service.connecturl' => 'http://127.0.0.1/service', //The Url of the counterpart of this application
                'pgp.password' => 'application', //If the private key requires a password, it can be entered here.
                //pathphraseprotected keys are currently not supported.
                'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
                'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
                'pgp.keyid' => 'FD0E37311717109EE23745AF81507E398A2521AC', //The Key-ID of this Service-Endpoint.
                'pgp.keydir' => 'keys/application', //Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
                'pgp.accepted.keys' => ['42129EE0100273DAFCD8A5A76F4BE4C779469199'],
            ],
            [   //Service Endpoint
                'endpoint.url' => '/service',
                'endpoint.role' => 'service', //This is a server side
                'endpoint.disabled' => FALSE,
                'endpoint.policy' => ['ENCRYPTION_IS_REQUIRED', 'RECEPTION_RECEIPT_REQUIRED'],
                'endpoint.postbox.address' => 'service@demuth.mobi',
                'endpoint.service.connecturl' => 'http://maverick.arcgis.com/arcgis/services/USA/MapServer/WMSServer', //Which service shall receive the request?
                //i.e. http://maverick.arcgis.com/arcgis/services/USA/MapServer/WMSServer?SERVICE=WMS&REQUEST=GetMap&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=default&VERSION=1.3.0&LAYERS=0&WIDTH=1920&HEIGHT=752&CRS=EPSG:4326&BBOX=25.0536602794336,-127.92427191904663,49.803660279433615,-64.73278255734446
                'pgp.password' => 'service', //If the private key requires a password, it can be entered here.
                //pathphraseprotected keys are currently not supported.
                'pgp.pathtopassword' => '', //If the Password for your private pgp-key is stored in a file, this is the path to it
                'pgp.usepasswordfile' => FALSE, //determines whether passwordfile or password from config are used
                'pgp.keyid' => '42129EE0100273DAFCD8A5A76F4BE4C779469199', //The Key-ID of this Service-Endpoint.
                'pgp.keydir' => 'keys/service', //Has to be a writeable directory, but may not be reachable as an url i.e. like http://myUrl/keys
            ]
];
