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
 * Description of httpinputsilo
 *
 * @author Dustin Demuth <mail@dmth.eu>
 */
class httpinputsilo {
    //put your code here

    private $getrequest;
    private $postrequest;
    private $headers;
    //private $body;

    function retrieveRequest(){
        // see http://php.net/manual/en/function.getallheaders.php
        if (!function_exists('getallheaders')){
            function getallheaders(){
                $headers = '';
                foreach ($_SERVER as $name => $value){
                    if (substr($name, 0, 5) == 'HTTP_'){
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }
        //https://stackoverflow.com/questions/8945879/how-to-get-body-of-a-post-in-php
//        function getRequestBody() {
//            $rawInput = fopen('php://input', 'r');
//            $tempStream = fopen('php://temp', 'r+');
//            stream_copy_to_stream($rawInput, $tempStream);
//            rewind($tempStream);
//            return $tempStream;
//        }
                
           $config=include('config.conf.php');
           $this->getrequest    = filter_input_array(INPUT_GET);
           $this->postrequest   = filter_input_array(INPUT_POST);
           $this->headers       = getallheaders();
           //$this->body          = getRequestBody();        
                      
           //
           //sanitize the arrays, as the endpoint is still stored in it.
           unset($this->getrequest[$config['httpgetparametername']]);
           unset($config);
    }
    
    function returnGet(){
        return $this->getrequest;
    }
    
    function returnGetAsStringUrlEncoded(){
        $r = "";
        foreach ( $this->getrequest as $key => $value){
            $r .= urlencode($key)."=".urlencode($value);

            if (!($value === end($this->getrequest))){
                $r .= "&";            
            }
        }
        return $r;
    }
    
    function returnPost(){
        return $this->postrequest;
    }
        
    //function returnBody(){
    //    return $this->body;
    //}
    
    function returnHeaders(){
        return $this->headers;
    }
}
