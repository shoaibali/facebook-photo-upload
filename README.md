facebook-photo-upload
=====================

Simple script that uses facebook php sdk to upload a photo

### Requirements

Webserver + PHP 5+ php_curl
Composer (see https://getcomposer.org/doc/00-intro.md#installation-nix)
A Facebook app, register it at developer.facebook.com

### Getting started

1. Open up index.php and configure all the parameters - primarliy these two
* Facebook App ID
* Facebook App Secret

2. Make sure the directory is writable be www-data user

``sudo chmod -R g+w /var/www/facebook
``sudo chown -R www-data: /var/www/facebook

3. Run ``sudo composer install`` to get facebook php sdk

3. Visit http://localhost/facebook/index.php and it should redirect you to login to facebook

4. Upon login you should see token.txt file being generated which will be used for subsequent postings to your Facebook.





