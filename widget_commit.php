<?php

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( 'feedweb_util.php');

if (!current_user_can('manage_options'))
	wp_die(__("You are not allowed to be here"));

$alert = "";
$cmd = $_GET["feedweb_cmd"];
$id = intval($_GET["wp_post_id"]);

if ($cmd == "DEL")
	RemoveWidget($id);
else
	InsertWidget($id);

function InsertWidget($id)
{
	$pac = CreatePageWidget($id);
	if ($pac == null)
		return;
	
	global $alert;
	if (InsertPac($pac, $id) == false)
		$alert = __("Wordpress cannot insert your widget", "FWTD");
}

function RemoveWidget($id)
{
	global $alert;
	$pac = GetPac($id);
	if ($pac != null)
	{
		RemovePac($id);
		$author_id = GetPostAuthorId($id);
		$gid = GetUserCode($author_id, true);
		if ($gid != null)
		{
			$query = GetFeedwebUrl()."FBanner.aspx?action=rpw&pac=$pac&gid=$gid";
			$response = wp_remote_get ($query, array('timeout' => 30));
			if (is_wp_error ($response))
			{
				$alert = __("Cannot connect Feedweb server", "FWTD");
				return;
			}
		
			$dom = new DOMDocument;
			if ($dom->loadXML($response['body']) == true)
			{
				$el = $dom->documentElement;
				if ($el->tagName == "BANNER")
					$alert = $el->getAttribute("error");
				return;
			}
		}
	}
	$alert = __("Wordpress cannot remove your widget", "FWTD");
}


function CreatePageWidget($id)
{
	global $alert;
	
	$data = GetFeedwebOptions();
	$lang = $data["language"];
	
	$plugin_name = dirname(__FILE__)."/feedweb.php";
	$plugin_data = get_plugin_data($plugin_name);
	$version = $plugin_data['Version'];
		
	try
	{
		$query = GetFeedwebUrl()."FBanner.aspx?action=cpw&client=WP:$version&lang=$lang";
		
		if ($_POST["WidgetQuestionsData"] != "")
		{
			$questions = explode ( "|", $_POST["WidgetQuestionsData"]);
			$count = count($questions);
		
			for ($index = 0; $index < $count; $index++)
			{
				$question = $questions[$index];
				if ($question == "")
					break;
				
				if (substr($question, 0, 1) == "@")
				{
					$question = substr($question, 1);
					$question = PrepareParam($question);
				}
				else
					$question = "{".$question."}";
				$query = $query."&q".$index."=".$question;
			}
			$query = $query."&qn=".$count;
		}
		
		if ($data["delay"] != "0")
			$query = $query."&delay=".$data["delay"];
		
		$url = PrepareParam($_POST["UrlText"]);
		if ($url == "")
		{
			$alert = __("Error in the Post data", "FWTD");
			return null;
		}
		$query = $query."&page=".$url;
			
		$title = PrepareParam($_POST["TitleText"]);
		if ($title == "")
		{
			$alert = __("Error in the Post data", "FWTD");
			return null;
		}
		$query = $query."&title=".$title;
		
		$author = PrepareParam($_POST["AuthorText"]);
		if ($author == "")
		{
			$alert = __("Error in the Post data", "FWTD");
			return null;
		}
		$query = $query."&author=".$author;
		
		$author_id = GetPostAuthorId($id);
		$author_code = GetUserCode($author_id, false);
		if ($author_code == null)
		{
			$alert = __("Error in the User data", "FWTD");
			return null;
		}
		$query = $query."&guid=".$author_code;
		
		$sub_title = PrepareParam($_POST["SubTitleText"]);
		if ($sub_title != "")
			$query = $query."&brief=".$sub_title;
		
		$response = wp_remote_get ($query, array('timeout' => 60));
		if (is_wp_error ($response))
		{
			$alert = __("Cannot connect Feedweb server", "FWTD");
			return null;
		}
		
		$dom = new DOMDocument;
		if ($dom->loadXML($response['body']) == true)
		{
			$el = $dom->documentElement;
			if ($el->tagName == "BANNER")
			{
				$pac = $el->getAttribute("pac");
				if ($pac != "")
					return $pac;
				
				$alert = $el->getAttribute("error");
				if ($alert == "")
					$alert = __("Unknown server error", "FWTD");
				return null;
			}
		}
		$alert = __("Feedweb service cannot create a widget", "FWTD");
	}
	catch (Exception $e)
	{
		$alert = $e->getMessage();
	}
	return null;
}


function GetAlertText()
{
	global $alert;
	echo $alert;
}
?>

<html>
	<head>
		<script language="javascript" type="text/javascript">
			function OnInit()
			{
				var text = "<?php GetAlertText() ?>";
				if (text != "")
					window.alert(text);
				
				window.parent.tb_remove();
				window.parent.location.href = window.parent.location.href;
			}
		</script>
	</head>
	<body onload="OnInit()">
	</body>
</html> 
