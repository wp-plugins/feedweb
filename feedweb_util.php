<?php 

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once( ABSPATH.'wp-load.php');

function PrepareParam($param)
{
	if (strlen ($param) == 0)
		return "";

	$param = base64_encode(stripcslashes($param));
	$param = str_replace("+", "-", $param);
	$param = str_replace("=", "~", $param);
	$param = str_replace("/", "_", $param);
	return $param;
}

function ConvertHtml($str)
{
	return htmlspecialchars($str, ENT_QUOTES|ENT_HTML5);
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

function GetPostAuthorId($id)
{
	$post = get_post($id);
	return $post->post_author;
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

function GetBac($must_exist)
{
	global $wpdb;
	$query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id=0 AND meta_key='feedweb_bac'";
	$bac = $wpdb->get_var($query);
	if ($bac != null)
		return $bac;

    if ($must_exist)
		return null;

    // Register Site Domain command
	$root = PrepareParam(get_option('siteurl'));
	$query = GetFeedwebUrl()."FBanner.aspx?action=rsd&root=$root";
	$response = wp_remote_get ($query, array('timeout' => 30));
	if (is_wp_error ($response))
		return null;
	
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
			$code = $dom->documentElement->getAttribute("bac");
	
	if ($code == null || $code == "")	
		return null;
	
	$query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (0, 'feedweb_bac', '$code')";
	$result = $wpdb->query($query);
	if ($result == false)
		return null;
	
	return $code;
}

function SetFeedwebOptions($data)
{
	global $wpdb;
	
	$query = "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'feedweb%%'";
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


function GetUserCode($id, $must_exist)
{
	global $wpdb;
	$query = "SELECT meta_value FROM $wpdb->usermeta WHERE user_id=$id AND meta_key='user_code_feedweb'";
	$code = $wpdb->get_var($query);
	if ($code != null)
		return $code;
		
	if ($must_exist)
		return null;
	
	$query = GetFeedwebUrl()."FBanner.aspx?action=get&mode=temp";
	$response = wp_remote_get ($query, array('timeout' => 30));
	if (is_wp_error ($response))
		return null;
	
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
			$code = $dom->documentElement->getAttribute("newgid");
	
	if ($code == null || $code == "")	
		return null;
	
	$query = "INSERT INTO $wpdb->usermeta (user_id, meta_key, meta_value) VALUES ($id, 'user_code_feedweb', '$code')";
	$result = $wpdb->query($query);
	if ($result == false)
		return null;
	
	return $code;
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

function GetFeedwebUrl()
{
	$url = get_bloginfo('url');
	$host = parse_url($url, PHP_URL_HOST);
	if ($host == "localhost")
		return "http://localhost/FeedwebServer/";
	return "http://wpblogs.feedweb.net/";
}

function GetPostVotes($pac)
{
	$query = GetFeedwebUrl()."FBanner.aspx?action=gpd&pac=".$pac;
    $bac = GetBac(true);
    if ($bac != null)
		$query = $query."&bac=".$bac;
		
	$response = wp_remote_get ($query, array('timeout' => 30));
	if (is_wp_error ($response))
		return null;
	
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
		{
			$data['id'] = $dom->documentElement->getAttribute("id");
			$data['url'] = $dom->documentElement->getAttribute("url");
			$data['votes'] = $dom->documentElement->getAttribute("votes");
			$data['score'] = $dom->documentElement->getAttribute("score");
			return $data;
		}
	return null;
}

function GetLanguageList()
{
	$query = GetFeedwebUrl()."FBanner.aspx?action=gll";
	$response = wp_remote_get ($query, array('timeout' => 30));
	if (is_wp_error ($response))
		return null;
	
	$dom = new DOMDocument;
    if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
		{
		    $root = $dom->documentElement->getElementsByTagName("LANGUAGES");
		    if ($root != null && $root->length > 0)
			{
				$list = $root->item(0);
				$items = $list->getElementsByTagName("L");
				if ($items != null)
				{
				    $languages = array();
					foreach($items as $item)
				    {
						$code = $item->getAttribute("code");
						$text = $item->getAttribute("text");
						$name = $item->getAttribute("name");
						if ($name != $text)
						    $name .= " - ".$text; 
						$languages[$code] = $name;
				    }
					return $languages;
				}
			}
		}
	return null;
}


function UpdateBlogCapabilities()
{
    if (current_user_can('manage_options') == false) // Must be admin
		return;

    $bac = GetBac(false);
	if ($bac == null)
		return;
	
	// Request blog caps by Blog Access Code
    global $feedweb_blog_caps;
	$query = GetFeedwebUrl()."FBanner.aspx?action=cap&bac=$bac";
	$response = wp_remote_get ($query, array('timeout' => 30));
	if (is_wp_error ($response))
		return null;
	
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
		{
		    $feedweb_blog_caps = array();
		    $caps = $dom->documentElement->getElementsByTagName("CAP");
		    
			foreach ($caps as $cap)
			{
				$name = $cap->getAttribute("name");
				$used = intval($cap->getAttribute("used"));
				$limit = intval($cap->getAttribute("limit"));
								
				$value = array();
				$value["used"] = $used;
				$value["limit"] = $limit;
				$feedweb_blog_caps[$name] = $value;
			}
		}
}

function GetInsertWidgetStatus($id)
{
    $days = GetPostAge($id);
    if ($days > GetMaxPostAge())
    {
	    $format = __("Cannot insert widget into a post published %d days ago", "FWTD");
	    return sprintf($format, $days);
	}
    	
	global $feedweb_blog_caps;
	$cap = $feedweb_blog_caps["DW"];
	if ($cap["used"] >= $cap["limit"])
	{
		$format = __("You have created %d widgets in the last 24 hours. The daily limit is %d new widgets", "FWTD");
		return sprintf ($format, $cap["used"], $cap["limit"]);
	}
		
	$cap = $feedweb_blog_caps["MW"];
	if ($cap["used"] >= $cap["limit"])
	{
		$format = __("You have created %d widgets in the last 30 days. The monthly limit is %d new widgets", "FWTD");
		return sprintf ($format, $cap["used"], $cap["limit"]);
	}
    
	return null;
}

function WriteDebugLog($text)
{
    global $wpdb;
	$query = "INSERT INTO debug_log(log_time, log_text) VALUES(NOW(), '$text')";
	$wpdb->query($query);
}

?>