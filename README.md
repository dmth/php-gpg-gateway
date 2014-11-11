php-gpg-gateway
===========

Connect two Webservices, encrypt and digitally sing the transported data inbetween with gpg.
Receive digitally signed E-Mail-Notifications which confirm, that the webservice has received your request.


Installation:
-------------
Requirements: Webserver + PHP + PEAR + Composer + GPG + E-mail-Account with SMTP access

1.) Install Crypt_GPG with PEAR
2.) Install PHP-Scripts
 - Clone the repository into the path of your webserver
3.) Run "composer install" in your script-directory
4.) Create a new directory which is not accessible by the public.
 - Use GPG to generate a new keyring in this directory.
 - Write down the path of this directory
 - Generate a new private gpg key. Currently the the php-scripts can not handle password-secured keys, so use no password
5.) Have a look at the files in the conf/ folder.
 - Rename smtp.example.conf.php to smtp.conf.php and fill in the details of your smtp-Account
 - Rename endpoints.conf.example.php to endpoints.conf.php and configure accoring to your needs.
 - Check the gpg-path in config.conf.php and correct if necessary.
6.) Check file-Permissions of the scripts.
7.) Start your webserver.

It's very likely that you need to do this procedure twice.
One time for the application gateway, the second time for the service gateway.