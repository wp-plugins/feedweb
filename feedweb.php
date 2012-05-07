<?php
/*
Plugin Name: Feedweb
Plugin URI: http://wordpress.org/extend/plugins/feedweb/
Description: Expose your blog to the Feedweb reader's community. Promote your views. Get a comprehensive and detailed feedback from your readers.
Author: Feedweb
Version: 1.3.3
Author URI: http://feedweb.net
*/

require_once('feedweb_util.php');
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
	{
		UpdateBlogCapabilities();
		$columns['feedweb'] = "Feedweb";
	}
	return $columns;
}

function FillFeedwebCell($id)
{
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
			$src = GetFeedwebUrl()."IMG/Insert.png";
			$url = plugin_dir_url(__FILE__)."widget_dialog.php?wp_post_id=".$id."&KeepThis=true&TB_iframe=true&height=345&width=675";
			echo "<input alt='".$url."' class='thickbox' title='".__("Insert Widget", "FWTD")."' type='image' src='$src'/>";
		}
	}
	else			// Created - display 'Remove' button
	{
		$src = GetFeedwebUrl()."IMG/Remove.png";
		$data = GetPostVotes($pac);
		$votes = $data['votes'];
		$score = $data['score'];
		if ($score != "")
		{
			$format = __("Remove Widget (%s Votes. Average Score: %s)", "FWTD");
			$title = sprintf($format, $votes, $score);
			if ($data['url'] != "")
				$src = GetFeedwebUrl().$data['url'];
		}
		else
			$title = __("Remove Widget (No votes yet)", "FWTD");
			
		$url = plugin_dir_url(__FILE__)."widget_remove.php?wp_post_id=".$id."&KeepThis=true&TB_iframe=true&height=125&width=575";
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


function BuildLanguageBox($language, $language_set)
{
	echo "<select id='WidgetLanguageBox' name='WidgetLanguageBox' style='width: 99%;' onchange='OnChangeLanguage()'>";
    
	$languages = GetLanguageList();
    if ($language_set != true) // Language was not set yet by the admin. Try to set by default locale
	{
		$locale = get_locale();
		$pos = strpos($locale, "_");
		if ($pos > 0)
		    $locale = substr($locale, 0, $pos);
			
		if (array_key_exists($locale, $languages) == true)
		    $language = $locale;
	}
	
	if ($languages != null)
		foreach ($languages as $key => $value)
		{
			echo "<option";
			if ($key == $language)
				echo " selected='selected'";
			echo " value='".$key."'>".$value."</option>";
		}
	
	echo "</select>";
}

function BuildColorSchemeBox($sceme)
{
	$values = array("classic" => __("Classic", "FWTD"), "monochrome" => __("Monochrome", "FWTD"), "light_blue" => __("Light Blue", "FWTD"));
	
	echo "<select id='ColorSchemeBox' name='ColorSchemeBox' style='width: 99%;' onchange='OnChangeColorSceme()'>";
	foreach ($values as $key => $value)
	{
		echo "<option";
		if ($key == $sceme)
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
		<h2><?php _e("Feedweb Plugin Settings", "FWTD");?></h2>

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
				
				function OnChangeColorSceme()
				{
					var input = document.getElementsByName('FrontWidgetColorScheme')[0];
					var list = document.getElementsByName('ColorSchemeBox')[0];
					input.value = list.options[list.selectedIndex].value;
					
					/*
					var img = document.getElementsByName('FrontPageWidgetColorSchemePreview')[0];
					var src = "<?php echo GetFeedwebUrl()?>Img/" + input.value + ".jpg";
					img.setAttribute('src', src);
					*/
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
				
				function OnCheckHideScroll()
				{
					var box = document.getElementsByName('FrontWidgetHideScrollBox')[0];
					var input = document.getElementsByName('FrontWidgetHideScroll')[0];
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
					
					input = document.getElementsByName("FrontWidgetHeightEdit")[0];
					var height = parseInt(input.value);
					if (isNaN(height))
					{
						window.alert ('<?php _e("Please enter a valid Front Widget height", "FWTD")?>');
						return false;
					}

					if (height < 200 || height > 800)
					{
						window.alert ('<?php _e("Front Widget height is out of range", "FWTD")?>');
						return false;
					}
					input.value = height.toString();
					
					var form = document.getElementsByName("FeedwebSettingsForm")[0];
					form.action ="<?php echo plugin_dir_url(__FILE__)?>feedweb_settings.php";
					form.method = "post";
					return true;
				}
				
				function OnClickFeedwebSettingsTab(tab)
				{
					var divs = document.getElementsByName("FeedwebSettingsDiv");
					var tabs = document.getElementsByName("FeedwebSettingsTab");
					for (var index = 0; index < divs.length; index++)
						if (index.toString() == tab)
						{
							divs[index].style.display = "block";
							tabs[index].style.textDecoration = "underline";
						}
						else
						{
							divs[index].style.display = "none";
							tabs[index].style.textDecoration = "none";
						}
				}
			</script>
			<?php wp_referer_field(true)?>
			<link href='<?php echo plugin_dir_url(__FILE__)?>Feedweb.css' rel='stylesheet' type='text/css' />
			<input type='hidden' id='DelayResults' name='DelayResults' value='<?php echo $feedweb_data["delay"];?>'/>
			<input type='hidden' id='FeedwebLanguage' name='FeedwebLanguage' value='<?php echo $feedweb_data["language"];?>'/>
			<input type='hidden' id='FeedwebMPWidgets' name='FeedwebMPWidgets' value='<?php echo $feedweb_data["mp_widgets"];?>'/>
			<input type='hidden' id='FeedwebCopyrightNotice' name='FeedwebCopyrightNotice' value='<?php echo $feedweb_data["copyright_notice"];?>'/>
			<input type='hidden' id='FrontWidgetHideScroll' name='FrontWidgetHideScroll' value='<?php echo $feedweb_data["front_widget_hide_scroll"];?>'/>
			<input type='hidden' id='FrontWidgetColorScheme' name='FrontWidgetColorScheme' value='<?php echo $feedweb_data["front_widget_color_scheme"];?>'/>
			<br/>
			<table cellpadding="0" cellspacing="0">
				<tr class="FeedwebSettingsTabs">
					<td>
						<a href="#" name="FeedwebSettingsTab" onclick="OnClickFeedwebSettingsTab(0)" style="text-decoration: underline;">Rating Widget</a>
						<a href="#" name="FeedwebSettingsTab"onclick="OnClickFeedwebSettingsTab(1)">Front Widget</a>
					</td>
				</tr>
				<tr class="FeedwebSettingsContent">
					<td>
						<div name="FeedwebSettingsDiv" style="display: block; height: 254px;">
							<table class="FeedwebSettingsTable">
								<tbody>
									<tr>
										<td style='width: 200px;'>
											<span><b><?php _e("Widget Language:", "FWTD")?></b></span>
										</td>
										<td style='width: 10px;'/>
										<td style='width: 150px;'>
											<?php BuildLanguageBox($feedweb_data['language'], $feedweb_data['language_set']) ?>
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
											<span><i><?php _e("Allowed width: 350 to 700 pixels. Recommended width: 400 to 450 pixels.", "FWTD")?></i></span>
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
								</tbody>
							</table>
						</div>
						<div name="FeedwebSettingsDiv" style="display: none; height: 250px;">
							<table class="FeedwebSettingsTable">
								<tbody>
									<tr>
										<td style="width: 200px;">
											<span><b><?php _e("Front Page Widget Color Sceme:", "FWTD")?></b></span> 				
										</td>
										<td/>
										<td style="width: 150px;">
											<?php BuildColorSchemeBox($feedweb_data['front_widget_color_scheme']) ?>				
										</td>
										<td/>
										<td style="width: 500px;">
											<span><i><?php _e("Select color scheme for the Front Page widget", "FWTD")?></i></span>
										</td>
									</tr>
									<tr>
										<td>
											<span><b><?php _e("Max. Widget height (pixels):", "FWTD")?></b></span>
										</td>
										<td/>
										<td>
											<input id='FrontWidgetHeightEdit' name='FrontWidgetHeightEdit' type='text' style='width: 100%;' 
												value="<?php echo $feedweb_data['front_widget_height']?>"/>
										</td>
										<td/>
										<td>
											<span><i><?php _e("Allowed height: 200 to 800 pixels. Recommended height: 400 pixels.", "FWTD")?></i></span>
										</td>
									</tr>
									<tr>
										<td>
											<span><b><?php _e("Vertical Scroll Bar:", "FWTD")?></b></span> 				
										</td>
										<td />
										<td>
											<input id="FrontWidgetHideScrollBox" name="FrontWidgetHideScrollBox" onchange="OnCheckHideScroll()"
											<?php if($feedweb_data['front_widget_hide_scroll'] == "1") echo 'checked="checked"'?> type="checkbox"> 
											<?php _e("Hide", "FWTD")?></input>				
										</td>
										<td />
										<td>
											<span><i><?php _e("Check to hide vertical scroll bar when the front widget exceeds max. height.", "FWTD")?></i></span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</td>
				</tr>
				<tr class="FeedwebSettingsCommitButton">
					<td>
						<?php echo get_submit_button(__('Save Changes'), 'primary', 'submit', false, "style='width: 200px;'") ?>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<?php 
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
			$url = $url."&background_color=ffffff&title_color=000000&content_color=404040&title_highlight=808080&selected_item_color=e0e0e0&border_color=000000";
			break;
		case 'light_blue':
			//$color = "#c0c0ff";
			$url = $url."&background_color=c0c0ff&title_color=000080&content_color=000040&title_highlight=0000ff&selected_item_color=8080ff&border_color=404080";
			break;
	}
	
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

add_action('init', 'InitPlugin');
add_filter('the_content', 'ContentFilter');

add_filter( 'manage_posts_columns', 'AddFeedwebColumn');
add_action( 'manage_posts_custom_column', 'FillFeedwebColumn', 10, 2 );

$feedweb_plugin = plugin_basename(__FILE__);
add_action('admin_menu', 'FeedwebPluginMenu');
add_filter("plugin_action_links_$feedweb_plugin", 'FeedwebSettingsLink' );

add_shortcode( 'FeedwebFrontWidget', 'FrontWidgetCallback' );
add_filter('widget_text', 'do_shortcode');
?>