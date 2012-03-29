<?php
/*
Plugin Name: Feedweb
Plugin URI: http://wordpress.org/extend/plugins/feedweb/
Description: Expose your blog to the Feedweb reader's community, promote your views, get a comprehensive and detailed feedback from your readers.
Author: Feedweb
Version: 1.1.5
Author URI: http://feedweb.net
*/

require_once('feedweb_util.php');
require_once(ABSPATH.'wp-admin/includes/plugin.php');

$feedweb_fw_swf = "FL/FrontWidget.swf";
$feedweb_rw_swf = "FL/RatingWidget.swf";

$feedweb_url = "http://wpblogs.feedweb.net/";
if (get_bloginfo('url') == "http://localhost/wordpress")
	$feedweb_url = "http://localhost/FeedwebServer/";

$test_str = __("Test String", "FWTD")

function ContentFilter($content)
{
	global $post_ID;
	global $feedweb_url;
	global $feedweb_rw_swf;
	
	$data = GetFeedwebOptions();
	if ($data["mp_widgets"] == "0")	// Doesn't display on the Home / Front Page
		if (is_front_page() || is_home())
			return  $content;
	
	$pac = GetPac(get_the_ID($post_ID));
	if ($pac == null)
		return  $content;
	
	$swf = $feedweb_url.$feedweb_rw_swf;
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
	
	if ($data["copyright_notice"] == "1")
		return $content."<br/>".$obj.GetCopyrightNotice('#ffffff');
	return $content."<br/>".$obj;
}


function GetCopyrightNotice($highlight)
{
	$data = get_plugin_data( __FILE__ );
	$version = $data['Version'];
	return "<p><span style='font-size: x-small; background-color: $highlight;'><i>".
		"<a href='http://wordpress.org/extend/plugins/feedweb'>Feedweb plugin for Wordpress</a>. ".
		"v$version</i>  &copy; <a href='http://feedweb.net'>Feedweb Research</a>, 2012</span></p>";
}


function AddFeedwebColumn($columns) 
{
	// Check if user is admin
	if (current_user_can('manage_options'))
		$columns['feedweb'] = __("Feedweb Widget", "FWTD");
	return $columns;
}

