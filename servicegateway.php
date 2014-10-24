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
require_once('gateway.php');
/**
 * Description of servicegateway
 *
 * @author Dustin Demuth <mail@dmth.eu>
 */
class servicegateway extends gateway{
    function startGW(){
        //echo "ServiceGW Start Submitted by Post: ";
        //print_r($this->httpinputsilo->returnPost());
        //echo "\n";
        return $this->httpinputsilo->returnPost();
    }
    
    function connect($serviceurl, $query){
        $header = $query['headers'];
        $get    = $query['get'];
        $post   = $query['post'];
        
        $get_r = "";
        foreach ( $get as $key => $value){
            $get_r .= urlencode($key)."=".urlencode($value);
            if (!($value === end($get))){
                $get_r .= "&";            
            }
        }
        
        $url = $serviceurl."?".$get_r;
        //ToDo Post
        if ($post == ""){
            $response = \Httpful\Request::get($url)->send();
        }else{
            //ToDo
            $response = \Httpful\Request::post($url,$post,'application/x-www-form-urlencoded')->send();
        }
        return $response;
    }
}