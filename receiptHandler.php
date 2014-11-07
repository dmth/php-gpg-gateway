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
 * This class takes care of sending E-Mails (receipts)
 * to the partner of the communication flow.
 * @author Dustin Demuth <mail@dmth.eu>
 */
require 'vendor/autoload.php'; //composer

class receiptHandler {

    //put your code here

    private $receiver;
    private $sender;
    private $headers;
    private $message;
    private $subject;
    private $mailer;

    function __construct($toAddress, $fromAddress, $message, $mailconfigarray) {
        $this->receiver = $toAddress;
        $this->sender = $fromAddress;
        $this->message = $message;

        $this->mailer = new PHPMailer;
        $this->mailer->isSMTP();
        $this->mailer->Host = $mailconfigarray['smtp.Host'];
        $this->mailer->SMTPAuth = $mailconfigarray['smtp.Auth'];
        $this->mailer->Username = $mailconfigarray['smtp.Username'];
        $this->mailer->Password = $mailconfigarray['smtp.Password'];
        $this->mailer->SMTPSecure = $mailconfigarray['smtp.SMTPSecure'];
        $this->mailer->Port = $mailconfigarray['smtp.Port'];
    }

    /*
     * Sends an E-Mail to a Receiver
     */

    function sendReceipt($subject, $gpgpublickey = "") {
        $mail = $this->mailer;
        $mail->From = $this->sender;
        $mail->FromName = $this->sender;
        $mail->addAddress($this->receiver);
        $mail->Subject = $subject;
        $mail->Body = $this->message;
        
        //Attach PublicKey to the Message.
        if (!empty($gpgpublickey)){
            $mail->addStringAttachment($gpgpublickey,'publickey.asc','base64','application/pgp-keys') ;
        }
        if (!$mail->send()) {
            syslog(LOG_ERR, 'E-Mail could not be sent.');
            syslog(LOG_DEBUG, 'Mailer Error: ' . $mail->ErrorInfo);
        }
        // else {
        //    syslog(LOG_DEBUG, 'An E-Mail has been sent');
        //}
    }

}
