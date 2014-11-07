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

// This file contains the Config for the SMTP-Server See PHP-Mailer documentation for Reference
// 
// 
// AFTER REPLACING THE VALUES IN THIS FILE, DO NOT INCLUDE IT INTO YOUR VERSIONING SYSTEM, 
// AS YOU MIGHT LEAK YOUR ____PASSWORDS____

return [
    //The Following Config is required to connect to a SMTP-Server
    'smtp.Host' => 'smtp1.example.com', // Specify main and backup SMTP servers
    'smtp.Auth' => true, // Enable SMTP authentication
    'smtp.Username' => 'user@example.com', // SMTP username
    'smtp.Password' => 'secret', // SMTP password
    'smtp.SMTPSecure' => 'tls', // Enable TLS encryption, `ssl` also accepted
    'smtp.Port' => 587 // TCP port to connect to  
];
