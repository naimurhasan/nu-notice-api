<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('client_id.json');
$client->addScope(Google_Service_Drive::DRIVE);

if (file_exists("credentials.json")) {
	print_file_list();
} else {
	redirect_for_auth();
}


function redirect_for_auth(){
  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/site/nu_notification/drive_upload/auth_callback.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}


function print_file_list(){
	 global $client;
	 $access_token = (file_get_contents("credentials.json"));
	 $client->setAccessToken($access_token);
	 if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    $drive_service = new Google_Service_Drive($client);
    $files_list = $drive_service->files->listFiles(array())->getFiles(); 
    echo "<PRE>";
    echo json_encode($files_list, JSON_PRETTY_PRINT);
}