<?php
/*
Plugin Name: Feedweb
Plugin URI: http://wordpress.org/extend/plugins/feedweb/
Description: Expose your blog to the Feedweb reader's community. Promote your views. Get a comprehensive and detailed feedback from your readers.
Author: Feedweb
Version: 2.1.5
Author URI: http://feedweb.net
*/

require_once('feedweb_util.php');
require_once('feedweb_options.php');
require_once(ABSPATH.'wp-admin/includes/plugin.php');

$feedweb_blog_caps = null;
$feedweb_rw_swf = "FL/RatingWidget.swf";

function CheckAtContentWidget($content)
{
	try
	{
		if (AtContentIncompatibleVersion() == null)
			return false;
	
		$dom = new DOMDocument;
		if ($dom->loadHTML($content) != true)
			return false;
			
		$divs = $dom->getElementsByTagName("div");
		if ($divs == null)
			return false;
			
		for ($index = 0; $index < $divs->length; $index++)
		{
			$div = $divs->item($index);
			$class = $div->getAttribute("class");
			if ($class == "atcontent_widget")
				return true;
		}
	}
	catch (Exception $e)
	{
	}
	return false;
}

function ContentFilter($content)
{
	global $post_ID;
	global $feedweb_rw_swf;
	
	$data = GetFeedwebOptions();
	if ($data["mp_widgets"] == "0")	// Doesn't display on the Home / Front Page
		if (is_front_page() || is_home())
			return  $content;
			
	if ($data["atcontent_widget_check"] == "1")	// Prevent double widget appearance...
		if (CheckAtContentWidget($content) == true)
			return $content . GetLicenseInfo('AtContentPatch');
		
	$id = get_the_ID($post_ID);
	$pac = GetPac($id);
	if ($pac == null)
		return  $content;
		
	if (CheckServiceAvailability() != null)
		return $content . GetLicenseInfo('Service is not available');
	
	$width = $data["widget_width"];
	switch ($data["widget_type"])
	{
		case "F": // Flash Widget
			$swf = GetFeedwebUrl().$feedweb_rw_swf;
			$code = "<object width='".$width."' height='150' ". 
				"type='application/x-shockwave-flash' ". 
				"classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' ". 
				"codebase='http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab' ".
				"pluginspage='http://www.adobe.com/go/getflashplayer'>". 
				"<param name='PluginsPage' value='http://www.adobe.com/go/getflashplayer'/>".
				"<param name='FlashVars' value='PAC=" .$pac. "&amp;lang=" .$data["language"]. "'/>".
				"<param name='Movie' value='" . $swf. "'/>".
				"<param name='allowScriptAccess' value='always'/>".
				"<param name='allowFullScreen' value='true'/>".
				"<embed src='" .$swf. "' width='".$width."' height='150' ".
				"flashvars='PAC=" .$pac. "&amp;lang=" .$data["language"]. "' ". 
				"allowfullscreen='true' allowScriptAccess='always' ".
				"type='application/x-shockwave-flash' ".
				"pluginspage='http://www.adobe.com/go/getflashplayer'>".
				"</embed></object>";
			break;
				
		case "H": // HTML5 Widget
			$frame_width = intval($width) + 5;
			$src = GetFeedwebUrl()."BRW/BlogRatingWidget.aspx?cs=".$data["widget_cs"]."&amp;width=$width&amp;height=120&amp;".
				"lang=".$data["language"]."&amp;pac=$pac&amp;ext_bg=".$data["widget_ext_bg"];
			$code = "<iframe id='FeedwebRatingWidget_$id' style='width: ".$frame_width."px; height: 125px; border-style: none;' scrolling='no' src='$src'></iframe>";
			break;
				
		default:
			return $content.GetLicenseInfo("Invalid Widget Type: " + $data["widget_type"]);
	}
	
	$signature = GetLicenseInfo(null);
	if (strstr($content, $signature) != false) // The signature is already exists
		return $content;
		
	if ($data["add_paragraphs"] == "1")
		$code = "<p>".$code."</p>";
			
	$content .= $signature."<br/>".$code;
	if ($data["copyright_notice"] == "1")
		$content .= GetCopyrightNotice(null);
	
	return $content;
}


