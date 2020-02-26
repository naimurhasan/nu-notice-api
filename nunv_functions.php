<?php
/*
* TO KEEP MAIN PROGRAM FILES CLEAN
* THIS SEPARATE FUNCTION FILE HAS BEEN CREATED
*/
include_once __DIR__.'/config.php';
require __DIR__ . '/vendor/autoload.php';
include_once __DIR__.'/htmldomparser/HtmlWeb.php';
include_once __DIR__.'/htmldomparser/HtmlDocument.php';
include_once __DIR__.'/htmldomparser/HtmlWeb.php';
use simplehtmldom\HtmlWeb;
use simplehtmldom\HtmlDocument;

//initializing constants
define("NOTICE_JSON_FILENAME", __DIR__."/json_data/notices.json");
define("NOTICE_INFO_JSON_FILENAME", __DIR__."/json_data/notice_info.json");
define("NEW_NOTICE_SLCIE_FILENAME", __DIR__."/json_data/new_notice_slice.json");
define("NU_WEBSITE", "http://www.nu.ac.bd/");

//preparing google drive for use
$client = new Google_Client();
$client->setAuthConfig(__DIR__.'/client_id.json');
$client->setAccessType("offline");
$client->addScope(Google_Service_Drive::DRIVE);
if (file_exists("credentials.json")) {
   $access_token = (file_get_contents("credentials.json"));
   $client->setAccessToken($access_token);
   if ($client->isAccessTokenExpired()) {
    	//TODO: SEND NOTICE TO ADMIN
    }
    $drive_service = new Google_Service_Drive($client);
} else {
  //TODO: SEND NOTICE TO ADMIN
}


//checking difference
function notice_has_updated($old_notice, $current_notice){	
	return $old_notice !== $current_notice;
}

function download_pdf_foreach_notification_slice($notification_slice){
	global $drive_service;;
	foreach ($notification_slice as $value) {
		$notice_pdf = file_get_contents(NU_WEBSITE.$value['link']);

		$fileMetadata = new Google_Service_Drive_DriveFile(
			array(
	    		'name' => get_file_name($value["link"]),
	    		'parents'=>array('1SaN9UHGOKwShSZihRSOnCRdU_weSWDiY')));
	     $file = $drive_service->files->create($fileMetadata, array(
	    'data' => $notice_pdf,
	    'mimeType' => 'application/pdf',
	    'uploadType' => 'multipart',
	    'fields' => 'id'));
	    //printf("<pre>File ID: %s\n", $file->id);

		add_databse_record($value['link'], $file->id);
	}
}
/*
* This is the old downlod pdf function
* Which was used to save file at own host
* This function has got a new version
* For google drive, Look at Up
function download_pdf_foreach_notification_slice($notification_slice){
	foreach ($notification_slice as $value) {
		downloadUrlToFile(NU_WEBSITE.$value['link'], __DIR__."/nu_pdf/".get_file_name($value['link']));
		add_databse_record($value['link'], get_file_name($value['link']));
	}
}
*/
function get_file_name($link){
	$link_parts = explode("/", $link);
	return $link_parts[count($link_parts)-1];
}

// make a function to save if not exist
function save_notice_if_not_exist($notice_array){
	if(!file_exists(NOTICE_JSON_FILENAME)){
		file_put_contents(NOTICE_JSON_FILENAME, $notice_array);
	}
}

//checking if a link exist on notice array
function is_exist_on_notice($link_string, $notice_array){
	foreach ($notice_array as $value) {
		if($value["link"] === $link_string){
			return true;
		}
	}
	return false;
}

//save notice lenths for mobile quick verify
function save_notice_lenght_info($notice_json){
	global $drive_service;
	date_default_timezone_set('Asia/Dacca');
	$cur_time = time();
	$next_refresh = $cur_time + 60*10;

	$notice_info  = array(
		'length' => strlen($notice_json),
		'updated_timestamp' => $cur_time,
		'next_update_timestamp' => $next_refresh,
		'updated_on' => date("d/M/Y H:i:s", $cur_time),
		'next_refresh' => date("d/M/Y H:i:s", $next_refresh),
	);
	$notice_info_json =  json_encode($notice_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	file_put_contents(NOTICE_INFO_JSON_FILENAME, $notice_info_json);

	/*
	* update drive
	*/
	$emptyFile = new Google_Service_Drive_DriveFile();
	$file = $drive_service->files->update('1UOJfKpo1wm2GjlUG3rDLtDpkGl8F2f45', $emptyFile, array(
    'data' => $notice_info_json,
    'mimeType' => 'application/json',
    'uploadType' => 'multipart',
    'fields' => 'id'));
    //printf("\nFile ID: %s\n", $file->id);
}

//take two array of notice and returns the difference only
function new_notification_slice($old_data, $new_data){
	$old_notice_list = json_decode($old_data, true);
	$new_notice_list = json_decode($new_data, true);
	$recent_notice = array();
	foreach ($new_notice_list as $value) {
		if(!is_exist_on_notice($value["link"], $old_notice_list)){
			$recent_notice[] = $value;
		}
	}
	return $recent_notice;
}

function update_gdrive_json($json_data, $file_id){
	global $drive_service;
	/*
	* update drive
	*/
	$emptyFile = new Google_Service_Drive_DriveFile();
	$file = $drive_service->files->update($file_id, $emptyFile, array(
    'data' => $json_data,
    'mimeType' => 'application/json',
    'uploadType' => 'multipart',
    'fields' => 'id'));
    //printf("\nFile ID: %s\n", $file->id);
}

//making json array from html
function notice_as_json($web_page){
	global $mysqli;
	$html = new HtmlDocument();
	$html->load($web_page);

	//selecting table
	$archive_box = $html->find('.archive-box', 0);

	$notice = array();

	//going through every table row
	foreach ($archive_box->find('tbody > tr') as $element) {
		$single_notice = array();
		
		//adding info collected from webpage
		$single_notice["title"] = $element->find('td', 1)->plaintext;
		$single_notice["date"] = $element->find('td', 2)->plaintext;
		$single_notice["link"] = $element->find('td', 1)->find('.news-item > a', 0)->attr['href'];

		//adding drive download link in the json
		$stmt = $mysqli->prepare("SELECT dl_link FROM download_link WHERE nu_link = ?");
		$stmt->bind_param("s", $single_notice["link"]);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows !== 0){
			$row = $result->fetch_assoc();
			$single_notice["dl_link"] = $row["dl_link"];
		}
		$result->free();

		$notice[] = $single_notice;
	}

	$result = json_encode($notice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	return $result;
}

function downloadUrlToFile($url, $outFileName){   
    if(is_file($url)) {
        copy($url, $outFileName); 
    } else {
        $options = array(
          CURLOPT_FILE    => fopen($outFileName, 'w'),
          CURLOPT_TIMEOUT =>  28800, // set this to 8 hours so we dont timeout on big files
          CURLOPT_URL     => $url
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        curl_close($ch);
    }
}

function add_databse_record($nu_link, $dl_link){
	global $mysqli;
	$stmt = $mysqli->prepare("INSERT INTO download_link (nu_link, dl_link) VALUES (?, ?)");
	$stmt->bind_param("ss", $nu_link, $dl_link);
	$stmt->execute();

	if($stmt->affected_rows === 0) trigger_error("FAILED TO INSERT in DB", E_USER_WARNING);

	$stmt->close();
}	

