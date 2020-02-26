<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('client_id.json');
$client->addScope(Google_Service_Drive::DRIVE);

if (file_exists("credentials.json")) {
  upload_file();
} else {
  redirect_for_auth();
}


function redirect_for_auth(){
  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/site/nu_notification/drive_upload/auth_callback.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

function upload_file(){
   global $client;
   $access_token = (file_get_contents("credentials.json"));
   $client->setAccessToken($access_token);
   if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    $drive_service = new Google_Service_Drive($client);
    $fileMetadata = new Google_Service_Drive_DriveFile(array(
    'name' => 'test_upload.text'));
    $content = file_get_contents(__DIR__.'/test_upload.txt');
    $file = $drive_service->files->create($fileMetadata, array(
    'data' => $content,
    'mimeType' => 'text/plain',
    'uploadType' => 'multipart',
    'fields' => 'id'));
    printf("File ID: %s\n", $file->id);
}