<?php
//currently it fatching notice from local
include_once __DIR__.'/nunv_functions.php';

//TODO: check last refreshed time die here if not atleast one minute passed

$page = file_get_contents(__DIR__."/page.txt");

$current_notice = notice_as_json($page);

//may be when doing everything with gdrive we won't need anymore to save on hosting if not exist anymore.
save_notice_if_not_exist($current_notice);

//save to notice length, last updated time, next update time
save_notice_lenght_info($current_notice);

$old_notice = file_get_contents(NOTICE_JSON_FILENAME);

$is_notice_updated = notice_has_updated($old_notice, $current_notice);

if($is_notice_updated){

	//slice only new
	$new_notification_slice = new_notification_slice($old_notice, $current_notice);

	//downlod the pdf
	// download_pdf_foreach_notification_slice($new_notification_slice);

	//TODO: save pdf download link to db

	//save only new to somewhere
	//so app will not have to download full json always
	$new_notification_slice_as_json = json_encode($new_notification_slice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	
	file_put_contents(NEW_NOTICE_SLCIE_FILENAME, $new_notification_slice_as_json);
	update_gdrive_json($new_notification_slice_as_json, '1iTGuT7vkY5auVBJGCmGFEbnoijY0oqY0');

	//generate again so the drive link will be added
	
	
	//save this current file
	//so we will not trigger has updated for this version of NU.
	$current_notice = notice_as_json($page);
	// update_gdrive_json($current_notice, '1qpriIPSvPOPjY67HOJ_OI7hyMNSqI_gf');
	if(strlen($current_notice)>2){
		file_put_contents(NOTICE_JSON_FILENAME, $current_notice);
	}
	//TODO: notification to phone

}

