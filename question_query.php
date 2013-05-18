<?php

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( 'feedweb_util.php');

if (!current_user_can('manage_options'))
	wp_die(__("You are not allowed to be here"));
else
	GetQuestionList();
 
function GetQuestionList()
{
	// Get Language
	$lang = $_GET["lang"];
	$site = PrepareParam(site_url());
	$url = GetFeedwebUrl()."FBanner.aspx?action=gql&lang=$lang&site=$site";
	
	$response = wp_remote_get ($url, array('timeout' => 60));
	if (is_wp_error ($response))
		return;
		
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
		{
			$questions = ReadQuestionList($dom->documentElement);
			if ($questions == null)
				return;
				
			foreach ($questions as $id => $text)
				echo "<option value='$id'>$text</option>";
		}
}
?>