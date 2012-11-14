<?php
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( ABSPATH.'wp-admin/includes/template.php');


$edit_page_data = null;

function GetSettingsLink()
{
	echo get_bloginfo('url')."/wp-admin/options-general.php?page=feedweb.php";
}

function GetSettingsPrompt()
{
	$count = GetFeedwebOptionsCount();
	if ($count == 0)	// Plugin settings have not been set yet
	{
		$format = __("Dear %s, Please configure your plugin settings before inserting the widget", "FWTD");
		printf($format, wp_get_current_user()->display_name);
	}
}

function GetEditPageData()
{
	global $edit_page_data;
	if ($edit_page_data == null)
	{
		$id = GetPostId();
		$pac = GetPac($id);
		$edit_page_data = GetPageData($pac, true);
	}
	return $edit_page_data;
}

function GetPostTitleControl()
{
	$id = GetPostId();
	$post = get_post($id);
	$title = ConvertHtml($post->post_title);
	
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
		{
			$db_title = $data["title"];
			if ($db_title != null && $db_title != "" && $db_title != $title)
			{
				echo "<select style='width: 100%;' id='TitleBox' name='TitleBox' onchange='OnChangeTitle()'>".
					"<option>$title</option><option>$db_title</option></select>".
					"<input type='hidden' id='TitleText' name='TitleText' value='$title'/>";
				return;
			}
		}
	}
	echo "<input type='text' id='TitleText' name='TitleText' value='$title' style='width:100%;' readonly='readonly'/>";
}

function GetPostSubTitleControl()
{
	$sub_title = "";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$sub_title = $data["brief"];
	}
	echo "<input type='text' id='SubTitleText' name='SubTitleText' style='width:100%;' value='$sub_title'/>"; 
}

function GetPostImageUrlControl()
{
	$img = "";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$img = $data["img"];
	}
	echo "<input type='text' readonly='readonly' style='width: 450px;' id='WidgetImageUrl' name='WidgetImageUrl' value='$img'/>";
}

function GetLanguageBox()
{
	$lang = null;
	$set = false;
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$lang = $data["lang"];
	}
	
	if ($lang == null || $lang == "")
	{
		$data = GetFeedwebOptions();
		$lang = $data['language'];
		$set = $data['language_set'];
	}
	else
		$set = true;
	
	$lang = BuildLanguageBox($lang, $set, 'width: 310px; height: 30px;', true);
	echo "<input type='hidden' id='LanguageText' name='LanguageText' value='$lang'/>";
}

function GetCategoryControl()
{
	$text = "";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$text = $data["categories"];
	}
	else
	{
		$id = GetPostId();
		$categories = get_the_category($id);
		foreach($categories as $category)
			if ($category->cat_name != __("Uncategorized"))
			{
				if ($text != "")
					$text .= ", ";
				$text .= $category->cat_name;
			}
	}
	echo "<input type='text' id='CategoryText' name='CategoryText' value='$text' style='width:100%;'/>";
}

function GetTagControl()
{
	$text = "";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$text = $data["tags"];
	}
	else
	{
		$id = GetPostId();
		$tags = wp_get_post_tags($id);
		foreach($tags as $tag)
		{
			if ($text != "")
				$text .= ", ";
			$text .= $tag->name;
		}
	}
	echo "<input type='text' id='TagText' name='TagText' value='$text' style='margin-left: 8px; width:310px;'/>";
}

function GetAuthorControl()
{
	$id = GetPostId();
	$post = get_post($id);
	$data = get_userdata($post->post_author);
	
	$names = array();
	if ($data->user_firstname != "" || $data->user_lastname != "")
	{
		$name = $data->user_firstname." ".$data->user_lastname;
		$names[$name] = "0";

		$name = $data->user_lastname." ".$data->user_firstname;
		$names[$name] = "0";
	}
	$names[$data->display_name] = "0";
	$names[$data->nickname] = "0";
		
	$default_id = null;
	$default_name = null;
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
		{
			$name = $data["author"];
			$id = $data["author_id"];
			if ($name != null && $name != "" && $id != null && $id != "")
			{
				$names[$name] = $id;
				$default_id = $id;
				$default_name = $name;
			}
		}
	}
	
	echo "<select style='width:100%;' id='AuthorBox' name='AuthorBox' onchange='OnChangeAuthor()'>";
	foreach ($names as $key => $value)
	{
		$selected = "";
		if ($default_name == null)
		{
			$default_id = $value;
			$default_name = $key;
		}
		else
			if ($key == $default_name)
				$selected = "selected='selected'";
				
		echo "<option value='$value' $selected>$key</option>";
	}
	echo "</select><input type='hidden' id='AuthorText' name='AuthorText' value='$default_name'/><input type='hidden' id='AuthorId' name='AuthorId' value='$default_id'/>";
}

