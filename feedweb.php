<?php
/*
Plugin Name: Feedweb
Plugin URI: http://wordpress.org/extend/plugins/feedweb/
Description: Expose your blog to the Feedweb reader's community. Promote your views. Get a comprehensive and detailed feedback from your readers.
Author: Feedweb
Version: 1.5.11
Author URI: http://feedweb.net
*/

require_once('feedweb_util.php');
require_once('feedweb_options.php');
require_once(ABSPATH.'wp-admin/includes/plugin.php');

$feedweb_blog_caps = null;
$feedweb_fw_swf = "FL/FrontWidget.swf";
$feedweb_rw_swf = "FL/RatingWidget.swf";

function ContentFilter($content)
{
	global $post_ID;
	global $feedweb_rw_swf;
	
	$data = GetFeedwebOptions();
	if ($data["mp_widgets"] == "0")	// Doesn't display on the Home / Front Page
		if (is_front_page() || is_home())
			return  $content;
	
	$pac = GetPac(get_the_ID($post_ID));
	if ($pac == null)
		return  $content;
		
	if (CheckServiceAvailability() != null)
		return $content . GetLicenseInfo('Service is not available');
	
	$swf = GetFeedwebUrl().$feedweb_rw_swf;
	$width = $data["widget_width"];

	$obj = "<object width='".$width."' height='150' ". 
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
	
	$content .= GetLicenseInfo(null);
	if ($data["copyright_notice"] == "1")
		return $content."<br/>".$obj.GetCopyrightNotice(null);
	return $content."<br/>".$obj;
}


function GetCopyrightNotice($highlight)
{
	$data = get_plugin_data( __FILE__ );
	$version = $data['Version'];
	$text = "<p><span style='font-size: x-small;";
	if ($highlight != null)
		$text .= "background-color: $highlight;";
	$text .= "'><i><a href='http://wordpress.org/extend/plugins/feedweb'>Feedweb plugin for WordPress</a>. ".
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
			$src = GetFeedwebUrl()."IMG/Warning.png";
			echo "<img src='$src' title='$status'/>";
		}
		else 
		{
			$src = GetFeedwebUrl()."IMG/Append.png";
			$url = plugin_dir_url(__FILE__)."widget_dialog.php?wp_post_id=".$id."&mode=add&KeepThis=true&TB_iframe=true&height=$height&width=$width";
			echo "<input alt='".$url."' class='thickbox' title='".__("Insert Rating Widget", "FWTD")."' type='image' src='$src'/>";
		}
	}
	else			// Created - display 'Edit' button
	{
		$data = GetPageData($pac, false);
		if ($data == null)
			return;
		
		if ($data['error'] != null && $data['error'] != "")
		{
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
			$format = __("Edit / Remove Rating Widget\n(%s Votes. Average Score: %s)", "FWTD");
			$title = sprintf($format, $votes, $score);
			if ($data['image'] != "")
				$src = GetFileUrl($data['image']);
		}
		else
			$title = __("Edit / Remove Rating Widget\n(No votes yet)", "FWTD");
			
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
	/*
	$title = __("Quick Tour", "FWTD");
	$url = plugin_dir_url(__FILE__)."feedweb_tour.php?Mode=QT&KeepThis=true&TB_iframe=true&height=200&width=200";
	$quick_tour_link = "<a class='thickbox' title='$title' href='$url'>$title</a>";
	array_unshift($links, $quick_tour_link);
	*/
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
		}
    }
}

add_action('init', 'InitPlugin');
add_filter('the_content', 'ContentFilter');

add_filter( 'manage_posts_columns', 'AddFeedwebColumn');
add_action( 'manage_posts_custom_column', 'FillFeedwebColumn', 10, 2 );

$feedweb_plugin = plugin_basename(__FILE__);
add_action('admin_menu', 'FeedwebPluginMenu');
add_filter("plugin_action_links_$feedweb_plugin", 'FeedwebSettingsLink' );

add_shortcode( 'FeedwebFrontWidget', 'FrontWidgetCallback' );
add_filter('widget_text', 'do_shortcode');

add_action('admin_notices', 'showAdminMessages');
?>