function GetCopyrightNotice($highlight)
{
	$data = get_plugin_data( __FILE__ );
	$version = $data['Version'];
	$text = "<p><span style='font-size: x-small;";
	if ($highlight != null)
		$text .= "background-color: $highlight;";
	$text .= "'><i><a href='http://wordpress.org/extend/plugins/feedweb'>Feedweb plugin for Wordpress</a>. ".
		"v$version</i>  &copy; <a href='http://feedweb.net'>Feedweb Research</a>, 2012</span></p>";
	return $text;
}

function AddFeedwebColumn($columns) 
{
	// Check if user is admin
	if (current_user_can('manage_options'))
		if (UpdateBlogCapabilities() != null)
			$columns['feedweb'] = "Feedweb";
	
	return $columns;
}

function AddFeedwebColumnSort($columns) 
{
	if (current_user_can('manage_options'))
		$columns['feedweb'] = "feedweb";
	
	return $columns;
}

function FeedwebColumnOrderby($vars) 
{
    if ( isset( $vars['orderby'] ) && 'feedweb' == $vars['orderby'] ) 
	{
        $vars = array_merge ( $vars, array('meta_key' => 'feedweb_post_sort_value', 'orderby' => 'meta_value_num') );
    }
    return $vars;
}

function SetSortValue($id, $sort_value)
{
	global $wpdb;
	
	// Get Previous Value
	$query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='feedweb_post_sort_value' AND post_id=$id";
	$old_value = $wpdb->get_var($query);

	if ($old_value == null) // No Previous Value
		$query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ($id, 'feedweb_post_sort_value', '$sort_value')";
	else					// Update existing Value
		$query = "UPDATE $wpdb->postmeta SET meta_value='$sort_value' WHERE post_id=$id AND meta_key='feedweb_post_sort_value'";
	
	$wpdb->query($query);
}

function FillFeedwebCell($id)
{
	$width = 675;
	$height = 360;
	
	// First, find out if a wiget has been created already
	$pac = GetPac($id);
	if ($pac == null) // Not created yet - display 'Insert' button
	{
		$status = GetInsertWidgetStatus($id);
		if ($status != null)
		{
			SetSortValue($id, -2);
			$src = GetFeedwebUrl()."IMG/Warning.png";
			echo "<img src='$src' title='$status'/>";
		}
		else 
		{
			SetSortValue($id, -1);
			$src = GetFeedwebUrl()."IMG/Append.png";
			$url = plugin_dir_url(__FILE__)."widget_dialog.php?wp_post_id=$id&mode=add&KeepThis=true&TB_iframe=true&height=$height&width=$width";
			echo "<input alt='".$url."' class='thickbox' title='".__("Insert Rating Widget", "FWTD")."' type='image' src='$src'/>";
		}
	}
	else	// Created - display 'Edit' button
	{
		$data = GetPageData($pac, false);
		if ($data == null)
			return;
		
		if ($data['error'] != null && $data['error'] != "")
		{
			SetSortValue($id, -3);
			$src = GetFeedwebUrl()."IMG/Remove.png";
			if ($data['error'] == "Bad PAC")
			{
				$title = __("The widget data is invalid and cannot be used.", "FWTD");
				echo "<script>function OnInvalidPAC() { if (window.confirm ('".__("Remove Invalid Widget?", "FWTD")."') == true) ".
					"window.location.href='".plugin_dir_url(__FILE__)."/widget_commit.php?feedweb_cmd=REM&wp_post_id=".$id."'; } ".
					"</script><a href='#' onclick='OnInvalidPAC()'><img title='$title' src='$src' style='padding-left: 4px;'/></a>";
				return;
			}
			$title = __("Unknown error.", "FWTD").__("\nPlease contact Feedweb (contact@feedweb.net)", "FWTD");
			echo "<img title='$title' src='$src' style='padding-left: 4px;'/>";
			return;
		}
		
		$src = GetFeedwebUrl()."IMG/Edit.png";
		$votes = $data['votes'];
		$score = $data['score'];
		if ($score != "")
		{
			SetSortValue($id, intval($votes));
			$format = __("Edit / Remove Rating Widget\n(%s Votes. Average Score: %s)", "FWTD");
			$title = sprintf($format, $votes, $score);
			if ($data['image'] != "")
				$src = GetFileUrl($data['image']);
		}
		else
		{
			SetSortValue($id, 0);
			$title = __("Edit / Remove Rating Widget\n(No votes yet)", "FWTD");
		}
			
		$url = plugin_dir_url(__FILE__)."widget_dialog.php?wp_post_id=".$id."&mode=edit&KeepThis=true&TB_iframe=true&height=$height&width=$width";
		echo "<input alt='$url' class='thickbox' title='$title' type='image' src='$src'/>";
	}
}

