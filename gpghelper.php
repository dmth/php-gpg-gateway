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

require_once('Crypt/GPG.php'); //pear
/*
 * This Class Wraps the GPG-Library.
 * Current library regquired: Crypt_GPG
 */

class gpgphelper {

    protected $gpg;
    protected $endpoint;

    /*
     * init() returns a new instance of the crypto library.
     * @param $calledEndpoint the endpoint which was called (i.e. /service1 ) used to determine the correct configuration.
     * 
     */

    function init(array $calledEndpoint) {
        $config = include('config.conf.php');
        $gpgbinpath = $config['gpgbinpath'];

        $this->endpoint = $calledEndpoint;

        $keyringdirectory = __DIR__ . '/' . $this->endpoint['pgp.keydir'];

        unset($config);

        //Check if GPG exists and is accessible.
        if (!is_file($gpgbinpath)) {
            //ToDo Throw an Exeception
            error_log('gpgphelper ('.$this->endpoint['endpoint.url'].'): The binary for GPG could not be found in \'' . $gpgbinpath . '\' check your config and the settings for open_basedir in php.ini');
            exit(1); //Fail
        }

        //Check if $keyringdirectory exists
        if (!is_dir($keyringdirectory)) {
            //ToDo Throw an Exeception
            error_log('gpgphelper ('.$this->endpoint['endpoint.url'].'): The provided path \'' . $keyringdirectory . '\' does not point to a directory. Check if dir exists');
            exit(1); //Fail.
        }

        //Check if $keyringdirectory is Writeable.
        if (!is_writable($keyringdirectory)) {
            //ToDo Throw an Exeception
            error_log('gpgphelper ('.$this->endpoint['endpoint.url'].'): The provided path \'' . $keyringdirectory->path . '\' is not writable. Check if permissions are set correctly');
            exit(1); //Fail.
        }

        syslog(LOG_INFO, 'gpgphelper ('.$this->endpoint['endpoint.url'].'): Initialising new GPG instance in \'' . $keyringdirectory . '\'');
        #$this->gpg = new Crypt_GPG(array('homedir' => $keyringdirectory, 'binary' => $gpgbinpath, 'debug' => true));
        $this->gpg = new Crypt_GPG(array('homedir' => $keyringdirectory, 'binary' => $gpgbinpath));
    }

    function sign($data) {
        $pgpkeyid = $this->endpoint['pgp.keyid'];
        $pgppassphrase = $this->getpasswd();

        if (!is_string($pgpkeyid)) {
            //ToDo Throw an Exeception
            error_log('gpgphelper: The KeyID for the gpg-key is not configured');
            exit(1); //Fail.
        }

        if (!is_string($pgppassphrase)) {
            //ToDo Throw an Exeception
            error_log('gpgphelper: No password provided for this gpg-key');
            exit(1); //Fail.
        }

        syslog(LOG_INFO, 'gpgphelper: keyid: ' . $pgpkeyid);
        // ToDo handling the passphrase appears to be buggy. crypt_gpg reports that no passphrase was provided.
        // ugly circumvention is to use NO passprase.
        
        $this->gpg->addSignkey($pgpkeyid, $pgppassphrase);
        $detachedsig = $this->gpg->sign($data, Crypt_GPG::SIGN_MODE_DETACHED);
        return $detachedsig;
    }

    function checkdetachedsigned($data, $detached){
       $verify = $this->verify($data, $detached);
       $valid = 0;
        foreach ($verify as $signatureobject){
            syslog(LOG_INFO, 'gpgphelper ('.$this->endpoint['endpoint.url'].'): Data was signed by: '.$signatureobject->getUserId().
                    ' The signature is '.
                    (($signatureobject->isValid() == true)?'**valid**':'**invalid**') );
            
            if ($signatureobject->isValid() == true){
                $valid++;
            }
        }
        return $valid; 
    }
    
    function verify($data, $detached){
        return $this->gpg->verify($data, $detached);
    }
    
    function checkclearsigned($clearsigned){
        $verify = $this->gpg->verify($clearsigned);
        foreach ($verify as $signatureobject){
            syslog(LOG_INFO, 'gpgphelper ('.$this->endpoint['endpoint.url'].'): Data was signed by: '.$signatureobject->getUserId().
                    ' The signature is '.
                    (($signatureobject->isValid() == true)?'**valid**':'**invalid**') );
        }
        return $verify;
    }
       
    /*
     * returns the password of a pgp key
     */

    function getpasswd() {
        //ToDo support filebased approach
        $pass = $this->endpoint['pgp.password'];
        return $pass;
    }

    /*
     * Exports the public key of this gateway
     */
    function exportpublickey(){
        $pgpkeyid = $this->endpoint['pgp.keyid'];
        return $this->gpg->exportPublicKey($pgpkeyid, false);
    }
    
    function importpublickey($key){
        return $this->gpg->importKey($key);
    }
}
