<?php

date_default_timezone_set('Pacific/Auckland');
ini_set('date.timezone', 'Pacific/Auckland');

$use_ftp = false;   // if you would like to get the latest image of an FTP server then set this to true
$image_filename = "intrudor.jpg";

$facebook_token_filename = "token.txt"; // poormans database
$facebook_app_id = "111111111111111";   // Facebook APP ID
$facebook_app_secret = "1111111111111111111111111111111111111111"; // Facebook APP Secret
$facebook_script_url = "http://localhost/facebook/index.php";
$facebook_scope      = "publish_stream,user_photos";  // see here for more https://developers.facebook.com/docs/facebook-login/permissions/v2.0#reference-extended-profile

if($use_ftp){
  $ftp_directory = "./security/video/grabs/";
  $ftp_username  = 'security';
  $ftp_password  = 'password';
  $ftp_host      = '10.239.149.102;

  // connect
  $conn = ftp_connect($ftp_host);
  ftp_login($conn, $ftp_username, $ftp_password);

  // get list of files on given path
  $files = ftp_nlist($conn, $ftp_directory);

  $mostRecent = array(
      'time' => 0,
      'file' => null
  );

  foreach ($files as $file) {
      // get the last modified time for the file
      $time = ftp_mdtm($conn, $file);

      if ($time > $mostRecent['time']) {
          // this file is the most recent so far
          $mostRecent['time'] = $time;
          $mostRecent['file'] = $file;
      }
  }

  ftp_get($conn, $image_filename, $mostRecent['file'], FTP_BINARY);
  ftp_close($conn);
}

 /**
 * @package Upload photo to facebook via php using facebook-php-sdk
 * @version 1.0.0
 * @author Shoaib Ali
 */
 ini_set('display_errors', 1);
 error_reporting(E_ALL);

 // make sure you run composer install
 require 'vendor/autoload.php';

 $facebook = new Facebook(array(
       'appId' => $facebook_app_id, 
       'secret' => $facebook_app_secret, 
       "cookie" => false,
       'fileUpload' => true,
       'allowSignedRequest' => false
 ));

 $user_id = $facebook->getUser();


 if( ($user_id == 0 || $user_id == "") && !file_exists($facebook_token_filename) ){  
 $login_url = $facebook->getLoginUrl(array(
  'redirect_uri' => $facebook_script_url,
  'scope' => $facebook_scope));
   header("Location: " . $login_url);
    // can also do it using javascript
    // echo "<script type='text/javascript'>top.location.href = '$login_url';</script>";
    exit();

 } else {

    try {
        

        if (file_exists($facebook_token_filename)) {
            // read the extended token from file (cheap way of avoiding a database)                   
            $access_token = file_get_contents($facebook_token_filename, FILE_USE_INCLUDE_PATH);
            $facebook->setAccessToken($access_token);  
            $user_profile = $facebook->api('/me/albums');

        } else {
            // we are here because we don't have an access token
            $facebook->setExtendedAccessToken(); // Set access token to 60 days
            $access_token = $facebook->getAccessToken();
            // write the access token to the file            
            file_put_contents($facebook_token_filename, $access_token);
            //$facebook->setAccessToken($access_token);  
        }

         //get user album
         $albums = $facebook->api("/me/albums");
         $album_id = ""; 
         foreach($albums["data"] as $item){
          // how to do cover photo using API needs further investigation. Below doesn't work
          // Cover photos are no longer kept in an album afaik
          if($item["type"] == "cover_photo"){
            $album_id = $item["id"];
            break;
          }
         }

         //set timeline atributes         
         $args = array('message' => "!!!!!INTRUDER ALERT!!!! Shoaib's HOME surveillance - Intruder has been spotted");
         // TODO also support uploading videos and other image types png, gif etc.
         $args['image'] = new CURLFile($image_filename, 'image/jpeg') ;

         //upload photo to Facebook
         $data = $facebook->api("/me/photos", 'post', $args);
         $picture = $facebook->api('/'.$data['id']);

         echo "picture uploaded! check your facebook timeline";

    } catch (FacebookApiException $e) {
        // we are here because Facebook or the user of our app hates us :(
        // one of the three things has happened. 
        // 1. User changed their facebook password
        // 2. User de-authorized the app
        // 3. 60 days have passed therefore the token is no longer valid.

        // TODO Send an email to administrator or user to visit this script again in order to get new token
        $user_id = false;        
        // delete the token file since token is now useless
        unlink($facebook_token_filename);
         echo $e->getMessage();
        // send the user back so they login again and get a new token for 60 days
        //header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

 }



?>