function FillFeedwebCell($id)
{
	// First, find out if a wiget has been created already
	$pac = GetPac($id);
	echo "<div style='vertical-align: bottom; height: 100%;'>";
	if ($pac == null) // Not created yet - display 'Insert' button
	{
		// Get post's age
		$days = GetPostAge($id);
		if ($days > GetMaxPostAge())
		{
			$format = __("Cannot insert widget into a post published %d days ago", "FWTD");
			$tip = sprintf($format, $days);
			$url = plugin_dir_url(__FILE__)."/SWarning.jpg";
			echo "<div style='text-align: center; width: 100px;'><img src='$url' title='$tip'/></div>";
		}
		else 
		{
			$url = plugin_dir_url(__FILE__)."widget_dialog.php?wp_post_id=".$id."&KeepThis=true&TB_iframe=true&height=345&width=675";
			echo "<input alt='".$url."' class='thickbox' title='Insert Widget' value='Insert' type='button' style='width: 100px;'/>";
		}
	}
	else			// Created - display 'Remove' button
	{
		$url = plugin_dir_url(__FILE__)."widget_remove.php?wp_post_id=".$id."&KeepThis=true&TB_iframe=true&height=125&width=575";
		echo "<input alt='".$url."' class='thickbox' title='Remove Widget' value='Remove' type='button' ".
			"style='width: 100px; background-color: #405060; color: #FFFFFF;'/>";
	}
	echo "</div>";
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
	load_plugin_textdomain( 'Feedweb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


function FeedwebPluginMenu()
{
	add_options_page('Feedweb Plugin Options', 'Feedweb', 'manage_options', basename(__FILE__), 'FeedwebPluginOptions');
}


function BuildLanguageBox($language)
{
	$languages = array("en"=>"English", "fr"=>"French", "he"=>"Hebrew", "ru"=>"Russian");

	echo "<select id='WidgetLanguageBox' name='WidgetLanguageBox' style='width: 99%;' onchange='OnChangeLanguage()'>";
	foreach ($languages as $key => $value)
	{
		echo "<option";
		if ($key == $language)
			echo " selected='selected'";
		echo " value='".$key."'>".$value."</option>";
	}
	echo "</select>";
}


function BuildDelayBox($delay)
{
	$values = array("0"=>__("No Delay", "FWTD"), "1"=>__("1 Hour", "FWTD"), "2"=>__("2 Hours", "FWTD"), "5"=>__("5 Hours", "FWTD"));

	echo "<select id='DelayResultsBox' name='DelayResultsBox' style='width: 99%;' onchange='OnChangeDelay()'>";
	foreach ($values as $key => $value)
	{
		echo "<option";
		if ($key == $delay)
			echo " selected='selected'";
		echo " value='".$key."'>".$value."</option>";
	}
	echo "</select>";
}


function FeedwebPluginOptions()
{
	if (!current_user_can("manage_options"))
		wp_die( __("You do not have sufficient permissions to access this page.") );
	
	// Read options 
	$feedweb_data = GetFeedwebOptions();
	?>
	
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2>Feedweb Plugin Settings</h2>

		<form name="FeedwebSettingsForm" onsubmit="return OnSubmitFeedwebSettingsForm();">
			<script type="text/javascript">
				function OnChangeLanguage()
				{
					var list = document.getElementsByName('WidgetLanguageBox')[0];
					var input = document.getElementsByName('FeedwebLanguage')[0];
					input.value = list.options[list.selectedIndex].value;
				}

				function OnChangeDelay()
				{
					var list = document.getElementsByName('DelayResultsBox')[0];
					var input = document.getElementsByName('DelayResults')[0];
					input.value = list.options[list.selectedIndex].value;
				}
				
				function OnCheckMPWidgets()
				{
					var box = document.getElementsByName('MPWidgetsBox')[0];
					var input = document.getElementsByName('FeedwebMPWidgets')[0];
					if (box.checked == true)
						input.value = "1";
					else
						input.value = "0";
				}

				function OnCheckCopyrightNotice()
				{
					var box = document.getElementsByName('CopyrightNoticeBox')[0];
					var input = document.getElementsByName('FeedwebCopyrightNotice')[0];
					if (box.checked == true)
						input.value = "1";
					else
						input.value = "0";
				}

				function OnSubmitFeedwebSettingsForm()
				{
					var input = document.getElementsByName("WidgetWidthEdit")[0];
					var width = parseInt(input.value);
					if (isNaN(width))
					{
						window.alert ('<?php _e("Please enter a valid width", "FWTD")?>');
						return false;
					}

					if (width < 350 || width > 700)
					{
						window.alert ('<?php _e("Width is out of range", "FWTD")?>');
						return false;
					}
					input.value = width.toString();
					
					var form = document.getElementsByName("FeedwebSettingsForm")[0];
					form.action ="<?php echo plugin_dir_url(__FILE__)?>feedweb_settings.php";
					form.method = "post";
					return true;
				}
			</script>
			<?php wp_referer_field(true)?>
			<input type='hidden' id='DelayResults' name='DelayResults' value='<?php echo $feedweb_data["delay"];?>'/>
			<input type='hidden' id='FeedwebLanguage' name='FeedwebLanguage' value='<?php echo $feedweb_data["language"];?>'/>
			<input type='hidden' id='FeedwebMPWidgets' name='FeedwebMPWidgets' value='<?php echo $feedweb_data["mp_widgets"];?>'/>
			<input type='hidden' id='FeedwebCopyrightNotice' name='FeedwebCopyrightNotice' value='<?php echo $feedweb_data["copyright_notice"];?>'/>
			<table class="form-table">
				<tbody>
					<tr>
						<td colspan="5"/>
					</tr>
					<tr>
						<td style='width: 150px;'>
							<span><b><?php _e("Widget Language:", "FWTD")?></b></span>
						</td>
						<td style='width: 10px;'/>
						<td style='width: 100px;'>
							<?php BuildLanguageBox($feedweb_data['language']) ?>
						</td>
						<td style='width: 10px;'/>
						<td style='width: 500px;'>
							<span><i>Don't find your language? <a href="mailto://contact@feedweb.net">Help us translate the widget for you!</a></i></span>
						</td>
					</tr>
					<tr>
						<td>
							<span><b><?php _e("Widget width (pixels):", "FWTD")?></b></span>
						</td>
						<td/>
						<td>
							<input id='WidgetWidthEdit' name='WidgetWidthEdit' type='text' style='width: 100%;' 
								value="<?php echo $feedweb_data['widget_width']?>"/>
						</td>
						<td/>
						<td>
							<span><i><?php _e("Allowed width: 350 to 700 pixels. Recommended width: 380 to 440 pixels.", "FWTD")?></i></span>
						</td>
					</tr>
					
					<tr>
						<td>
							<span><b><?php _e("Widgets at the Home/Front Page:", "FWTD")?></b></span> 				
						</td>
						<td />
						<td>
							<input <?php if($feedweb_data['mp_widgets'] == "1") echo 'checked="checked"' ?>
							id="MPWidgetsBox" name="MPWidgetsBox" type="checkbox" onchange='OnCheckMPWidgets()'> <?php _e("Display Widgets", "FWTD")?></input>				
						</td>
						<td />
						<td>
							<span><i><?php _e("Check to display the widgets both in the Front Page and the single post pages.", "FWTD")?></i></span>
						</td>
					</tr>
					<tr>
						<td colspan="5"/>
					</tr>
					
					<tr>
						<td>
							<span><b><?php _e("Delay displaying results:", "FWTD")?></b></span> 				
						</td>
						<td />
						<td>
							<?php BuildDelayBox($feedweb_data['delay']) ?>				
						</td>
						<td />
						<td>
							<span><i><?php _e("Set the period of time you want to hide voting results after the widget is created.", "FWTD")?></i></span>
						</td>
					</tr>
					<tr>
						<td colspan="5"/>
					</tr>
					
					<tr>
						<td>
							<span><b><?php _e("Feedweb Copyright Notice:", "FWTD")?></b></span> 				
						</td>
						<td />
						<td>
							<input <?php if($feedweb_data['copyright_notice'] == "1") echo 'checked="checked"' ?>
							id="CopyrightNoticeBox" name="CopyrightNoticeBox" type="checkbox" onchange='OnCheckCopyrightNotice()'> <?php _e("Allow")?></input>				
						</td>
						<td />
						<td>
							<span><i><?php _e("Please check to display the following text below the widgets: ", "FWTD")?></i></span>
							<?php echo GetCopyrightNotice('#ffff00')?>
						</td>
					</tr>
					<tr>
						<td colspan="5"/>
					</tr>
					
					<tr>
						<td colspan="5">
							<?php echo get_submit_button(__('Save Changes'), 'primary', 'submit', false, "style='width: 200px;'") ?>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
	<?php 
}

function FeedwebSettingsLink($links)
{
	$settings_link = "<a href='options-general.php?page=".basename(__FILE__)."'>".__("Settings")."</a>";
	array_unshift($links, $settings_link);
	return $links;
}

add_action('init', 'InitPlugin');
add_filter('the_content', 'ContentFilter');

add_filter( 'manage_posts_columns', 'AddFeedwebColumn');
add_action( 'manage_posts_custom_column', 'FillFeedwebColumn', 10, 2 );

$feedweb_plugin = plugin_basename(__FILE__);
add_action('admin_menu', 'FeedwebPluginMenu');
add_filter("plugin_action_links_$feedweb_plugin", 'FeedwebSettingsLink' );
?>