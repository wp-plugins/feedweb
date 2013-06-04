<?php
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( ABSPATH.'wp-admin/includes/template.php');

if (!current_user_can('manage_options'))
	wp_die(__("You are not allowed to be here"));

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
	echo "<input type='text' id='TitleText' name='TitleText' value='$title' style='width:100%;'/>";
}

function GetPostSummaryControl()
{
	$summary = "";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$summary = $data["brief"];
	}
	echo "<textarea id='SummaryText' name='SummaryText'>$summary</textarea>"; 
}

function GetDefaultPostImage($id)
{
	$image = get_the_post_thumbnail($id, 'full');  
	if ($image != null)
	{
		$dom = new DOMDocument;
		if ($dom->loadHTML($image) == true)
		{
			$nodes = $dom->getElementsByTagName('img');
			if ($nodes != null)
				foreach($nodes as $node)
					return $node->getAttribute("src");
		}
	}
	return null;
}

function LoadTermsOfService()
{
	$url = GetFeedwebUrl()."FeedwebPluginTermsOfService.txt";
	$response = wp_remote_get ($url, array('timeout' => 30));
	if (is_wp_error ($response))
		return;
	
	echo $response['body'];
}


function UrlRelativeToAbsolute($rel)
{
	$base = get_bloginfo('url');
    
	/* return if already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '') 
		return $rel;

    /* queries and anchors */
    if ($rel[0]=='#' || $rel[0]=='?') 
		return $base.$rel;

    /* parse base URL and convert to local variables: $scheme, $host, $path */
    extract(parse_url($base));

    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);

    /* destroy path if relative url points to root */
    if ($rel[0] == '/') $path = '';

    /* dirty absolute URL */
    $abs = "$host$path/$rel";

    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

    /* absolute URL is ready! */
    return $scheme.'://'.$abs;
}

function ExtractPostImages($id)
{
	$post = get_post($id);
	$dom = new DOMDocument;
	if ($dom->loadHTML($post->post_content) == false)
		return null;
	
	$nodes = $dom->getElementsByTagName('img');
	if ($nodes == null)
		return null;
	if ($nodes->length == 0)
		return null;
		
	$images = array();
	foreach($nodes as $node)
	{
		$src = UrlRelativeToAbsolute($node->getAttribute("src"));
		$alt = $node->getAttribute("alt");
		$images[$src] = $alt;
	}
	return $images;
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
	
	$id = GetPostId();
	$images = ExtractPostImages($id);
	$default_image = GetDefaultPostImage($id);
	if ($images != null || $default_image != null)
	{
		echo "<select id='WidgetImageList' name='WidgetImageList' onchange='OnChangeImage()' style='width: 450px;' >";
		
		if ($default_image != null)
		{
			if ($img == null || $img == "")
				$img = $default_image;
			
			if ($img == $default_image)
				echo "<option selected='selected'>$default_image</option>";
			else
				echo "<option>$default_image</option>";
		}
		
		if ($images != null)
			foreach( $images as $key => $value )
				if ($key == $img)
					echo "<option selected='selected'>$key</option>";
				else
					echo "<option>$key</option>";
		
		echo "</select>";
		echo "<input type='hidden' id='WidgetImageUrl' name='WidgetImageUrl' value='$img'/>";
	} 
	else
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
	
	$lang = BuildLanguageBox($lang, $set, true);
	echo "<input type='hidden' id='LanguageText' name='LanguageText' value='$lang'/>";
}


function GetPublishWidgetCheckBox()
{
	$checked = "";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$checked = ($data["visible"] ? "checked" : "");
	}
	echo "<input id='PublishWidgetCheckBox' type='checkbox' onclick='OnClickPublishWidgetBox()' $checked />";
	echo "<span id='PublishWidgetLabel'>".__("Publish your post in the Feedweb's Readers Community Portal", "FWTD")."</span>";
	
	$placeholders = array("[", "]", "{", "}");
	$text = __("By clicking ['Next'] you agree to our {Terms of service}", "FWTD");
	$markup = array("<b>", "</b>", "<a href='#' onclick='OnShowTermsOfService()'>", "</a>");
	echo "<span id='TermsOfServiceDisclaimer'>".str_replace($placeholders, $markup, $text)."</span>";
}

