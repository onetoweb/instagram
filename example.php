<?php

require 'vendor/autoload.php';

use Onetoweb\Instagram\Client;
use Onetoweb\Instagram\Token;

session_start();

$appId = 'app_id';
$appSecret = 'app_secret';
$redirectUri = 'app_redirect';

// instagram client
$client = new Client($appId, $appSecret, $redirectUri);

// instagram workflow
if (!isset($_SESSION['token']) and !isset($_GET['code'])) {
    
    // get authorization code
    printf('<a href="%1$s">%1$s</a>', $client->getAuthorizationLink());
    
} elseif (!isset($_SESSION['token']) and isset($_GET['code'])) {
    
    // get access token
    $client->requestAccessToken($_GET['code']);
    
    // store token in session
    $token = $client->getToken();
    
    $_SESSION['token']['token'] = $token->getToken();
    $_SESSION['token']['expires'] = $token->getExpires();
    
} elseif (isset($_SESSION['token'])) {
    
    // load token from session
    $token = new Token($_SESSION['token']['token'], $_SESSION['token']['expires']);
    
    $client->setToken($token);
}


// get instagram data
if ($client->getToken() !== null) {
    
    // get user data
    $user = $client->getUserData(['id', 'username']);
    
    // get user media
    $media = $client->getUserMedia(['id', 'caption', 'media_type', 'media_url', 'username', 'timestamp']);
}