function GetUrlControl()
{
	$id = GetPostId();
	$url = ConvertHtml(get_permalink($id));
	
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
		{
			$db_url = $data["url"];
			if ($db_url != null && $db_url != "" && $db_url != $url)
			{
				echo "<select style='width: 100%;' id='UrlBox' name='UrlBox' onchange='OnChangeUrl()'>".
					"<option>$url</option><option>$db_url</option></select>".
					"<input type='hidden' id='UrlText' name='UrlText' value='$url'/>";
				return;
			}
		}
	}
	echo "<input type='text' id='UrlText' name='UrlText' value='$url' style='width:100%;' readonly='readonly'/>";
}

function GetPostId()
{
	return $_GET["wp_post_id"];
}

function GetRemoveWidgetPrompt()
{
	$id = GetPostId();
	$post = get_post($id);
	$format = __("The rating widget in the post '%s' will be removed", "FWTD");
	printf($format, ConvertHtml($post->post_title));
}

function GetFormAction()
{
	$action = "widget_commit.php?wp_post_id=".GetPostId();
	if ($_GET["mode"] == "edit")
		$action = $action."&feedweb_cmd=UPD";
	echo $action;
}

function GetQuestionList($pac)
{
	$data = GetFeedwebOptions();
	$site = PrepareParam(site_url());
	$query = GetFeedwebUrl()."FBanner.aspx?action=gql&lang=".$data["language"]."&site=".$site;
	if ($pac != null) 
		$query = $query."&pac=".$pac;

	$response = wp_remote_get ($query, array('timeout' => 60));
	if (is_wp_error ($response))
		return null;
	
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
			return ReadQuestionList($dom->documentElement);
	return null;
}

function BuildQuestionsListControl()
{
	echo "<select size='4' id='QuestionsList' name='QuestionsList' style='width:100%;height:100px;'>";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
		{
			$questions = $data["questions"];
			if ($questions != null)
				foreach ($questions as $key => $value)
					echo "<option value='$value[0]'>$value[1]</option>";
		}
	}
	echo "</select>";
}

function YesNoQuestionPrompt()
{
	$wiki_url = __("http://en.wikipedia.org/wiki/Yes%E2%80%93no_question", "FWTD");
	$format = __("must be a %sYES / NO%s question", "FWTD");
	$tag = sprintf("<a href='%s'>", $wiki_url);
	printf($format, $tag, "</a>");
}

?>

