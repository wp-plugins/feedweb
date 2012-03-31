<?php
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( ABSPATH.'wp-admin/includes/template.php');

function GetPostId()
{
	return $_GET["wp_post_id"];
}

function GetQuestionList($pac)
{
	global $feedweb_url;

	$data = GetFeedwebOptions();
	$site = PrepareParam(site_url());
	$query = $feedweb_url."FBanner.aspx?action=gql&lang=".$data["language"]."&site=".$site;
	if ($pac != null) 
		$query = $query."&pac=".$pac;

	$response = wp_remote_get ($query, array('timeout' => 60));
	if (is_wp_error ($response))
		return null;
	
	$dom = new DOMDocument;
	if ($dom->loadXML($response['body']) == true)
		if ($dom->documentElement->tagName == "BANNER")
		{
			$list = $dom->documentElement->getElementsByTagName("QUESTIONS");
			if ($list->length > 0)
			{
				$questions = array();
				$list = $list->item(0)->getElementsByTagName("Q");
				for ($item = 0; $item < $list->length; $item++)
				{
					$question = $list->item($item);
					
					$id = $question->getAttribute("id");
					$text = $question->getAttribute("text");
					$index = $question->getAttribute("index");
					
					$value = array($text, $index);
					$questions[$id] = $value; 
				}
				return $questions;
			}
		}
	return null;
}

function BuildQuestionsCombo()
{
	$questions = GetQuestionList(null);
	if ($questions == null)
		return;
	
	$combo = "<select id='OldQuestionsList' name='OldQuestionsList' style='width:100%;'> ";
	$keys = array_keys($questions);
	
	foreach ($keys as $key)
	{
		$item = $questions[$key];
		$combo = $combo."<option value='".$key."'>".$item[0]."</option>";
	}
	return $combo."</select>";
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
					var text = "<?php _e("Please pecify a question", "FWTD")?>";
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
			
		
			function OnCancel() 
			{ 
				window.parent.tb_remove(); 
			} 
		
			function OnLoad()
			{
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
				
				document.forms[0].action ="widget_commit.php?wp_post_id=" + <?php echo GetPostId()?>;
				document.forms[0].method = "post";
				return true;
			}
		</script>

		<link rel='stylesheet' href='<?php echo get_bloginfo('url') ?>/wp-admin/load-styles.php?c=0&amp;dir=ltr&amp;load=admin-bar,wp-admin' type='text/css' media='all' />
		<link rel='stylesheet' id='thickbox-css'  href='<?php echo get_bloginfo('url') ?>/wp-includes/js/thickbox/thickbox.css' type='text/css' media='all' />
		<link rel='stylesheet' id='colors-css'  href='<?php echo get_bloginfo('url') ?>/wp-admin/css/colors-fresh.css' type='text/css' media='all' />
		
	</head>
	<body onload="OnLoad()" style="margin: 0px;">
		<div id="WidgetDialog" >
			<!--  method="post" action="widget_commit.php"  -->
		 	<form id="WidgetDialogForm" onsubmit="return OnSubmitForm();">
				<input type="hidden" name="WidgetQuestionsData" id="WidgetQuestionsData"/>
		 		<table class="wp-list-table widefat fixed posts" cellspacing="0">
					<tbody>
						<tr height='5px'>
							<td style='width: 10px;'/>
							<td style='width: 350px;'/>
							<td style='width: 100px;'/>
							<td style='width: 10px;'/>
						</tr>
						<tr>
							<td/>
							<td colspan="2">
								<span id='OldQuestionsLabel'><b><?php _e("Existing Questions:", "FWTD")?></b></span>
							</td>
							<td/>
						</tr>
						<tr>
							<td/>
							<td> 
								<?php echo BuildQuestionsCombo() ?> 
							</td>
							<td>
								<input type="button" value='<?php _e("Select")?>' style='width: 100%;' onclick='OnSelect()'/>
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
								<input type='text' id='NewQuestionText' name='NewQuestionText' style='width:100%;'/>
							</td>
							<td>
								<input type='button' value='<?php _e("Add")?>' onclick="OnAddNew()" style='width: 100%;'/>
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
							<td rowspan='3'>
								<select size='4' id='QuestionsList' name='QuestionsList' style='width:100%;height:100px;'> </select>
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
							<td colspan='4'/>
						</tr>
						<tr>
							<td/>
							<td>
								<?php echo get_submit_button(__("Insert Widget", "FWTD"), "primary", "submit", false, "style='width: 95%;'") ?> 
							</td>
							<td>
								<input type='button' value='<?php _e("Cancel")?>' style='width: 100%;' onclick='OnCancel()'/>
							</td>
							<td/>
						</tr>
					</tbody>
				</table>
		  	</form>
		</div>
	</body>
</html>