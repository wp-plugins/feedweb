<?php
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once( ABSPATH.'wp-load.php');
include_once( ABSPATH.'wp-admin/includes/plugin.php' );

function BuildLanguageBox($language, $language_set, $all)
{
	echo "<select id='WidgetLanguageBox' name='WidgetLanguageBox' onchange='OnChangeLanguage()'>";
    
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

function BuildItemCountBox($number)
{
	echo "<select id='ItemCountBox' name='ItemCountBox' onchange='OnChangeItemCount()'>";
	for ($value = 3; $value <= 10; $value++)
	{
		echo "<option";
		if ($value == $number)
			echo " selected='selected'";
		echo " value='$value'>$value</option>";
	}
	echo "</select>";
}

function BuildColorSchemeBox($scheme, $is_rating_widget)
{
	if ($is_rating_widget)
	{
		echo "<select id='RatingWidgetColorSchemeBox' name='RatingWidgetColorSchemeBox' onchange='OnChangeRatingWidgetColorScheme()'>";
		$values = array("blue" => __("Blue", "FWTD"), "gray" => __("Gray", "FWTD"));
	}
	else
	{
		echo "<select id='FrontWidgetColorSchemeBox' name='FrontWidgetColorSchemeBox' style='width: 99%;' onchange='OnChangeFrontWidgetColorScheme()'>";
		$values = array("classic" => __("Classic", "FWTD"), "monochrome" => __("Monochrome", "FWTD"), "light_blue" => __("Light Blue", "FWTD"), 
			"dark_blue" => __("Dark Blue", "FWTD"), "dark_green" => __("Dark Green", "FWTD"));
	}
			
	foreach ($values as $key => $value)
	{
		echo "<option";
		if ($key == $scheme)
			echo " selected='selected'";
		echo " value='".$key."'>".$value."</option>";
	}
	echo "</select>";
}

function BuildExternalBackgroundControl($color)
{
	echo "<input id='ExternalBackgroundBox' name='ExternalBackgroundBox' class='color' value='$color'>";
	BuildResetPreviewButton('ExternalBackgroundResetButton');
}

function BuildResetPreviewButton($id)
{
	$title = __("Reset Preview", "FWTD");
	$button_url = GetFeedwebUrl()."Img/Gray/Refresh.png";
	echo "<img id='$id' src='$button_url' title='$title' onclick='ResetWidgetPreview()'/>";
}

function BuildDelayBox($delay)
{
	$values = array("0"=>__("No Delay", "FWTD"), "1"=>__("1 Hour", "FWTD"), "2"=>__("2 Hours", "FWTD"), "5"=>__("5 Hours", "FWTD"));

	echo "<select id='DelayResultsBox' name='DelayResultsBox' onchange='OnChangeDelay()'>";
	foreach ($values as $key => $value)
	{
		echo "<option";
		if ($key == $delay)
			echo " selected='selected'";
		echo " value='".$key."'>".$value."</option>";
	}
	echo "</select>";
}

function GetPurgeInactiveWidgets()
{
	$ids = GetOrfanedIDs();
	if ($ids == null)
		return;
	if (count($ids) == 0)
		return;
			
	echo "<input id='InactiveWidgetIds' type='hidden' value='";
	$first = true;
	foreach ($ids as $id)
	{
		if ($first == true)
			$first = false;
		else
			echo ";";
		echo $id;
	}
	echo "'/><input id='PurgeInactiveWidgetsButton' type='button' onclick='OnPurgeInactiveWidgets()' value='".__("Remove Inactive Widgets", "FWTD")."' ".
		"title='".__("Click to remove widgets from the deleted posts", "FWTD")."' />";
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

		<form name="FeedwebSettingsForm" id="FeedwebSettingsForm" onsubmit="return OnSubmitFeedwebSettingsForm();">
			<?php
				$script_url = GetFeedwebUrl()."Base/jscolor/jscolor.js";
				echo "<script type='text/javascript' src='$script_url'></script>";
			?>
			<script type="text/javascript">
				function OnShowWidgetPreview()
				{
					var settings = document.getElementsByClassName("FeedwebSettingsDiv");
					var title = document.getElementById("WidgetPreviewTitle");
					var row = document.getElementById("WidgetPreviewRow");
					var div = document.getElementById("WidgetPreview");
					if (div.style.display == "block") // Hide
					{
						title.innerHTML = "<?php _e("Show Widget Preview >>>", "FWTD") ?>";
						settings[0].style.height = "450px";
						div.style.display = "none";
						row.style.height = "35px";
					}
					else
					{
						title.innerHTML = "<?php _e("<<< Hide Widget Preview", "FWTD") ?>";
						settings[0].style.height = "570px";
						div.style.display = "block";
						row.style.height = "155px";
					}
				}
				
				function ResetWidgetPreview()
				{
					var lang = document.getElementById('FeedwebLanguage').value;
					var div = document.getElementById("WidgetPreview");
					var pac = "e5615caa-cc14-4c9d-9a5b-069f41c2e802";
					var width = ValidateRatingWidgetWidth();
					if (width == 0)
						return;
						
					var url = '<?php echo GetFeedwebUrl()?>';
					if (document.getElementById('RatingWidgetType').value == "H")
					{
						var ext_bg = document.getElementById("ExternalBackgroundBox").value;
						var box = document.getElementById("RatingWidgetColorSchemeBox");
						var cs = box.options[box.selectedIndex].value;
						
						var src = url + "BRW/BlogRatingWidget.aspx?cs=" + cs + "&amp;width=" + width.toString() + 
							"&amp;height=120&amp;lang=" + lang + "&amp;pac=" + pac + "&amp;ext_bg=" + ext_bg;
						var style = "width: " + (width + 5).toString() + "px; height: 125px; border-style: none;";
						div.innerHTML = "<iframe style='" + style + "' scrolling='no' src='" + src + "'></iframe>";
					}
					else
					{
						var swf = url + "FL/RatingWidget.swf";
						div.innerHTML = "<object width='" + width.toString() + "' height='150' " + 
							"type='application/x-shockwave-flash' " + 
							"classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' " + 
							"codebase='http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab' " +
							"pluginspage='http://www.adobe.com/go/getflashplayer'>" +
							"<param name='PluginsPage' value='http://www.adobe.com/go/getflashplayer'/>" +
							"<param name='FlashVars' value='PAC=" + pac + "&amp;lang=" + lang + "'/>" +
							"<param name='Movie' value='" + swf + "'/>" +
							"<param name='allowScriptAccess' value='always'/>" +
							"<param name='allowFullScreen' value='true'/>" +
							"<embed src='" + swf + "' width='" + width.toString() + "' height='150' " +
							"flashvars='PAC=" + pac + "&amp;lang=" + lang + "' " +
							"allowfullscreen='true' allowScriptAccess='always' " +
							"type='application/x-shockwave-flash' " +
							"pluginspage='http://www.adobe.com/go/getflashplayer'>" +
							"</embed></object>";
					}
				}
								
				function OnPurgeInactiveWidgets()
				{
					if (window.confirm('<?php _e("Remove Widgets?", "FWTD") ?>') == true)
					{
						var ids = document.getElementById('InactiveWidgetIds');
						window.location.href = "<?php echo plugin_dir_url(__FILE__)?>widget_commit.php?feedweb_cmd=RMW&wp_post_ids=" + ids.value;
					}
				}
			
				function OnChangeLanguage()
				{
					var list = document.getElementById('WidgetLanguageBox');
					var input = document.getElementById('FeedwebLanguage');
					input.value = list.options[list.selectedIndex].value;
					ResetWidgetPreview();
				}

				function OnChangeDelay()
				{
					var list = document.getElementById('DelayResultsBox');
					var input = document.getElementById('DelayResults');
					input.value = list.options[list.selectedIndex].value;
				}
				
				function OnChangeItemCount()
				{
					var input = document.getElementById('FrontWidgetItemCount');
					var list = document.getElementById('ItemCountBox');
					input.value = list.options[list.selectedIndex].value;
				}
				
				function OnChangeFrontWidgetColorScheme()
				{
					var list = document.getElementById('FrontWidgetColorSchemeBox');
					var input = document.getElementById('FrontWidgetColorScheme');
					input.value = list.options[list.selectedIndex].value;
				}
				
				function OnChangeRatingWidgetColorScheme()
				{
					var list = document.getElementById('RatingWidgetColorSchemeBox');
					var input = document.getElementById('RatingWidgetColorScheme');
					input.value = list.options[list.selectedIndex].value;
					ResetWidgetPreview();
				}
				
				function OnWidgetPlacement(placement)
				{
					document.getElementById('RatingWidgetPlacement').value = placement;
				}
				
				function OnWidgetType(type)
				{
					if (type == "H")
					{
						document.getElementById('ExternalBackgroundBox').disabled = "";
						document.getElementById('RatingWidgetColorSchemeBox').disabled = "";
						document.getElementById('RatingWidgetColorSchemeRow').style.color = "#000000";
						document.getElementById('ExternalBackgroundResetButton').style.visibility = "visible";
					}
					else
					{
						document.getElementById('ExternalBackgroundBox').disabled = "disabled";
						document.getElementById('RatingWidgetColorSchemeBox').disabled = "disabled";
						document.getElementById('RatingWidgetColorSchemeRow').style.color = "#808080";
						document.getElementById('ExternalBackgroundResetButton').style.visibility = "hidden";
					}
					document.getElementById('RatingWidgetType').value = type;
					
					ResetWidgetPreview();
				}
				
				function OnCheckMPWidgets()
				{
					var box = document.getElementById('MPWidgetsBox');
					var input = document.getElementById('FeedwebMPWidgets');
					if (box.checked == true)
						input.value = "1";
					else
						input.value = "0";
				}
				
				function OnCheckCopyrightNotice()
				{
					var box = document.getElementById('CopyrightNoticeBox');
					var input = document.getElementById('FeedwebCopyrightNotice');
					if (box.checked == true)
						input.value = "1";
					else
						input.value = "0";
				}
				
				function OnCheckWidgetPrompt()
				{
					var box = document.getElementById('WidgetPromptBox');
					var input = document.getElementById('InsertWidgetPrompt');
					if (box.checked == true)
						input.value = "1";
					else
						input.value = "0";
				}
				
				function OnCheckAddParagraphs()
				{
					var box = document.getElementById('AddParagraphsBox');
					var input = document.getElementById('AutoAddParagraphs');
					if (box.checked == true)
						input.value = "1";
					else
						input.value = "0";
				}
				
				function OnCheckHideScroll()
				{
					var box = document.getElementById('FrontWidgetHideScrollBox');
					var input = document.getElementById('FrontWidgetHideScroll');
					if (box.checked == true)
						input.value = "1";
					else
						input.value = "0";
				}
				
				function ValidateRatingWidgetWidth()
				{
					var input = document.getElementById("WidgetWidthEdit");
					var width = parseInt(input.value);
					if (isNaN(width))
					{
						window.alert ('<?php _e("Please enter a valid width", "FWTD")?>');
						return 0;
					}

					if (width < 350 || width > 700)
					{
						window.alert ('<?php _e("Width is out of range", "FWTD")?>');
						return 0;
					}
					input.value = width.toString();
					return width;
				}

				function OnSwitchToHtml()
				{
					document.getElementById("WidgetTypeSwitch").value = "*";
				}
							
				function OnSubmitFeedwebSettingsForm()
				{
					if (document.getElementById("WidgetTypeSwitch").value != "*")
					{
						if (ValidateRatingWidgetWidth() == 0)
							return false;
						
						input = document.getElementById("FrontWidgetHeightEdit");
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
					}
					
					var form = document.getElementById("FeedwebSettingsForm");
					form.action ="<?php echo plugin_dir_url(__FILE__)?>feedweb_settings.php";
					form.method = "post";
					return true;
				}
				
				function OnClickFeedwebSettingsTab(tab)
				{
					var divs = document.getElementsByClassName("FeedwebSettingsDiv");
					var tabs = document.getElementsByClassName("FeedwebSettingsTab");
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
				}
			</script>
			<?php wp_referer_field(true)?>
			<link href='<?php echo plugin_dir_url(__FILE__)?>Feedweb.css?v=2.0.7' rel='stylesheet' type='text/css' />
			<input type='hidden' id='DelayResults' name='DelayResults' value='<?php echo $feedweb_data["delay"];?>'/>
			<input type='hidden' id='FeedwebLanguage' name='FeedwebLanguage' value='<?php echo $feedweb_data["language"];?>'/>
			<input type='hidden' id='FeedwebMPWidgets' name='FeedwebMPWidgets' value='<?php echo $feedweb_data["mp_widgets"];?>'/>
			<input type='hidden' id='RatingWidgetType' name='RatingWidgetType' value='<?php echo $feedweb_data["widget_type"];?>'/>
			<input type='hidden' id='AutoAddParagraphs' name='AutoAddParagraphs' value='<?php echo $feedweb_data["add_paragraphs"];?>'/>
			<input type='hidden' id='InsertWidgetPrompt' name='InsertWidgetPrompt' value='<?php echo $feedweb_data["widget_prompt"];?>'/>
			<input type='hidden' id='RatingWidgetPlacement' name='RatingWidgetPlacement' value='<?php echo $feedweb_data["widget_place"];?>'/>
			<input type='hidden' id='RatingWidgetColorScheme' name='RatingWidgetColorScheme' value='<?php echo $feedweb_data["widget_cs"];?>'/>
			<input type='hidden' id='FrontWidgetItemCount' name='FrontWidgetItemCount' value='<?php echo $feedweb_data["front_widget_items"];?>'/>
			<input type='hidden' id='FeedwebCopyrightNotice' name='FeedwebCopyrightNotice' value='<?php echo $feedweb_data["copyright_notice_ex"];?>'/>
			<input type='hidden' id='FrontWidgetHideScroll' name='FrontWidgetHideScroll' value='<?php echo $feedweb_data["front_widget_hide_scroll"];?>'/>
			<input type='hidden' id='FrontWidgetColorScheme' name='FrontWidgetColorScheme' value='<?php echo $feedweb_data["front_widget_color_scheme"];?>'/>
			<br/>
			<table cellpadding="0" cellspacing="0">
				<tr class="FeedwebSettingsTabs">
					<td>
						<a href="#" class="FeedwebSettingsTab" onclick="OnClickFeedwebSettingsTab(0)" 
							style="text-decoration: underline; background-color: #e0e0ff;"><?php _e("Rating Widget", "FWTD")?></a>
						<a href="#" class="FeedwebSettingsTab" onclick="OnClickFeedwebSettingsTab(1)"><?php _e("Front Widget", "FWTD")?></a>
					</td>
				</tr>
				<tr class="FeedwebSettingsContent" style="overflow: hidden;">
					<td>
						<div class="FeedwebSettingsDiv" style="display: block; height: 450px;">
							<table class="FeedwebSettingsTable">
								<tbody>
									<tr>
										<td>
											<span><b><?php _e("Widget Placement:", "FWTD")?></b></span>
										</td>
										<td>
											<div class="RadioDiv">
												<input type="radio" <?php if ($feedweb_data['widget_place']=='1') echo 'checked="checked"'; ?> 
													name="WidgetPlaceRadio" id="WidgetPlaceTopRadio" onclick="OnWidgetPlacement('1')"/>
												<label id="WidgetPlaceTopLabel" for="WidgetPlaceTopRadio">Top</label>
												
												<input type="radio" <?php if ($feedweb_data['widget_place']!='1') echo 'checked="checked"'; ?> 
													name="WidgetPlaceRadio" id="WidgetPlaceBottomRadio" onclick="OnWidgetPlacement('0')"/>
												<label id="WidgetPlaceBottomLabel" for="WidgetPlaceBottomRadio">Bottom</label>
											</div>
										</td>
										<td class="DescriptionColumn">
											<span><i><?php _e("Please choose the placement of the rating widget within a post", "FWTD")?></i></span><br/>
										</td>
									</tr>
									
									<tr <?php if ($feedweb_data['widget_type']=='H') echo " style='display: none;'"; ?> >
										<td>
											<span><b><?php _e("Widget Type:", "FWTD")?></b></span>
										</td>
										<td>
											<input type='submit' class='button button-primary' style='width: 170px;' onclick='OnSwitchToHtml()' value='Upgrade to HTML5'/>
											<input type='hidden' id='WidgetTypeSwitch' name='WidgetTypeSwitch' value='-'/>
										</td>
										<td class="DescriptionColumn">
											<span><i><?php _e("Click to upgrade your rating widgets from Flash to HTML5", "FWTD")?></i></span><br/>
											<span class="DescriptionWarningText">
												<?php _e("Note that Flash is not supported in devices like iPad or iPhone.", "FWTD")?>
												<?php _e("Flash widget will be discontinued after December 31, 2013.", "FWTD")?>
											</span>
										</td>
									</tr>
									
									
									<tr id="RatingWidgetColorSchemeRow" style="height: 64px; vertical-align: top;">
										<td>
											<span style="position: relative; top: 5px;"><b><?php _e("Widget Color Scheme:", "FWTD")?></b></span><br/>
											<span style="position: relative; top: 20px;"><b><?php _e("Widget External Background:", "FWTD")?></b></span>
										</td>
										<td>
											<?php BuildColorSchemeBox($feedweb_data['widget_cs'], true) ?><br/>
											<?php BuildExternalBackgroundControl($feedweb_data['widget_ext_bg']) ?>
										</td>
										<td class="DescriptionColumn">
											<span><i><?php _e("Please choose the color scheme of your HTML rating widgets", "FWTD")?></i></span>
										</td>
									</tr>	
																		
									<tr>
										<td>
											<span><b><?php _e("Widget Language:", "FWTD")?></b></span>
										</td>
										<td style='width: 200px;'>
											<?php BuildLanguageBox($feedweb_data['language'], $feedweb_data['language_set'], false) ?>
										</td>
										<td style='width: 600px;'>
											<span><i>Don't find your language? <a href="mailto://contact@feedweb.net">Help us translate the widget for you!</a></i></span>
										</td>
									</tr>
									<tr>
										<td>
											<span><b><?php _e("Widget width (pixels):", "FWTD")?></b></span>
										</td>
										<td>
											<input id='WidgetWidthEdit' name='WidgetWidthEdit' type='text' value="<?php echo $feedweb_data['widget_width']?>"/>
											<?php BuildResetPreviewButton('WidgetWidthResetButton') ?>
										</td>
										<td>
											<span><i><?php _e("Allowed width: 350 to 700 pixels. Recommended width: 400 to 450 pixels.", "FWTD")?></i></span>
										</td>
									</tr>
									
									<tr id="WidgetPreviewRow">
										<td>
											<span id="WidgetPreviewTitle" onclick="OnShowWidgetPreview()" style="cursor: pointer;"><?php _e("Show Widget Preview >>>", "FWTD")?></span>
										</td>
										<td colspan="2">
											<div id="WidgetPreview" style="display: none;"></div>
										</td>
									</tr>
									
									<tr>
										<td>
											<span><b><?php _e("Widgets at the Home/Front Page:", "FWTD")?></b></span> 				
										</td>
										<td>
											<input <?php if($feedweb_data['mp_widgets'] == "1") echo 'checked="checked"' ?>
											id="MPWidgetsBox" name="MPWidgetsBox" type="checkbox" onchange='OnCheckMPWidgets()'> <?php _e("Display Widgets", "FWTD")?></input>				
										</td>
										<td>
											<span><i><?php _e("Check to display the widgets both in the Front Page and the single post pages.", "FWTD")?></i></span>
										</td>
									</tr>
									<tr>
										<td>
											<span><b><?php _e("Delay displaying results:", "FWTD")?></b></span> 				
										</td>
										<td>
											<?php BuildDelayBox($feedweb_data['delay']) ?>				
										</td>
										<td>
											<span><i><?php _e("Set the period of time you want to hide voting results after the widget is created.", "FWTD")?></i></span>
										</td>
									</tr>
									
									<tr>
										<td>
											<span><b><?php _e("Feedweb Copyright Notice:", "FWTD")?></b></span> 				
										</td>
										<td>
											<input <?php if($feedweb_data['copyright_notice_ex'] == "1") echo 'checked="checked"' ?>
											id="CopyrightNoticeBox" name="CopyrightNoticeBox" type="checkbox" onchange='OnCheckCopyrightNotice()'> <?php _e("Allow")?></input>				
										</td>
										<td style="padding-bottom: 12px; padding-top: 6px;">
											<span><i><?php _e("Please check to display the following text below the widgets: ", "FWTD")?></i></span>
											<?php echo GetCopyrightNotice()?>
										</td>
									</tr>
									
									<tr>
										<td>
											<span><b><?php _e("Prompt to insert widgets:", "FWTD")?></b></span> 				
										</td>
										<td>
											<input <?php if($feedweb_data['widget_prompt'] == "1") echo 'checked="checked"' ?>
											id="WidgetPromptBox" name="WidgetPromptBox" type="checkbox" onchange='OnCheckWidgetPrompt()'> <?php _e("Show")?></input>				
										</td>
										<td>
											<span><i><?php _e("Display a prompt to insert a rating widget when a post is published", "FWTD")?></i></span>
										</td>
									</tr>
									
									<tr>
										<td>
											<span><b><?php _e("Automatically add paragraphs:", "FWTD")?></b></span> 				
										</td>
										<td>
											<input <?php if($feedweb_data['add_paragraphs'] == "1") echo 'checked="checked"' ?>
											id="AddParagraphsBox" name="AddParagraphsBox" type="checkbox" onchange='OnCheckAddParagraphs()'> <?php _e("Add")?></input>				
										</td>
										<td>
											<span><i><?php _e("Surround widgets with paragraph tags:", "FWTD")?></i><b> &lt;P&gt;...&lt;/P&gt;</b></span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="FeedwebSettingsDiv" style="display: none; height: 450px;">
							<table class="FeedwebSettingsTable">
								<tbody>
									<tr>
										<td style="width: 255px;">
											<span><b><?php _e("Front Page Widget Color Scheme:", "FWTD")?></b></span> 				
										</td>
										<td style='width: 10px;'/>
										<td style="width: 200px;">
											<?php BuildColorSchemeBox($feedweb_data['front_widget_color_scheme'], false) ?>				
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
									<tr>
										<td style="width: 255px;">
											<span><b><?php _e("Number of list items:", "FWTD")?></b></span> 				
										</td>
										<td style='width: 10px;'/>
										<td style="width: 200px;">
											<?php BuildItemCountBox(intval($feedweb_data['front_widget_items'])) ?>				
										</td>
										<td style='width: 10px;'/>
										<td style="width: 600px;">
											<span><i><?php _e("Number of items in the Front Widget list", "FWTD")?></i></span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</td>
				</tr>
				<tr class="FeedwebSettingsCommitButton">
					<td>
						<?php echo get_submit_button(__('Save Changes'), 'primary', 'submit', false, "style='width: 200px;'"); GetPurgeInactiveWidgets(); ?>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<script>
		OnWidgetType('<?php echo $feedweb_data['widget_type']?>');
	</script>
	<?php 
}
?>