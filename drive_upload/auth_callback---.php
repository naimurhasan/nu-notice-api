<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('client_id.json');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/drive_upload/auth_callback.php');
$client->setAccessType('offline');
$client->addScope(Google_Service_Drive::DRIVE);
$client->setApprovalPrompt("force");
$client->setIncludeGrantedScopes(true);


if (! isset($_GET['code'])) {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
  $client->authenticate($_GET['code']);
  $access_token = $client->getAccessToken();
  file_put_contents("credentials.json", json_encode($access_token));

  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/drive_upload/chindex.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}