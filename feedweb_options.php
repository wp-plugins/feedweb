<?php

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once( ABSPATH.'wp-load.php');
include_once( ABSPATH.'wp-admin/includes/plugin.php' );

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

function BuildItemCountBox($number)
{
	echo "<select id='ItemCountBox' name='ItemCountBox' style='width: 99%;' onchange='OnChangeItemCount()'>";
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
		echo "<select id='RatingWidgetColorSchemeBox' name='RatingWidgetColorSchemeBox' style='width: 99%;' onchange='OnChangeRatingWidgetColorScheme()'>";
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
			<script type="text/javascript">
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
				}
				
				function OnWidgetType(type)
				{
					if (type == "H")
					{
						document.getElementById('RatingWidgetColorSchemeBox').disabled = "";
						document.getElementById('RatingWidgetColorSchemeRow').style.color = "#000000";
						//document.getElementById('RatingWidgetColorSchemeRow').style.display = "table-row";
					}
					else
					{
						document.getElementById('RatingWidgetColorSchemeBox').disabled = "disabled";
						document.getElementById('RatingWidgetColorSchemeRow').style.color = "#808080";
						//document.getElementById('RatingWidgetColorSchemeRow').style.display = "none";
					}
					document.getElementById('RatingWidgetType').value = type;
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
				
				function OnCheckAtContentWidget()
				{
					var box = document.getElementById('AtContentWidgetBox');
					var input = document.getElementById('AtContentWidgetCheck');
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

				function OnSubmitFeedwebSettingsForm()
				{
					var input = document.getElementById("WidgetWidthEdit");
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
			<link href='<?php echo plugin_dir_url(__FILE__)?>Feedweb_2_0.css' rel='stylesheet' type='text/css' />
			<input type='hidden' id='DelayResults' name='DelayResults' value='<?php echo $feedweb_data["delay"];?>'/>
			<input type='hidden' id='FeedwebLanguage' name='FeedwebLanguage' value='<?php echo $feedweb_data["language"];?>'/>
			<input type='hidden' id='FeedwebMPWidgets' name='FeedwebMPWidgets' value='<?php echo $feedweb_data["mp_widgets"];?>'/>
			<input type='hidden' id='RatingWidgetType' name='RatingWidgetType' value='<?php echo $feedweb_data["widget_type"];?>'/>
			<input type='hidden' id='AutoAddParagraphs' name='AutoAddParagraphs' value='<?php echo $feedweb_data["add_paragraphs"];?>'/>
			<input type='hidden' id='InsertWidgetPrompt' name='InsertWidgetPrompt' value='<?php echo $feedweb_data["widget_prompt"];?>'/>
			<input type='hidden' id='RatingWidgetColorScheme' name='RatingWidgetColorScheme' value='<?php echo $feedweb_data["widget_cs"];?>'/>
			<input type='hidden' id='FrontWidgetItemCount' name='FrontWidgetItemCount' value='<?php echo $feedweb_data["front_widget_items"];?>'/>
			<input type='hidden' id='FeedwebCopyrightNotice' name='FeedwebCopyrightNotice' value='<?php echo $feedweb_data["copyright_notice"];?>'/>
			<input type='hidden' id='AtContentWidgetCheck' name='AtContentWidgetCheck' value='<?php echo $feedweb_data["atcontent_widget_check"];?>'/>
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
				<tr class="FeedwebSettingsContent">
					<td>
						<div class="FeedwebSettingsDiv" style="display: block; height: 450px;">
							<table class="FeedwebSettingsTable">
								<tbody>
									<tr>
										<td style='width: 255px;'>
											<span><b><?php _e("Widget Type:", "FWTD")?></b></span>
										</td>
										<td style='width: 10px;'/>
										<td style='width: 200px;'>
											<input type="radio" <?php if ($feedweb_data['widget_type']=='F') echo 'checked="checked"'; ?> 
												name="WidgetTypeRadio" id="WidgetTypeFlashRadio" onclick="OnWidgetType('F')"> Flash</input><br/>
											<input type="radio" <?php if ($feedweb_data['widget_type']=='H') echo 'checked="checked"'; ?> 
												name="WidgetTypeRadio" id="WidgetTypeHTML5Radio" onclick="OnWidgetType('H')"> HTML5</input>
										</td>
										<td style='width: 10px;'/>
										<td style='width: 600px;'>
											<span><i><?php _e("Please choose the type of rating widget", "FWTD")?></i></span>
										</td>
									</tr>
									
									<tr id="RatingWidgetColorSchemeRow">
										<td style='width: 255px;'>
											<span><b><?php _e("Widget Color Scheme:", "FWTD")?></b></span>
										</td>
										<td style='width: 10px;'/>
										<td style='width: 200px;'>
											<?php BuildColorSchemeBox($feedweb_data['widget_cs'], true) ?>				
										</td>
										<td style='width: 10px;'/>
										<td style='width: 600px;'>
											<span><i><?php _e("Please choose the color scheme of your HTML rating widgets", "FWTD")?></i></span>
										</td>
									</tr>
																		
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
									
									<tr>
										<td>
											<span><b><?php _e("Prompt to insert widgets:", "FWTD")?></b></span> 				
										</td>
										<td />
										<td>
											<input <?php if($feedweb_data['widget_prompt'] == "1") echo 'checked="checked"' ?>
											id="WidgetPromptBox" name="WidgetPromptBox" type="checkbox" onchange='OnCheckWidgetPrompt()'> <?php _e("Show")?></input>				
										</td>
										<td />
										<td>
											<span><i><?php _e("Display a prompt to insert a rating widget when a post is published", "FWTD")?></i></span>
										</td>
									</tr>
									
									<tr>
										<td>
											<span><b><?php _e("Automatically add paragraphs:", "FWTD")?></b></span> 				
										</td>
										<td />
										<td>
											<input <?php if($feedweb_data['add_paragraphs'] == "1") echo 'checked="checked"' ?>
											id="AddParagraphsBox" name="AddParagraphsBox" type="checkbox" onchange='OnCheckAddParagraphs()'> <?php _e("Add")?></input>				
										</td>
										<td />
										<td>
											<span><i><?php _e("Surround widgets with paragraph tags:", "FWTD")?></i><b> &lt;P&gt;...&lt;/P&gt;</b></span>
										</td>
									</tr>
									
									<tr style="visibility: 
										<?php 
											if (AtContentIncompatibleVersion() != null)
												echo "visible";
											else
												echo "hidden";
										?>;">
										<td>
											<span><b><?php _e("Disable double widget appearance:", "FWTD")?></b></span> 				
										</td>
										<td />
										<td>
											<input <?php if($feedweb_data['atcontent_widget_check'] == "1") echo 'checked="checked"' ?>
											id="AtContentWidgetBox" name="AtContentWidgetBox" type="checkbox" onchange='OnCheckAtContentWidget()'> <?php _e("Disable")?></input>				
										</td>
										<td />
										<td>
											<span style="color: Red;"><b><?php _e("IMPORTANT!", "FWTD")?></b></span>
											<span><i>Please check if the rating widgets appear twice in your posts.</i></span>
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