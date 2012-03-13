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
	$data = array("language" => "en", "mp_widgets" => "0", "widget_width" => "400", "delay" => "2");	// Set default data
	
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

function IsRTL($language)
{
	if ($language == 'he' || $language == 'ar')
		return true;
	return false;
}

?>