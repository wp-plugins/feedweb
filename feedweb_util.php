<?php 

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once( ABSPATH.'wp-load.php');

function PrepareParam($param)
{
	if (strlen ($param) == 0)
		return "";

	$param = base64_encode($param);
	$param = str_replace("+", "-", $param);
	$param = str_replace("=", "~", $param);
	$param = str_replace("/", "_", $param);
	return $param;
}

function GetPostCode($id)
{
	$post = get_post($id);
	$url = PrepareParam(get_permalink($id));
	$name = PrepareParam($post->post_title);
	$user = PrepareParam(get_userdata($post->post_author)->display_name);
	
	if ($url != "" && $name != "" && $user != "")
		return $url."|".$name."|".$user;
	return null;
}

function InsertPac($pac, $id)
{
	global $wpdb;
	$query = "SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id=$id AND meta_key='feedweb_pac'";
	$count = $wpdb->get_var($query);
	if ($count > 0)
		return false;
	
	$query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ($id, 'feedweb_pac', '$pac')";
	$wpdb->query($query);
	return true;
}

function RemovePac($id)
{
	global $wpdb;
	$query = "DELETE FROM $wpdb->postmeta WHERE post_id=$id AND meta_key='feedweb_pac'";
	$result = $wpdb->query($query);
}

function GetPac($id)
{
	global $wpdb;
	$query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id=$id AND meta_key='feedweb_pac'";
	return $wpdb->get_var($query);
}

function SetFeedwebOptions($data)
{
	global $wpdb;
	
	$query = "DELETE FROM $wpdb->usermeta WHERE LIKE 'feedweb%%'";
	$wpdb->query($query);
	
	$id = wp_get_current_user()->ID;
	foreach ($data as $key => $value)
	{
		$query = "INSERT INTO $wpdb->usermeta (user_id, meta_key, meta_value) ".
			"VALUES ($id, 'feedweb_$key', '$value')";
		
		$result = $wpdb->query($query);
		if ($result == false)
			return false;
	}
	return true;
}

function GetFeedwebOptions()
{
	// Default widgets options: Engglish, 400 pix., Hide from the Main Page
	$data = array("language" => "en", "mp_widgets" => "0", "widget_width" => "400", "delay" => "2", "copyright_notice" => "0");	// Set default data
	
	global $wpdb;
	$query = "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'feedweb%%'";
	$rows = $wpdb->get_results($query);
	foreach ( $rows as $row )
	{
		$key = substr($row->meta_key, 8);
		if ($key != false && $key != "")
			$data[$key] = $row->meta_value;
	}
	return $data;
}

function GetPostAge($id)
{
	if (phpversion() < "5.3")
		return 0;
	
	try
	{
		$post = get_post($id);
		$now = new DateTime("now");
		$date = new DateTime($post->post_date);
		$interval = $date->diff($now);
		return $interval->days;
	}
	catch(Exception $e)
	{
	}
	return 0;
}


function GetMaxPostAge()
{
	return 30;
}


function IsRTL($language)
{
	if ($language == 'he' || $language == 'ar')
		return true;
	return false;
}

function CreateGuid() 
{
	// The field names refer to RFC 4122 section 4.1.2
	return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
			mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
			mt_rand(0, 65535), // 16 bits for "time_mid"
			mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
			bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
			// 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
			// (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
			// 8 bits for "clk_seq_low"
			mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
	);
}

function GetFeedwebUrl()
{
	$url = get_bloginfo('url');
	$host = parse_url($url, PHP_URL_HOST);
	if ($host == "localhost")
		return "http://localhost/FeedwebServer/";
	return "http://wpblogs.feedweb.net/";
}

?>