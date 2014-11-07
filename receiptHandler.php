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
require_once 'gpghelper.php';

class receiptHandler {

    //put your code here

    private $receiver;
    private $sender;
    private $headers;
    private $mailer;

    function __construct($toAddress, $fromAddress, $mailconfigarray) {
        $this->receiver = $toAddress;
        $this->sender = $fromAddress;

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

    function sendReceipt($subject, $message, $gpgpublickey = "", $attachment = "") {
        $mail = $this->mailer;
        $mail->From = $this->sender;
        $mail->FromName = $this->sender;
        $mail->addAddress($this->receiver);
        $mail->Subject = $subject;
        $mail->Body = $message;

        //Attach PublicKey to the Message.
        if (!empty($gpgpublickey)) {
            $mail->addStringAttachment($gpgpublickey, 'publickey.asc', 'base64', 'application/pgp-keys');
        }

        //attach a further thing
        if (!empty($attachment)) {
            $mail->addStringAttachment($attachment, 'deliveryreceipt.msg', 'base64');
        }

        if (!$mail->send()) {
            syslog(LOG_ERR, 'E-Mail could not be sent.');
            syslog(LOG_DEBUG, 'Mailer Error: ' . $mail->ErrorInfo);
        }
        // else {
        //    syslog(LOG_DEBUG, 'An E-Mail has been sent');
        //}
    }

    function messageID($messageID) {
        $this->mailer->addCustomHeader('Message-Id', $messageID);
    }

    function inReplyTo($messageID) {
        $this->mailer->addCustomHeader('In-Reply-To', $messageID);
    }

    function sendNotice($calledEndpoint, $gpg, $md5hash) {
        $receiptMSG = "The " . $calledEndpoint['endpoint.role'] . " Gateway forwarded your data according to its configuration";
        $receiptMSG .= "\n\n";
        $receiptMSG .= "The MD5-Hash of your request is:";
        $receiptMSG .= "\n";
        $receiptMSG .= $md5hash;
        $receiptMSG .= "\n";
        $receiptMSG .= "You should receive an E-Mail from the Service Gateway soon. Please compare the MD5 Hashes.";
        $receiptMSG .= "\n";
        $receiptMSG .= "\nTo verify the validity of this message, you might need to import the public key which is attached to this message.";

        $signedmsg = $gpg->signMessage($receiptMSG);

        $this->sendReceipt('Notice: Your data is on its way.',$signedmsg, $gpg->exportArmoredPublickey());
    }

    function sendReceptionReceipt($calledEndpoint, $gpg, $md5hash) {
        $msg = "The " . $calledEndpoint['endpoint.role'] . " Gateway received your data, and forwarded it according to its configuration.";
        $msg .= "\nThis implies that the ultimate recipient SHOULD have received the data.";
        $msg .= "\n\n";
        $msg .= "The MD5-Hash of the request the " . $calledEndpoint['endpoint.role'] . " Gateway has received is:";
        $msg .= "\n";
        $msg .= $md5hash;
        $msg .= "\n";
        $msg .= "You should have received an other E-Mail. Please compare the MD5 Hashes.";
        $msg .= "\n";
        $msg .= "\nTo verify the validity of this message, you might need to import the public key which is attached to this message.";

        $signedmsg = $gpg->signMessage($msg);

        $this->sendReceipt('Confirmation of receipt', $signedmsg, $gpg->exportArmoredPublickey());
    }

    function sendDeliveryReceipt($calledEndpoint, $gpg, $deliveryreceipt) {
        $receiptMSG = "A " . $calledEndpoint['endpoint.role'] . " Gateway received your data.";
        $receiptMSG .= "\nThis does not mean, that the data will be processed.";
        $receiptMSG .= "\nNevertheless, a Delivery-Receipt has been attached to this E-Mail.";
        $receiptMSG .= "\nYou can verify it, by running: gpg --verify deliveryreceipt.msg.";
        $receiptMSG .= "\n";
        $receiptMSG .= "\nTo verify the validity of this message, you might need to import the public key which is attached to this message.";
        
        $signedmsg = $gpg->signMessage($receiptMSG);
       
        $this->sendReceipt('The delivery of your data was confirmed', $signedmsg, $gpg->exportArmoredPublickey(), $deliveryreceipt);
    }

}
