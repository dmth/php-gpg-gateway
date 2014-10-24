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
        'content'   => '',
        'publickey' => '',
        'signature' => '',
        'flags'     => array()
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
    
    function setFlag($flag){
        array_push($this->capsule['flags'], $flag);
    }

    function setFlags(array $flags){
        $this->capsule['flags'] = $flags;
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
    
    function getFlags(){
        return $this->capsule['flags'];
    }
    
    function getFlagsString(){
        $str = '';
        
        foreach ($this->capsule['flags'] as $flag){
            $str .= $flag;
            if (!($flag === end($this->capsule['flags']))){
                $str .= ',';           
            }
        }
        return $str;
    }
    
    function getCapsule(){
        return $this->capsule;
    }
    
    function setCapsule(array $c){
        $this->setContent($c['content']);
        $this->setSignature($c['signature']);
        $this->setPublickey($c['publickey']);
        $this->setFlags($c['flags']);
    }
    
    function serialise(){
        return serialize($this->capsule);
    }
    
    function deserialise($str){
        $this->setCapsule(unserialize($str));
    }

}
