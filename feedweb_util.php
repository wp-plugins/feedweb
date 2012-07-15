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

function SetSingleFeedwebOption($key, $value)
{
	global $wpdb;
	
	$query = "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='feedweb_$key'";
	$count = $wpdb->get_var($query);
	if ($count > 0)
		$query = "UPDATE $wpdb->usermeta SET meta_value='$value' WHERE meta_key='feedweb_$key'";
	else
	{
		$id = wp_get_current_user()->ID;
		$query = "INSERT INTO $wpdb->usermeta (user_id, meta_key, meta_value) VALUES ($id, 'feedweb_$key', '$value')";
	}
	$result = $wpdb->query($query);
	return $result != false;
}

function GetSingleFeedwebOption($key)
{
	global $wpdb;
	
	$query = "SELECT meta_value FROM $wpdb->usermeta WHERE meta_key='feedweb_$key'";
	return $wpdb->get_var($query);
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
	// Default widgets options: English, 400 pix., Hide from the Main Page
	$data = array("language" => "en", "mp_widgets" => "0", "widget_width" => "400", "delay" => "2", "copyright_notice" => "0", 
		"allow_edit" => "0", "front_widget_color_scheme" => "classic", "front_widget_height" => "400", "front_widget_hide_scroll" => "0");	
	
	global $wpdb;
	$query = "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'feedweb%%'";
	$rows = $wpdb->get_results($query);
	foreach ( $rows as $row )
	{
		$key = substr($row->meta_key, 8);
		if ($key != false && $key != "")
		{
			$data[$key] = $row->meta_value;
			if ($key == "language")
				$data["language_set"] = true;
		}
	}
	return $data;
}

function GetFeedwebOptionsCount()
{
	global $wpdb;
	$query = "SELECT COUNT(meta_value) FROM $wpdb->usermeta WHERE meta_key LIKE 'feedweb%%'";
	return $wpdb->get_var($query);
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
		return "http://localhost:49170/"; //"http://localhost/FeedwebServer/";
	return "http://wpblogs.feedweb.net/";
}

function GetFileUrl($file)
{
	// Return if already absolute URL
    if(parse_url($file, PHP_URL_SCHEME) != '') 
		return $file;		
	return GetFeedwebUrl().$file;
}

function ReadQuestionList($root)
{
	$list = $root->getElementsByTagName("QUESTIONS");
	if ($list->length == 0)
		return null;
		
	$questions = array();
	$list = $list->item(0)->getElementsByTagName("Q");
	for ($item = 0; $item < $list->length; $item++)
	{
		$question = $list->item($item);
		$id = $question->getAttribute("id");
		$text = $question->getAttribute("text");
		$index = $question->getAttribute("index");
		
		if ($index != null && $index != "")
			$questions[$index] = array($id, $text);
		else
			$questions[$id] = $text;
	}
	return $questions;
}

function GetPageData($pac, $info_mode)
{
	$query = GetFeedwebUrl()."FBanner.aspx?action=gpd&icon=edit&pac=".$pac;
	if ($info_mode == true)
		$query .= "&mode=info";
		
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
			$data['error'] = $dom->documentElement->getAttribute("error");
			if ($info_mode == true)
			{
				$data['url'] = $dom->documentElement->getAttribute("url");
				$data['img'] = $dom->documentElement->getAttribute("img");
				$data['lang'] = $dom->documentElement->getAttribute("lang");
				$data['title'] = $dom->documentElement->getAttribute("title");
				$data['brief'] = $dom->documentElement->getAttribute("brief");
				$data['author'] = $dom->documentElement->getAttribute("author");
				$data['author_id'] = $dom->documentElement->getAttribute("aid");
				$data['questions'] = ReadQuestionList($dom->documentElement);
			}
			else	// Votes / Score / Image
			{
				$data['image'] = $dom->documentElement->getAttribute("image");
				$data['votes'] = $dom->documentElement->getAttribute("votes");
				$data['score'] = $dom->documentElement->getAttribute("score");
			}
			return $data;
		}
	return null;
}

function GetLanguageList($all)
{
	$query = GetFeedwebUrl()."FBanner.aspx?action=gll";
	if ($all == true)
		$query .= "&all=true";
		
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
				if ($list->childNodes != null && $list->childNodes->length > 0)
				{
				    $languages = array();
					foreach($list->childNodes as $item)
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
		return null;

    $bac = GetBac(false);
	if ($bac == null)
		return null;
	
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
			$license = $dom->documentElement->getAttribute("license");
			if ($license != null && $license != "")
				SetSingleFeedwebOption("license", $license);
			
		    $caps = $dom->documentElement->getElementsByTagName("CAP");
		    $feedweb_blog_caps = array();
		    
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
			return $license;
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

function GetLicenseInfo($remark)
{
	$license = GetSingleFeedwebOption("license");
	if ($license == null || $license == "")
		$license = "*";
		
	$plugin_name = dirname(__FILE__)."/feedweb.php";
	$plugin_data = get_plugin_data($plugin_name);
	$val = $license.";".$plugin_data['Version'];
	if ($remark != null)
		$val .= ";".$remark;
	return "<input name='FeedwebLicenseInfo' type='hidden' value='$val'/>";
}

function CheckServiceAvailability()
{
	// Check service availability once in an hour
	global $wpdb;
	$query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id=0 AND meta_key='feedweb_last_access'";
	$access = $wpdb->get_var($query);
	if ($access != null)
	{
		$current = time();
		$previous = intval($access);
		if ($current - $access < 900)	// 15 min * 60 sec
			return null;
	}

	$query = GetFeedwebUrl()."FBanner.aspx?action=ping";
	$response = wp_remote_get ($query, array('timeout' => 20));
	if (is_wp_error ($response))
		return $response->get_error_message();
	
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
		{
			$query = "DELETE FROM $wpdb->postmeta WHERE post_id=0 AND meta_key='feedweb_last_access'";
			$wpdb->query($query);
			
			$access = strval(time());
			$query = "INSERT INTO $wpdb->postmeta(post_id, meta_key, meta_value) VALUES (0, 'feedweb_last_access', '$access')";
			$wpdb->query($query);
			return null;
		}
			
	return "";
}

function WriteDebugLog($text)
{
    global $wpdb;
	$query = "INSERT INTO debug_log(log_time, log_text) VALUES(NOW(), '$text')";
	$wpdb->query($query);
}

?>