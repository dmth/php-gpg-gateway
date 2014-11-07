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

/**
 * A Transportobject which keeps hold of:
 * headers
 * post
 * get
 * body
 * publickey
 * signature
 * @author Dustin Demuth <mail@dmth.eu>
 */
class transportcapsule {

    //put your code here
    private $capsule = [
        'content' => '',
        'publickey' => '',
        'signature' => '',
        'flags' => array(),
        'deliveryreceipt' => '' //This field will only be used in Responses.
    ];

    function setContent($content) {
        $this->capsule['content'] = $content;
    }

    function setPublickey($pkey) {
        $this->capsule['publickey'] = $pkey;
    }

    function setSignature($signature) {
        $this->capsule['signature'] = $signature;
    }

    function setFlag($flag) {
        array_push($this->capsule['flags'], $flag);
    }

    function setFlags($flags) {
        $this->capsule['flags'] = $flags;
    }

    function setDeliveryReceipt($dr) {
        $this->capsule['deliveryreceipt'] = $dr;
    }     
    function getContent() {
        return $this->capsule['content'];
    }

    function getPublickey() {
        return $this->capsule['publickey'];
    }

    function getSignature() {
        return $this->capsule['signature'];
    }

    function getFlags() {
        return $this->capsule['flags'];
    }

    function getFlagsString() {

        if (!is_array($this->capsule['flags'])) {
            return $this->capsule['flags'];
        } else {
            $str = '';
            foreach ($this->capsule['flags'] as $flag) {
                $str .= $flag;
                if (!($flag === end($this->capsule['flags']))) {
                    $str .= ',';
                }
            }
            return $str;
        }
    }
    
    function getDeliveryReceipt() {
        return $this->capsule['deliveryreceipt'];
    }     
    
    function getCapsule() {
        return $this->capsule;
    }

    function setCapsule(array $c) {
        if (array_key_exists('content', $c)) {
            $this->setContent($c['content']);
        }
        if (array_key_exists('signature', $c)) {
            $this->setSignature($c['signature']);
        }
        if (array_key_exists('publickey', $c)) {
            $this->setPublickey($c['publickey']);
        }
        if (array_key_exists('flags', $c)) {
            $this->setFlags($c['flags']);
        }
        if (array_key_exists('deliveryreceipt', $c)) {
            $this->setDeliveryReceipt($c['deliveryreceipt']);
        }
    }

    function serialise() {
        return serialize($this->capsule);
    }

    function deserialise($str) {
        $this->setCapsule(unserialize($str));
    }

}
