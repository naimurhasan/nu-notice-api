<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('client_id.json');
$client->addScope(Google_Service_Drive::DRIVE);
$nu_folder_id = '1UeEjX02KIuICHuS8hYiausij7MHX6SAZ';

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
    $emptyFile = new Google_Service_Drive_DriveFile();
    $content = file_get_contents(__DIR__.'/test_update.txt');
    $file = $drive_service->files->update('1q4L8UyTuv-KraHk4xCXQ9l90Veiisy1x', $emptyFile, array(
    'data' => $content,
    'mimeType' => 'text/plain',
    'uploadType' => 'multipart',
    'fields' => 'id'));
    printf("File ID: %s\n", $file->id);
}