function FillFeedwebColumn($column_name, $id) 
{
	switch ($column_name) 
	{
		case 'feedweb' :
			FillFeedwebCell($id);
			break;
	}
}

function InitPlugin() 
{
	add_thickbox();
	load_plugin_textdomain( 'FWTD', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function FeedwebPluginMenu()
{
	add_options_page('Feedweb Plugin Options', 'Feedweb', 'manage_options', basename(__FILE__), 'FeedwebPluginOptions');
}

function FeedwebSettingsLink($links)
{
	$settings_link = "<a href='options-general.php?page=".basename(__FILE__)."'>".__("Settings")."</a>";
	array_unshift($links, $settings_link);
	return $links;
}

function FrontWidgetCallback($atts)
{
	if (CheckServiceAvailability() != null)
		return "";

	$bac = GetBac(true);
	if ($bac == null)
		return "";
		
	$data = GetFeedwebOptions();
	if ($data == null)
		return "";
		
	$color = "#ffffff";
	$lang = $data["language"];
	$url = GetFeedwebUrl()."FPW/FrontWidget.aspx?bac=$bac&amp;lang=$lang";
	switch ($data["front_widget_color_scheme"])
	{
		case 'classic':
			$color = "#405060";
			break;
		case 'monochrome':
			$url = $url."&amp;background_color=ffffff&amp;title_color=000000&amp;content_color=404040&amp;title_highlight=808080&amp;selected_item_color=e0e0e0&amp;border_color=000000";
			break;
		case 'light_blue':
			$url = $url."&amp;background_color=c0c0ff&amp;title_color=000080&amp;content_color=000040&amp;title_highlight=0000ff&amp;selected_item_color=8080ff&amp;border_color=404080";
			break;
		case 'dark_blue':
			$url = $url."&amp;background_color=000040&amp;title_color=ffffff&amp;content_color=c0c0ff&amp;title_highlight=c0ffff&amp;selected_item_color=000060&amp;border_color=000000";
			break;
		case 'dark_green':
			$url = $url."&amp;background_color=004000&amp;title_color=ffffff&amp;content_color=c0ffc0&amp;title_highlight=c0ffff&amp;selected_item_color=006000&amp;border_color=000000";
			break;
	}
	$url .= "&amp;items=".$data["front_widget_items"];
	
	$width = 250;
	$scrolling = "";
	if ($data["front_widget_hide_scroll"] == "1") // No scrolling - 
		$scrolling = "scrolling='no'";
	else
		$width = 270;
	return "<div style='width: 100%; height: 100%; background-color: $color; text-align: center;'>".
		"<iframe id='FrontWidgetFrame' src='$url' style='width: ".$width."px; height: ".$data["front_widget_height"]."px; ".
		"border-style: none;' $scrolling></iframe></div>";
}

function showMessage($message, $errormsg = false)
{
	if ($errormsg) 
		echo '<div id="message" class="error">';
	else 
		echo '<div id="message" class="updated fade">';
	echo "<p><strong>$message</strong></p></div>";
}    

function showAdminMessages()
{
     // Only show to admins
    if (current_user_can('manage_options'))
	{
		$error = CheckServiceAvailability();
		if ($error != null)
		{
			$msg = __("The Feedweb service is temporarily unavailable due to system maintenance", "FWTD");
			if ($error != "")
				$msg .= " (Error: $error)";
			showMessage($msg, true);
			return;
		}
		
		$error = CheckIncompatiblePlugin();
		if ($error != null)
		{
			showMessage($error, true);
			return;
		}
    }
}

function FeedwebSavePost($pid) 
{
	if (current_user_can('manage_options') == false)
		return;
	
	//verify post is not a revision
	if (wp_is_post_revision($pid))
		return;
		
	//verify post is not trashed
	$status = get_post_status($pid);
	if ($status == 'trash')
		return;
			
	//verify post has a widget
	$pac = GetPac($pid);
	if ($pac == null)
		return;
		
	SetPostStatus($pid, 2); // Set status 2 - check post Title/Url
}


function EnqueueAdminScript() 
{
	$result = QueryPostStatus();
	if ($result == null)
		return;
		
	$id = $result->post_id;
	switch ($result->meta_value)
	{
		case '0':	// New Post
			$function = "DisplayInsertWidgetPrompt();";
			$title = __("Insert Rating Widget", "FWTD");
			$prompt = __("Do you wish to insert a Feedweb rating widget into your new post?", "FWTD");
			$url = plugin_dir_url(__FILE__)."widget_dialog.php?wp_post_id=$id&mode=add&KeepThis=true&TB_iframe=true";
			break;
			
		case '2':	// Edited Post
			$pac = GetPac($id);
			$post = get_post($id);
			$data = GetPageData($pac, true);
			
			$server_url = $data["url"];
			$server_title = $data["title"];
			$local_url = get_permalink($id);
			$local_title = ConvertHtml($post->post_title);
				
			if ($server_url == $local_url && $server_title == $local_title)
				return;
				
			$prompt = __("The Title or Permalink of the post has been changed. Do you with to update the Rating Widget with new data?", "FWTD");
			$action = "window.location.href='".plugin_dir_url(__FILE__)."widget_commit.php?wp_post_id=$id&feedweb_cmd=NPW';";
			$function = "DisplayUpdateWidgetPrompt();";
			break;
			
		default:
			return;
	}
	
	?>
	<script type="text/javascript">
		var readyStateCheckInterval = setInterval( function() 
		{
			if (document.readyState === "complete") 
			{
				<?php echo $function?>
				clearInterval(readyStateCheckInterval);
			}
		}, 1000);
		
		function DisplayUpdateWidgetPrompt()
		{
			if (window.confirm('<?php echo $prompt ?>') == true)
			{
				<?php echo $action?>
			}
		}
	
		function DisplayInsertWidgetPrompt()
		{
			if (window.confirm('<?php echo $prompt ?>') == true)
			{
				tb_show('<?php echo $title?>', '<?php echo $url?>');
				
				var tb = document.getElementById("TB_window");
				if (tb != null)
				{
					var frames = tb.getElementsByTagName("iframe");
					frames[0].style.height = "370px";
					frames[0].style.width = "700px";
					tb.style.height = "370px";
					tb.style.width = "700px";
				}
			}
		}
	</script>
	<?php
}   


function DeletePostHook($pid)
{
	// Delete Widget Entry
}

function TrashPostHook($pid)
{
	//Set widget 'Invisible' 
	SetPageVisibilityStatus($pid, 0);
}

function TrashedPostHook($pid)
{
}

function PublishPostHook($deprecated = '')
{
	if (current_user_can('manage_options') == false)
		return;
		
	$data = GetFeedwebOptions();
	if ($data == null)
		return;
	
	if ($data["widget_prompt"] != "1")
		return;
	
	// Get current post id (for newly published post)
	global $post_ID;
	$id = get_the_ID($post_ID);
	$pac = GetPac($id);
	if ($pac != null) // Already exists
		return;
		
	SetPostStatus($id, 0);
}

// Checks if the current post status differs from 1. If yes, return it and reset status to 1.
function QueryPostStatus()
{
	global $wpdb;
	$query = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'feedweb_post_status' AND meta_value != '1'";
	$results = $wpdb->get_results($query);
	if ($results == null)
		return null;
		
	foreach($results as $result)
	{
		$id = $result->post_id;
		$query = "UPDATE $wpdb->postmeta SET meta_value='1' WHERE post_id=$id AND meta_key='feedweb_post_status'";
		$wpdb->query($query);
		return $result;
	}
}

function SetPostStatus($id, $new_status)
{
	global $wpdb;
	// Get Previous Status
	$query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='feedweb_post_status' AND post_id=$id";
	$old_status = $wpdb->get_var($query);

	if ($old_status == null) // No previous status
		$query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ($id, 'feedweb_post_status', '$new_status')";
	else	// Update existing status
	{
		if ($new_status == 0) // Initial status; Old status MUST NOT exist
			return;
		
		$query = "UPDATE $wpdb->postmeta SET meta_value='$new_status' WHERE post_id=$id AND meta_key='feedweb_post_status'";
	}
	$wpdb->query($query);
}

function AddFeedwebAdminMenu() 
{
	$url = GetFeedwebUrl()."IMG/Logo_16x16_Transparent.png";
	add_menu_page ( 'Feedweb', 'Feedweb', 'manage_options', 'feedweb/feedweb_menu.php', '', $url, 123 );
	add_submenu_page( 'feedweb/feedweb_menu.php', __('Settings'), __('Settings'), 'manage_options', 'feedweb/feedweb_menu.php');
	add_submenu_page( 'feedweb/feedweb_menu.php', __('Tutorial', 'FWTD'), __('Tutorial', 'FWTD'), 'manage_options', 'feedweb/feedweb_help.php');
	//add_submenu_page( 'feedweb/feedweb_menu.php', __('Our Friends'), __('Our Friends'), 'manage_options', 'feedweb/feedweb_friends.php');
}

add_action('init', 'InitPlugin');
add_filter('the_content', 'ContentFilter');

add_filter( 'manage_posts_columns', 'AddFeedwebColumn');
add_action( 'manage_posts_custom_column', 'FillFeedwebColumn', 10, 2 );

add_filter( 'manage_edit-post_sortable_columns', 'AddFeedwebColumnSort');
add_filter( 'request', 'FeedwebColumnOrderby' );

$feedweb_plugin = plugin_basename(__FILE__);
add_action('admin_menu', 'FeedwebPluginMenu');
add_filter("plugin_action_links_$feedweb_plugin", 'FeedwebSettingsLink' );

add_shortcode( 'FeedwebFrontWidget', 'FrontWidgetCallback' );
add_filter('widget_text', 'do_shortcode');

add_action('admin_notices', 'showAdminMessages');

add_action('publish_post', 'PublishPostHook');

add_action('delete_post', 'DeletePostHook');
add_action('trashed_post', 'TrashedPostHook');
add_action('trash_post', 'TrashPostHook');

add_action( 'admin_enqueue_scripts', 'EnqueueAdminScript' );
add_action( 'admin_menu', 'AddFeedwebAdminMenu' );
add_action( 'save_post', 'FeedwebSavePost' );


?>