function GetCensorshipBox()
{
	$selected = "G";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$selected = $data["censorship"];
	}
	
	echo "<select id='CensorshipBox' onchange='OnSelectCensorship()'>";
	
	$types = array(
		"G"  => __("Suitable for any audience type", "FWTD"), 
		"PG" => __("May contain rude gestures, provocatively dressed individuals; the lesser swear words, or mild violence", "FWTD"), 
		"R"  => __("May contain such things as harsh profanity, intense violence, nudity, or hard drug use", "FWTD"), 
		"X"  => __("May contain hardcore sexual imagery or extremely disturbing violence", "FWTD") );
	
	foreach ($types as $type => $text)
		if ($type == $selected)
			echo "<option selected='selected'>$type</option>";
		else
			echo "<option>$type</option>";
	echo "</select>";
	
	foreach ($types as $type => $text)
		echo "<span class='CensorshipText' id='$type_CensorshipText'>$text</span>";
}

function GetAdContentCheckBox()
{
	$checked = "";
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
			$checked = ($data["ad_content"] ? "checked" : "");
	}
	
	$text = __("If this posts main purpose is to advertise a certain item or product and not to give information please check here.", "FWTD");
	$warning = __("If your post is for advertising and you do not check the box you are violating the license agreement.", "FWTD");

	echo "<input id='AdContentCheckBox' title='$text' type='checkbox' $checked />";
	echo "<span id='AdContentLabel'>".__("The post contains advertising material", "FWTD")."</span>";
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
	echo "<select size='4' id='QuestionsList' style='width: 460px; height:100px;'>";
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
			
			function OnClickPublishWidgetBox()
			{
				var span = document.getElementById("TermsOfServiceDisclaimer");
				var box = document.getElementById("PublishWidgetCheckBox");
				if (span == null || box == null)
					return;
				
				if (box.checked == true)
					span.style.display = "block";
				else
					span.style.display = "none";
			}
			
			function OnShowTermsOfService()
			{
				document.getElementById("WidgetTitleDiv").style.visibility = "hidden";
				document.getElementById("TermsOfServiceDiv").style.display = "block";
				document.getElementById("WizardNavigatorDiv").style.display = "none";
				document.getElementById('TermsOfServiceText').focus();
				
			}
			
			function OnCloseTermsOfService()
			{
				document.getElementById("TermsOfServiceDiv").style.display = "none";
				document.getElementById("WizardNavigatorDiv").style.display = "block";
				document.getElementById("WidgetTitleDiv").style.visibility = "visible";
			}
			
			function OnSelectCensorship()
			{
				var spans = document.getElementsByClassName("CensorshipText");
				for (var index = 0; index < spans.length; index++)
					spans[index].style.display = "none";
					
				var box = document.getElementById("CensorshipBox");
				spans[box.selectedIndex].style.display = "block";
			}
						
			function OnChangeImage()
			{
				var list = document.getElementById("WidgetImageList");
				var url = list.options[list.selectedIndex].value;
				
				if (url != null && url != "")
				{
					var image = document.getElementById("WidgetImage");
					image.style.display = "inline";
					image.src = url;
					
					document.getElementById("WidgetImageUrl").value = url;
				}
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
				
				var limit = <?php echo GetQuestionCountLimit() ?>;
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
				
				var limit = <?php echo GetQuestionLengthLimit() ?>;
				if (edit.value.length > limit)
				{
					var text = "<?php $format = __("The question text must not exceed %d characters", "FWTD");
						echo sprintf ($format, GetQuestionLengthLimit()); ?>";
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
			
			function GetCurrentWizardPage()
			{
				var divs = document.getElementsByClassName("WidgetWizardPage");
				for (var index = 0; index < divs.length; index++)
					if (divs[index].style.visibility == "visible")
						return index;
						
				return -1;
			}
			
			function ValidateTopics()
			{
				var tag = document.getElementById("TagText");
				var category = document.getElementById("CategoryText");
				if (tag.value.length > 250 || category.value.length > 250)
				{
					window.alert('<?php _e("The tags/categories text is limited to 250 characters.", "FWTD")?>');
					return false;
				}
				
				var tags = tag.value.split(",");
				if (tags.length > 10)
				{
					window.alert('<?php _e("The number of tags is limited to 10", "FWTD")?>');
					return false;
				}
				
				var categories = category.value.split(",");
				if (categories.length > 10)
				{
					window.alert('<?php _e("The number of categories is limited to 10", "FWTD")?>');
					return false;
				}
				
				return true;
			}
			
			function InitImageDiv()
			{
				var url = document.getElementById("WidgetImageUrl").value;
				if (url != null && url != "")
				{
					var image = document.getElementById("WidgetImage");
					image.style.display = "inline";
					image.src = url;
				}
				
				var list = document.getElementById("WidgetImageList");
				if (list != null)
				{
					list.selectedIndex = -1;
					for (var index = 0; index < list.options.length; index++)
						if (list.options[index].value == url)
						{
							list.selectedIndex = index;
							break;
						}
				}
			}
			
			function OnBack()
			{
				var old_page = GetCurrentWizardPage();
				if (old_page < 1)
					return;
					
				var box = document.getElementById("PublishWidgetCheckBox");
				var new_page = 0;
				if (box.checked == true)
					new_page = old_page - 1;
					
				var del_button = document.getElementById("DeleteButton");
				var next_button = document.getElementById("NextButton");
				var back_button = document.getElementById("BackButton");
				var ok_button = document.getElementById("OkButton");
				
				if (new_page == 0)
				{
					if (del_button != null)
						del_button.style.visibility = "visible";
					back_button.style.visibility = "hidden";
				}
				next_button.style.visibility = "visible";
				ok_button.style.visibility = "hidden";
				
				var divs = document.getElementsByClassName("WidgetWizardPage");
				divs[new_page].style.visibility = "visible";
				divs[old_page].style.visibility = "hidden";
			}
			
			function CheckTitle()
			{
				var title = document.getElementById("TitleText");
				if (title.value.length > 100)
				{
					alert('<?php _e("Page Title cannot exceed 100 characters", "FWTD") ?>');
					return false;
				}
				return true;
			}
			
			function CheckSummary()
			{
				var summary = document.getElementById("SummaryText");
				if (summary.value.length > 250)
				{
					alert('<?php _e("Page Summary cannot exceed 250 characters", "FWTD") ?>');
					return false;
				}
				return true;
			}
			
			function OnNext()
			{
				var page = GetCurrentWizardPage();
				if (page < 0)
					return;
					
				var divs = document.getElementsByClassName("WidgetWizardPage");
				var box = document.getElementById("PublishWidgetCheckBox");
				var del_button = document.getElementById("DeleteButton");
				var back_button = document.getElementById("BackButton");
				var next_button = document.getElementById("NextButton");
				var ok_button = document.getElementById("OkButton");
				switch(page)
				{
					case 0: // Title Div
						if (CheckTitle() == false)
							return;
					
						if (del_button != null)
							del_button.style.visibility = "hidden";
						back_button.style.visibility = "visible";
					
						FillQuestionList();
				
						if (box.checked == true)
						{
							divs[1].style.visibility = "visible";
							OnSelectCensorship();
							InitImageDiv();
						}
						else
						{
							next_button.style.visibility = "hidden";
							ok_button.style.visibility = "visible";
							divs[3].style.visibility = "visible";
						}
						divs[0].style.visibility = "hidden";
						break;
						
					case 1:	// Summary Div
						if (CheckSummary() == false)
							return;
						
						if (ValidateTopics() == false)
							return;
							
						divs[2].style.visibility = "visible";
						divs[1].style.visibility = "hidden";
						break;
					
					case 2: // Image Div
						next_button.style.visibility = "hidden";
						ok_button.style.visibility = "visible";
						divs[3].style.visibility = "visible";
						divs[2].style.visibility = "hidden";
						break;
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
				
					var control = document.getElementById("WidgetImageList");
					if (control != null)
					{
						for (var index = 0; index < control.options.length; index++)
							if (control.options[index].value == url)
							{
								control.selectedIndex = index;
								return;
							}
							
						control.innerHTML = "<option>" + url + "</option>" + control.innerHTML;
						control.selectedIndex = 0;
					}
					document.getElementById("WidgetImageUrl").value = url;
				}
			}
			
			function OnLoadWidgetImage()
			{
				var box = document.getElementById("WidgetImageBox");
				var img = document.getElementById("WidgetImage");
				img.style.display = "inline";
				
				if (img.clientWidth < 100 || img.clientHeight < 100)
				{
					window.alert ('<?php _e("The image width or height should be at least 100 pixels", "FWTD") ?>');
					document.getElementById("WidgetImageUrl").value = "";
					img.style.display = "none";
					img.src = "";
					return;
				}
				
				var ratio = img.clientWidth / img.clientHeight;
				if (ratio > 2.5 || ratio < 0.4)
				{
					window.alert ('<?php _e("The image ratio is invalid. The proportion between image width and height cannot be greater than 250%", "FWTD") ?>');
					document.getElementById("WidgetImageUrl").value = "";
					img.style.display = "none";
					img.src = "";
					return;
				}
				
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
			
			function OnDeleteMouseOver($state)
			{
				var version = '<?php echo $wp_version ?>';
				if (version != "3.5")
				{
					var button = document.getElementById("DeleteButton");
					if ($state == true)
					{
						button.style.backgroundColor = '#ff0000';
						button.style.color = '#ffffff';
					}
					else
					{
						button.style.backgroundColor = '#ffffff';
						button.style.color = '#000000';
					}
				}
			}
			
			function OnDelete()
			{
				if (window.confirm("<?php GetRemoveWidgetPrompt()?>") == true)
					window.location.href = "widget_commit.php?feedweb_cmd=DEL&wp_post_id=" + <?php echo GetPostId()?>;
			}
			
			function BuildQuestionData()
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
			}
			
			function BuildVisibilityData()
			{
				var data = "";
				var box = document.getElementById("PublishWidgetCheckBox");
				if (box.checked == true)
				{
					data = "+";
					
					box = document.getElementById("CensorshipBox");
					data += ";" + box.options[box.selectedIndex].text;
					
					box = document.getElementById("AdContentCheckBox");
					if (box.checked == true)
						data += ";*"
				}
				else
					data = "-";

				document.getElementById("WidgetVisibilityData").value = data;
			}
			
			function OnSubmitForm()
			{
				BuildQuestionData();
				BuildVisibilityData();
								
				var action = "<?php GetFormAction()?>";
				document.forms[0].action = action;
				document.forms[0].method = "post";
				return true;
			}
			
			function OnLoad()
			{
				OnClickPublishWidgetBox();
			
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
		<link href='<?php echo plugin_dir_url(__FILE__)?>Feedweb.css?v=2.0.7' rel='stylesheet' type='text/css' />
		
	</head>
	<body style="margin: 0px; overflow: hidden;" onload="OnLoad()">
		<div id="WidgetDialog" >
		 	<form id="WidgetDialogForm" onsubmit="return OnSubmitForm();">
				<input type="hidden" name="WidgetQuestionsData" id="WidgetQuestionsData"/>
				<input type='hidden' name="WidgetVisibilityData" id="WidgetVisibilityData"/>
				<div id="TermsOfServiceDiv">
					<span id="TermsOfServiceTitle"><?php _e("Feedweb Plugin Terms of Service", "FWTD");?></span>
					<textarea id="TermsOfServiceText" value="Here are the terms of service, people." readonly><?php LoadTermsOfService(); ?></textarea>
					<input type="button" id="TermsOfServiceCloseButton" value='<?php _e("Close")?>' onclick='OnCloseTermsOfService()'/>
				</div>
				<div id="WidgetTitleDiv" class="WidgetWizardPage" style="visibility: visible;">
			 		<table id="WidgetTitlePage" class="wp-list-table widefat fixed posts" cellspacing="0">
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
							<tr class="WizardContentRow">
								<td/>
								<td colspan="4"> 
									<?php GetPostTitleControl() ?>
								</td>
								<td/>
							</tr>
							
							<tr>
								<td/>
								<td colspan="4">
									<span id='UrlLabel'><b><?php _e("URL:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr >
							<tr class="WizardContentRow">
								<td/>
								<td colspan="4"> 
									<?php GetUrlControl() ?>
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
							<tr class="WizardContentRow">
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
								<td colspan="4">
									<div id="PublishWidgetDiv">
										<?php GetPublishWidgetCheckBox() ?>
									</div>
								</td>
								<td/>
							</tr>
						</tbody>
					</table>
				</div>
				
				<div id="WidgetBriefDiv" class="WidgetWizardPage" style="visibility: hidden; position: absolute; top: 0; left: 0;">
					<table id="WidgetBriefTable" class="wp-list-table widefat fixed posts" cellspacing="0">
						<tbody>
							<tr style="height: 5px;">
								<td style='width: 10px;'/>
								<td style='width: 300px;'/>
								<td style='width: 150px;'/>
								<td style='width: 150px;'/>
								<td style='width: 10px;'/>
							</tr>
							
							<tr>
								<td/>
								<td colspan="3">
									<span id='SummaryLabel'><b><?php _e("Summary:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr id="SummaryTextRow">
								<td/>
								<td colspan="3"> 
									<?php GetPostSummaryControl() ?>
								</td>
								<td/>
							</tr>

							<tr>
								<td/>
								<td>
									<span id='CategoryLabel'><b><?php _e("Categories:")?></b></span>
								</td>
								<td colspan="2">
									<span id='TagLabel' style='padding-left: 20px;'><b><?php _e("Tags:")?></b></span>
								</td>
								<td/>
							</tr>
							<tr id="TopicRow">
								<td/>
								<td> 
									<?php GetCategoryControl() ?>
								</td>
								<td colspan="2"> 
									<?php GetTagControl() ?>
								</td>
								<td/>
							</tr>
							
							<tr>
								<td/>
								<td colspan="3">
									<span id="CensorshipLabel"><b><?php _e("Censorship:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr id="CensorshipRow">
								<td/>
								<td colspan="3">
									<?php GetCensorshipBox() ?>
								</td>
								<td/>
							</tr>
							
							<tr id="AdContentRow">
								<td/>
								<td colspan="3">
									<?php GetAdContentCheckBox() ?>
								</td>
								<td/>
							</tr>
						</tbody>
					</table>
				</div>
				
				<div id="WidgetImageDiv" class="WidgetWizardPage" style="visibility: hidden; position: absolute; top: 0; left: 0;">
					<table id="WidgetImageTable" class="wp-list-table widefat fixed posts" cellspacing="0">
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
									<div id="WidgetImageBox" style="text-align: center; height: 240px; overflow: hidden; border: 1px solid #C0C0C0; margin-right: 24px;">
										<img id="WidgetImage" onload="OnLoadWidgetImage()" style="position: relative; display: none;"/>
									</div>
								</td>
								<td/>
							</tr>
						</tbody>
					</table>
				</div>
				
				<div id="WidgetQuestionDiv" class="WidgetWizardPage" style="visibility: hidden; position: absolute; top: 0; left: 0;">
			 		<table id="WidgetQuestionTable" class="wp-list-table widefat fixed posts" cellspacing="0">
						<tbody>
							<tr style="height: 5px;">
								<td style='width: 10px;'/>
								<td style='width: 350px;'/>
								<td style='width: 120px;'/>
								<td style='width: 10px;'/>
							</tr>
							<tr>
								<td/>
								<td colspan="2">
									<span id='OldQuestionsLabel'><b><?php _e("Existing Questions:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 36px;">
								<td/>
								<td> 
									<select id='OldQuestionsList' name='OldQuestionsList' style='width:460px;'>
									</select>
								</td>
								<td>
									<input type="button" value='<?php _e("Select")?>' style='width: 150px; margin-left: 8px;' onclick='OnSelect()'/>
								</td>
								<td/>
							</tr>
							<tr height='5px'>
								<td colspan='4'/>
							</tr>
							<tr>
								<td/>
								<td colspan="2">
									<span id='NewQuestionLabel'><b><?php _e("New Question", "FWTD")?></b> (<i><?php YesNoQuestionPrompt()?></i>)<b>:</b></span>
								</td>
								<td/>
							</tr>
							<tr>
								<td/>
								<td>
									<input type='text' id='NewQuestionText' name='NewQuestionText' style='width: 460px;'/>
								</td>
								<td>
									<input type='button' value='<?php _e("Add")?>' onclick="OnAddNew()" style='width: 150px; margin-left: 8px;'/>
								</td>
								<td/>
							</tr>
							<tr height='5px'>
								<td colspan='4'/>
							</tr>
							<tr>
								<td/>
								<td colspan="2">
									<span id='QuestionsLabel'><b><?php _e("Selected Questions:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr>
								<td rowspan='3'/>
								<td rowspan='3' style='height: 116px;'>
									<?php BuildQuestionsListControl() ?>
								</td>
								<td valign='top'>
									<input type='button' value='<?php _e("Move Up", "FWTD")?>' onclick='OnMoveUp()' style='width: 150px; margin-left: 8px;'/>
								</td>
								<td rowspan='3'/>
							</tr>
							<tr>
								<td>
									<input type='button' value='<?php _e("Move Down", "FWTD")?>' onclick='OnMoveDown()' style='width: 150px; margin-left: 8px;'/>
								</td>
							</tr>
							<tr>
								<td valign='bottom'>
									<input type='button' value='<?php _e("Remove")?>' onclick='OnRemove()' style='width: 150px; margin-left: 8px;'/>
								</td>
							</tr>
							
						</tbody>
					</table>
				</div>
				
				<div id="WizardNavigatorDiv" style="position: absolute; top: 320px; width: 100%; height: 50px;">
					<!-- Remove -->
					<?php 
						if($_GET["mode"] == "edit") 
							echo "<input type='button' value='".__("Remove Widget", "FWTD")."' style='width: 150px; position: absolute; left: 35px; top: 10px;'". 
								" id='DeleteButton' onmouseover='OnDeleteMouseOver(true)' onmouseout='OnDeleteMouseOver(false)' onclick='OnDelete()'/>";
					?>								
					
					<!-- Back -->
					<input type='button' id='BackButton' value='<?php _e("< Back", "FWTD")?>' style='width: 150px; position: absolute; left: 35px; top: 10px; visibility: hidden;' onclick='OnBack()'/> 
					
					<!-- Next -->
					<input type='button' id='NextButton' value='<?php _e("Next >", "FWTD")?>' style='width: 150px; position: absolute; left: 350px; top: 10px;' onclick='OnNext()'/>
					
					<!-- Done -->
					<input type='submit' id='OkButton' value='<?php _e("Done", "FWTD")?>' style='width: 150px; position: absolute; left: 350px; top: 10px; visibility: hidden;'/>
					
					<!-- Cancel -->
					<input type='button' id='CancelButton' value='<?php _e("Cancel")?>' style='width: 150px; position: absolute; left: 510px; top: 10px;' onclick='OnCancel()'/>
				</div>
			</form>
		</div>
	</body>
</html>