<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<script type="text/javascript">
			function OnChangeTitle()
			{
				var input = document.getElementById("TitleText");
				var box = document.getElementById("TitleBox");
				input.value = box.options[box.selectedIndex].value; 
			}
			
			function OnChangeUrl()
			{
				var input = document.getElementById("UrlText");
				var box = document.getElementById("UrlBox");
				input.value = box.options[box.selectedIndex].value; 
				window.alert(input.value);
			}
			
			function OnChangeAuthor()
			{
				var name_input = document.getElementById("AuthorText");
				var id_input = document.getElementById("AuthorId");
				var box = document.getElementById("AuthorBox");
				
				id_input.value = box.options[box.selectedIndex].value; 
				name_input.value = box.options[box.selectedIndex].label; 
			}
		
			function OnChangeLanguage()
			{
				var box = document.getElementById("WidgetLanguageBox");
				var input = document.getElementById("LanguageText");
				
				input.value = box.options[box.selectedIndex].value; 
			}
			
			function AddQuestion(text, value)
			{
				var list = document.getElementById("QuestionsList");
				for (var index = 0; index < list.options.length; index++)
				{
					if (text == list.options[index].text)
					{
						var message = "<?php _e("Question '{0}' is already selected", "FWTD")?>";
						var warning = message.replace("{0}", text);
						window.alert(warning);
						return;
					}
				}
				
				var limit = 3; // Obtain from capabilities
				if (list.options.length >= limit)
				{
					var message = "<?php _e("A widget cannot contain more than {0} questions", "FWTD")?>";
					var warning = message.replace("{0}", limit.toString());
					window.alert(warning);
					return;
				}
				
				var option = document.createElement("Option");
				option.text = text;
				option.value = value;
	            list.options.add(option);
			}
		
			function OnSelect() 
			{ 
				var combo = document.getElementById("OldQuestionsList");
				var value = combo.options[combo.selectedIndex].value;
				var text = combo.options[combo.selectedIndex].text;
				AddQuestion(text, value);
			}

			function OnAddNew()
			{
				<?php 
				$data = GetFeedwebOptions();
				echo "var lang='".$data["language"]."';";
				if (IsRTL($data["language"]))
					echo " var rtl=true; ";
				else
					echo " var rtl=false; ";
				?>

				var edit = document.getElementById("NewQuestionText");
				if (edit.value.length == 0)
				{
					var text = "<?php _e("Please specify a question", "FWTD")?>";
					window.alert(text);
					return;
				}

				var text = edit.value;
				if (rtl)
				{
					if (text.charAt(text.length - 1) == '?')
						text = text.substr(0, text.length - 1);
	
					if (text.charAt(0) != '?')
						text = "?" + text;
				}

				AddQuestion(text, "");
			}

			function MoveCurrentItem(list, index)
			{
				var value = list.options[index].value;
				var text = list.options[index].text;
				
				list.options[index].value = list.options[list.selectedIndex].value;
				list.options[index].text = list.options[list.selectedIndex].text;
				list.options[list.selectedIndex].value = value;
				list.options[list.selectedIndex].text = text;
				list.selectedIndex = index;
			}
			
			function OnMoveUp()
			{
				var list = document.getElementById("QuestionsList");
				if (list.selectedIndex <= 0)
					return;

				MoveCurrentItem(list, list.selectedIndex - 1);
			}
						
			function OnMoveDown()
			{
				var list = document.getElementById("QuestionsList");
				if (list.selectedIndex < 0 || list.selectedIndex >= list.options.length - 1)
					return;

				MoveCurrentItem(list, list.selectedIndex + 1);
			}
			
			function OnRemove()
			{
				var list = document.getElementById("QuestionsList");
				if (list.selectedIndex < 0)
				{
					window.alert ('<?php _e("Please select a question to remove", "FWTD")?>');
					return;
				}

				var result = window.confirm('<?php _e("The question will be removed. Proceed?", "FWTD")?>');
	            if (result == true)
	            	list.remove(list.selectedIndex);
			}

			function OnBack()
			{
				var question_div = document.getElementById("WidgetQuestionDiv");
				var picture_div = document.getElementById("WidgetPictureDiv");
				var data_div = document.getElementById("WidgetDataDiv");
				
				if (question_div.style.visibility == "visible")
				{
					question_div.style.visibility = "hidden";
					picture_div.style.visibility = "visible";
				}
				else
				{
					picture_div.style.visibility = "hidden";
					data_div.style.visibility = "visible";
				}
			}
			
			function FillQuestionList()
			{
				var list = document.getElementById("OldQuestionsList");
				var box = document.getElementById("WidgetLanguageBox");
				var lang = box.options[box.selectedIndex].value;
				var url = "./question_query.php?lang=" + lang;
				var request = new XMLHttpRequest();
				
				request.open("GET", url, false);
				request.send();
				list.innerHTML = request.responseText;
			}
			
			function OnNext()
			{
				var question_div = document.getElementById("WidgetQuestionDiv");
				var picture_div = document.getElementById("WidgetPictureDiv");
				var data_div = document.getElementById("WidgetDataDiv");

				if (data_div.style.visibility == "visible")
				{
					var tag = document.getElementById("TagText");
					var category = document.getElementById("CategoryText");
					if (tag.value.length > 250 || category.value.length > 250)
					{
						window.alert('<?php _e("The tags/categories text is limited to 250 characters.", "FWTD")?>');
						return;
					}
					
					FillQuestionList();
				
					picture_div.style.visibility = "visible";
					data_div.style.visibility = "hidden";
					
					var url = document.getElementById("WidgetImageUrl").value;
					if (url != null && url != "")
					{
						var image = document.getElementById("WidgetImage");
						image.style.display = "inline";
						image.src = url;
					}
				}
				else
				{
					question_div.style.visibility = "visible";
					picture_div.style.visibility = "hidden";
				}
			}
		
			function OnCancel() 
			{ 
				window.parent.tb_remove(); 
			}
			
			function OnSetImage()
			{
				var url = window.prompt('<?php _e("Please enter image Url", "FWTD") ?>');
				if (url != null && url != "")
				{
					var image = document.getElementById("WidgetImage");
					image.style.display = "inline";
					image.src = url;
				
					document.getElementById("WidgetImageUrl").value = url;
				}
			}
			
			function OnLoadWidgetImage()
			{
				var box = document.getElementById("WidgetImageBox");
				var img = document.getElementById("WidgetImage");
				img.style.display = "inline";
				
				var max_height = box.clientHeight - 10;
				var max_width = box.clientWidth - 10;
				
				var scale_y = max_height / img.clientHeight;
				var scale_x = max_width / img.clientWidth;
				var scale = Math.min(scale_x, scale_y);
				
				var img_height = Math.floor(img.clientHeight * scale);
				img.style.height = img_height.toString() + "px";

				var top = (box.clientHeight - img_height) / 2;
				img.style.marginTop = top.toString() + "px";
			}
					
			function OnDeleteMouseOver()
			{
				var button = document.getElementById("DeleteButton");
				button.style.backgroundColor = '#ff0000';
				button.style.color = '#ffffff';
			}
			
			function OnDeleteMouseOut() 
			{
				var button = document.getElementById("DeleteButton");
				button.style.backgroundColor = '#ffffff';
				button.style.color = '#000000';
			}
			
			function OnDelete()
			{
				if (window.confirm("<?php GetRemoveWidgetPrompt()?>") == true)
					window.location.href = "widget_commit.php?feedweb_cmd=DEL&wp_post_id=" + <?php echo GetPostId()?>;
			}
			
			function OnSubmitForm()
			{
				var input = document.getElementById("WidgetQuestionsData");
				input.value = "";
					
				var list = document.getElementById("QuestionsList");
				for (var index = 0; index < list.options.length; index++)
				{
					var item = list.options[index].value;
					if (item == "") // New question
						item = "@" + list.options[index].text;
					if (index == 0)
						input.value = item;
					else
						input.value += "|" + item; 
				}
				
				var action = "<?php GetFormAction()?>";
				document.forms[0].action = action;
				document.forms[0].method = "post";
				return true;
			}
			
			function OnLoad()
			{
				var message = "<?php GetSettingsPrompt()?>";
				if (message == "")
					return;
					
				if (window.confirm(message) == true)
				{
					window.parent.tb_remove();
					window.parent.location.href = "<?php GetSettingsLink()?>";
				}
			}
		</script>

		<link rel='stylesheet' href='<?php echo get_bloginfo('url') ?>/wp-admin/load-styles.php?c=0&amp;dir=ltr&amp;load=admin-bar,wp-admin' type='text/css' media='all' />
		<link rel='stylesheet' id='thickbox-css'  href='<?php echo get_bloginfo('url') ?>/wp-includes/js/thickbox/thickbox.css' type='text/css' media='all' />
		<link rel='stylesheet' id='colors-css'  href='<?php echo get_bloginfo('url') ?>/wp-admin/css/colors-fresh.css' type='text/css' media='all' />
		<link href='<?php echo plugin_dir_url(__FILE__)?>Feedweb.css' rel='stylesheet' type='text/css' />
		
	</head>
	<body style="margin: 0px;" onload="OnLoad()">
		<div id="WidgetDialog" >
		 	<form id="WidgetDialogForm" onsubmit="return OnSubmitForm();">
				<input type="hidden" name="WidgetQuestionsData" id="WidgetQuestionsData"/>
				<div id="WidgetDataDiv" name="WidgetDataDiv" style="visibility: visible;">
			 		<table id="FirstPhaseTable" name="FirstPhaseTable" class="wp-list-table widefat fixed posts" cellspacing="0">
						<tbody>
							<tr style='height: 1px;'>
								<td style='width: 10px;'/>
								<td style='width: 180px;'/>
								<td style='width: 10px;'/>
								<td style='width: 120px;'/>
								<td style='width: 100px;'/>
								<td style='width: 10px;'/>
							</tr>
							<tr>
								<td/>
								<td colspan="4">
									<span id='TitleLabel'><b><?php _e("Title:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 36px;">
								<td/>
								<td colspan="4"> 
									<?php GetPostTitleControl() ?>
								</td>
								<td/>
							</tr>
							
							<tr>
								<td/>
								<td colspan="4">
									<span id='SubTitleLabel'><b><?php _e("Sub-Title:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 36px;">
								<td/>
								<td colspan="4"> 
									<?php GetPostSubTitleControl() ?>
								</td>
								<td/>
							</tr>
							
							<tr>
								<td/>
								<td colspan="2">
									<span id='AuthorLabel'><b><?php _e("Author:", "FWTD")?></b></span>
								</td>
								<td colspan="2">
									<span id='LanguageLabel' style='padding-left: 20px;'><b><?php _e("Post Language:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 38px;">
								<td/>
								<td colspan="2"> 
									<?php GetAuthorControl() ?>
								</td>
								<td colspan="2" style="text-align: right;"> 
									<?php GetLanguageBox() ?>
								</td>
								<td/>
							</tr>
							
							<tr>
								<td/>
								<td colspan="2">
									<span id='CategoryLabel'><b><?php _e("Categories")?></b></span>
								</td>
								<td colspan="2">
									<span id='TagLabel' style='padding-left: 20px;'><b><?php _e("Tags")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 38px;">
								<td/>
								<td colspan="2"> 
									<?php GetCategoryControl() ?>
								</td>
								<td colspan="2" style="text-align: right;"> 
									<?php GetTagControl() ?>
								</td>
								<td/>
							</tr>
							
							<tr>
								<td/>
								<td colspan="4">
									<span id='UrlLabel'><b><?php _e("URL:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 36px;">
								<td/>
								<td colspan="4"> 
									<?php GetUrlControl() ?>
								</td>
								<td/>
							</tr>
							
							<tr>
								<td colspan="6"/>
							</tr>							
							<tr>
								<td/>
								<td>
									<?php 
										if($_GET["mode"] == "edit") 
											echo "<input type='button' value='".__("Remove Widget", "FWTD")."' style='width: 150px;' id='DeleteButton' name='DeleteButton'".
												"onmouseover='OnDeleteMouseOver()' onmouseout='OnDeleteMouseOut()' onclick='OnDelete()'/>";
									?>								
								</td>
								<td/>
								<td style='text-align: right;'>
									<input type='button' value='<?php _e("Next >", "FWTD")?>' style='width: 150px;' onclick='OnNext()'/>
								</td>
								<td>
									 <input type='button' value='<?php _e("Cancel")?>' style='width: 140px;' onclick='OnCancel()'/>
								</td>
								<td/>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- overflow: hidden; -->
				<div id="WidgetPictureDiv" name="WidgetPictureDiv" style="visibility: hidden; position: absolute; top: 0; left: 0;">
					<table id="WidgetPictureTable" name="WidgetPictureTable" class="wp-list-table widefat fixed posts" cellspacing="0">
						<tbody>
							<tr style="height: 5px;">
								<td style='width: 10px;'/>
								<td style='width: 150px;'/>
								<td style='width: 250px;'/>
								<td style='width: 150px;'/>
								<td style='width: 10px;'/>
							</tr>
							<tr>
								<td/>
								<td colspan="3">
									<span id='ImageUrlLabel'><b><?php _e("Image Url")?></b></span>
								</td>
								<td/>
							</tr>
							<tr>
								<td/>
								<td colspan="2">
									<?php GetPostImageUrlControl() ?>
								</td>
								<td>
									<input type='button' value='<?php _e("Set", "FWTD")?>' style='width: 140px;' onclick='OnSetImage()'/>
								</td>
								<td/>
							</tr>
							<tr style="height: 5px;">
								<td colspan="5"/>
							</tr>
							<tr style="height: 250px; min-height: 250px; max-height: 250px;" >
								<td/>
								<td colspan="3" style="text-align: center;">
									<div id="WidgetImageBox" style="text-align: center; height: 250px; overflow: hidden; border: 1px solid #C0C0C0; margin-right: 24px;">
										<img id="WidgetImage" onload="OnLoadWidgetImage()" style="position: relative; display: none;"/>
									</div>
								</td>
								<td/>
							</tr>
							<tr>
								<td/>
								<td>
									<input type='button' value='<?php _e("< Back", "FWTD")?>' style='width: 150px;' onclick='OnBack()'/>								
								</td>
								<td style='text-align: right;'>
									<input type='button' value='<?php _e("Next >", "FWTD")?>' style='width: 150px;' onclick='OnNext()'/>
								</td>
								<td>
									 <input type='button' value='<?php _e("Cancel")?>' style='width: 140px;' onclick='OnCancel()'/>
								</td>
								<td/>
							</tr>
						</tbody>
					</table>
				</div>
				
				<div id="WidgetQuestionDiv" name="WidgetQuestionDiv" style="visibility: hidden; position: absolute; top: 0; left: 0;">
			 		<table id="WidgetQuestionTable" name="WidgetQuestionTable" class="wp-list-table widefat fixed posts" cellspacing="0">
						<tbody>
							<tr style="height: 5px;">
								<td style='width: 10px;'/>
								<td style='width: 175px;'/>
								<td style='width: 175px;'/>
								<td style='width: 100px;'/>
								<td style='width: 10px;'/>
							</tr>
							<tr>
								<td/>
								<td colspan="3">
									<span id='OldQuestionsLabel'><b><?php _e("Existing Questions:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 36px;">
								<td/>
								<td colspan="2"> 
									<select id='OldQuestionsList' name='OldQuestionsList' style='width:100%;'>
									</select>
								</td>
								<td>
									<input type="button" value='<?php _e("Select")?>' style='width: 100%;' onclick='OnSelect()'/>
								</td>
								<td/>
							</tr>
							<tr height='5px'>
								<td colspan='5'/>
							</tr>
							<tr>
								<td/>
								<td colspan="3">
									<span id='NewQuestionLabel'><b><?php _e("New Question", "FWTD")?></b> (<i><?php YesNoQuestionPrompt()?></i>)<b>:</b></span>
								</td>
								<td/>
							</tr>
							<tr>
								<td/>
								<td colspan="2">
									<input type='text' id='NewQuestionText' name='NewQuestionText' style='width:100%;'/>
								</td>
								<td>
									<input type='button' value='<?php _e("Add")?>' onclick="OnAddNew()" style='width: 100%;'/>
								</td>
								<td/>
							</tr>
							<tr height='5px'>
								<td colspan='5'/>
							</tr>
							<tr>
								<td/>
								<td colspan="3">
									<span id='QuestionsLabel'><b><?php _e("Selected Questions:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr>
								<td rowspan='3'/>
								<td rowspan='3' colspan='2' style='height: 116px;'>
									<?php BuildQuestionsListControl() ?>
								</td>
								<td valign='top'>
									<input type='button' value='<?php _e("Move Up", "FWTD")?>' onclick='OnMoveUp()' style='width: 100%;'/>
								</td>
								<td rowspan='3'/>
							</tr>
							<tr>
								<td>
									<input type='button' value='<?php _e("Move Down", "FWTD")?>' onclick='OnMoveDown()' style='width: 100%;'/>
								</td>
							</tr>
							<tr>
								<td valign='bottom'>
									<input type='button' value='<?php _e("Remove")?>' onclick='OnRemove()' style='width: 100%;'/>
								</td>
							</tr>
							<tr height='34px'>
								<td colspan='5'/>
							</tr>
							<tr>
								<td/>
								<td>
									<input type='button' value='<?php _e("Cancel")?>' style='width: 150px;' onclick='OnCancel()'/>
								</td>
								<td style="text-align: right;">
									<input type='button' value='<?php _e("< Back", "FWTD")?>' style='width: 150px;' onclick='OnBack()'/> 
								</td>
								<td>
									<?php echo get_submit_button(__("Done", "FWTD"), "primary", "submit", false, "style='width: 120px;'") ?>								
								</td>
								<td/>
							</tr>
						</tbody>
					</table>
				</div>
			</form>
		</div>
	</body>
</html>