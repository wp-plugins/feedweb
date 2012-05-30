<?php

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once( ABSPATH.'wp-load.php');

function BuildLanguageBox($language, $language_set, $style, $all)
{
	echo "<select id='WidgetLanguageBox' name='WidgetLanguageBox' style='$style' onchange='OnChangeLanguage()'>";
    
	$languages = GetLanguageList($all);
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
	return $language;
}

function BuildColorSchemeBox($sceme)
{
	$values = array("classic" => __("Classic", "FWTD"), "monochrome" => __("Monochrome", "FWTD"), "light_blue" => __("Light Blue", "FWTD"), "dark_blue" => __("Dark Blue", "FWTD"));
	
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
							tabs[index].style.backgroundColor = "#e0e0ff";
						}
						else
						{
							divs[index].style.display = "none";
							tabs[index].style.textDecoration = "none";
							tabs[index].style.backgroundColor = "#ffffff";
						}
						
					var link = document.getElementsByName("FeedwebFrontWidgetHelpLink")[0];
					if (tab == "1")
						link.style.display = "inline";
					else
						link.style.display = "none";
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
						<a href="#" name="FeedwebSettingsTab" onclick="OnClickFeedwebSettingsTab(0)" 
							style="text-decoration: underline; background-color: #e0e0ff;"><?php _e("Rating Widget", "FWTD")?></a>
						<a href="#" name="FeedwebSettingsTab"onclick="OnClickFeedwebSettingsTab(1)"><?php _e("Front Widget", "FWTD")?></a>
					</td>
				</tr>
				<tr class="FeedwebSettingsContent">
					<td>
						<div name="FeedwebSettingsDiv" style="display: block; height: 246px;">
							<table class="FeedwebSettingsTable">
								<tbody>
									<tr>
										<td style='width: 255px;'>
											<span><b><?php _e("Widget Language:", "FWTD")?></b></span>
										</td>
										<td style='width: 10px;'/>
										<td style='width: 200px;'>
											<?php BuildLanguageBox($feedweb_data['language'], $feedweb_data['language_set'], 'width: 99%;', false) ?>
										</td>
										<td style='width: 10px;'/>
										<td style='width: 600px;'>
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
						<div name="FeedwebSettingsDiv" style="display: none; height: 246px;">
							<table class="FeedwebSettingsTable">
								<tbody>
									<tr>
										<td style="width: 255px;">
											<span><b><?php _e("Front Page Widget Color Sceme:", "FWTD")?></b></span> 				
										</td>
										<td style='width: 10px;'/>
										<td style="width: 200px;">
											<?php BuildColorSchemeBox($feedweb_data['front_widget_color_scheme']) ?>				
										</td>
										<td style='width: 10px;'/>
										<td style="width: 600px;">
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
						<a href="http://feedweb.net/site/the-new-front-page-widget/" name="FeedwebFrontWidgetHelpLink" style="padding-left: 300px; display:none;"
							target="_blank"><?php _e("Learn how to use the Front Page Widget in your blog", "FWTD")?></a>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<?php 
}
?>