<?php
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( ABSPATH.'wp-admin/includes/template.php');


$edit_page_data = null;

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
		
	if ($_GET["mode"] == "edit")
	{
		$data = GetEditPageData();
		if ($data != null)
		{
			$name = $data["author"];
			$id = $data["author_id"];
			if ($name != null && $name != "" && $id != null && $id != "")
				$names[$name] = $id;
		}
	}
	
	$default_id = null;
	$default_name = null;
	echo "<select style='width:100%;' name='AuthorBox' onchange='OnChangeAuthor()'>";
	foreach ($names as $key => $value)
	{
		if ($default_name == null)
		{
			$default_id = $value;
			$default_name = $key;
		}
		echo "<option value='$value'>$key</option>";
	}
	echo "</select><input type='hidden' name='AuthorText' value='$default_name'/><input type='hidden' name='AuthorId' value='$default_id'/>";
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

function BuildQuestionsCombo()
{
	$questions = GetQuestionList(null);
	if ($questions == null)
		return;
	
	$combo = "<select id='OldQuestionsList' name='OldQuestionsList' style='width:100%;'> ";
	foreach ($questions as $key => $value)
		$combo .= "<option value='$key'>$value</option>";
	return $combo."</select>";
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
		<script type="text/javascript">
			function OnChangeTitle()
			{
				var input = document.getElementsByName("TitleText")[0];
				var box = document.getElementsByName("TitleBox")[0];
				input.value = box.options[box.selectedIndex].value; 
			}
			
			function OnChangeUrl()
			{
				var input = document.getElementsByName("UrlText")[0];
				var box = document.getElementsByName("UrlBox")[0];
				input.value = box.options[box.selectedIndex].value; 
				window.alert(input.value);
			}
			
			function OnChangeAuthor()
			{
				var name_input = document.getElementsByName("AuthorText")[0];
				var id_input = document.getElementsByName("AuthorId")[0];
				var box = document.getElementsByName("AuthorBox")[0];
				
				id_input.value = box.options[box.selectedIndex].value; 
				name_input.value = box.options[box.selectedIndex].label; 
			}
		
			function AddQuestion(text, value)
			{
				var list = document.getElementsByName("QuestionsList")[0];
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
				var option = document.createElement("Option");
				option.text = text;
				option.value = value;
	            list.options.add(option);
			}
		
			function OnSelect() 
			{ 
				var combo = document.getElementsByName("OldQuestionsList")[0];
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

				var edit = document.getElementsByName("NewQuestionText")[0];
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
				var list = document.getElementsByName("QuestionsList")[0];
				if (list.selectedIndex <= 0)
					return;

				MoveCurrentItem(list, list.selectedIndex - 1);
			}
			
			
			function OnMoveDown()
			{
				var list = document.getElementsByName("QuestionsList")[0];
				if (list.selectedIndex < 0 || list.selectedIndex >= list.options.length - 1)
					return;

				MoveCurrentItem(list, list.selectedIndex + 1);
			}

			
			function OnRemove()
			{
				var list = document.getElementsByName("QuestionsList")[0];
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
				var div = document.getElementsByName("FirstPhaseDiv")[0];
				div.style.visibility = "visible";

				div = document.getElementsByName("SecondPhaseDiv")[0];
				div.style.visibility = "hidden";
			}
			
			function OnNext()
			{
				var div = document.getElementsByName("FirstPhaseDiv")[0];
				div.style.visibility = "hidden";

				div = document.getElementsByName("SecondPhaseDiv")[0];
				div.style.visibility = "visible";
			}
		
			function OnCancel() 
			{ 
				window.parent.tb_remove(); 
			} 
					
			function OnDeleteMouseOver()
			{
				var button = document.getElementsByName("DeleteButton")[0];
				button.style.backgroundColor = '#ff0000';
				button.style.color = '#ffffff';
			}
			
			function OnDeleteMouseOut() 
			{
				var button = document.getElementsByName("DeleteButton")[0];
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
				var input = document.getElementsByName("WidgetQuestionsData")[0];
				input.value = "";
					
				var list = document.getElementsByName("QuestionsList")[0];
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
		</script>

		<link rel='stylesheet' href='<?php echo get_bloginfo('url') ?>/wp-admin/load-styles.php?c=0&amp;dir=ltr&amp;load=admin-bar,wp-admin' type='text/css' media='all' />
		<link rel='stylesheet' id='thickbox-css'  href='<?php echo get_bloginfo('url') ?>/wp-includes/js/thickbox/thickbox.css' type='text/css' media='all' />
		<link rel='stylesheet' id='colors-css'  href='<?php echo get_bloginfo('url') ?>/wp-admin/css/colors-fresh.css' type='text/css' media='all' />
		
	</head>
	<body style="margin: 0px;">
		<div id="WidgetDialog" >
		 	<form id="WidgetDialogForm" onsubmit="return OnSubmitForm();">
				<input type="hidden" name="WidgetQuestionsData" id="WidgetQuestionsData"/>
				<div id="FirstPhaseDiv" name="FirstPhaseDiv" style="visibility: visible;">
			 		<table id="FirstPhaseTable" name="FirstPhaseTable" class="wp-list-table widefat fixed posts" cellspacing="0">
						<tbody>
							<tr height='5px'>
								<td style='width: 10px;'/>
								<td style='width: 150px;'/>
								<td style='width: 10px;'/>
								<td style='width: 150px;'/>
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
							
							<tr height='5px'>
								<td colspan="6"/>
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
							
							<tr height='5px'>
								<td colspan="6"/>
							</tr>
							<tr>
								<td/>
								<td colspan="4">
									<span id='AuthorLabel'><b><?php _e("Author:", "FWTD")?></b></span>
								</td>
								<td/>
							</tr>
							<tr style="height: 36px;">
								<td/>
								<td colspan="4"> 
									<?php GetAuthorControl() ?>
								</td>
								<td/>
							</tr>
							
							<tr height='5px'>
								<td colspan="6"/>
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
													
							<tr height='24px'>
								<td colspan='6'/>
							</tr>
							<tr>
								<td/>
								<td>
									<?php 
										if($_GET["mode"] == "edit") 
											echo "<input type='button' value='".__("Remove Widget")."' style='width: 150px;' name='DeleteButton'".
												"onmouseover='OnDeleteMouseOver()' onmouseout='OnDeleteMouseOut()' onclick='OnDelete()'/>";
									?>								
								</td>
								<td/>
								<td style='text-align: right;'>
									<input type='button' value='<?php _e("Next >")?>' style='width: 150px;' onclick='OnNext()'/>
								</td>
								<td>
									 <input type='button' value='<?php _e("Cancel")?>' style='width: 140px;' onclick='OnCancel()'/>
								</td>
								<td/>
							</tr>
						</tbody>
					</table>
				</div>
				<div id="SecondPhaseDiv" name="SecondPhaseDiv" style="visibility: hidden; position: absolute; top: 0; left: 0;">
			 		<table id="SecondPhaseTable" name="SecondPhaseTable" class="wp-list-table widefat fixed posts" cellspacing="0">
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
									<?php echo BuildQuestionsCombo() ?> 
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
								<td rowspan='3' colspan='2'>
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
							<tr height='20px'>
								<td colspan='5'/>
							</tr>
							<tr>
								<td/>
								<td>
									<input type='button' value='<?php _e("Cancel")?>' style='width: 150px;' onclick='OnCancel()'/>
								</td>
								<td style="text-align: right;">
									<input type='button' value='<?php _e("< Back")?>' style='width: 150px;' onclick='OnBack()'/> 
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