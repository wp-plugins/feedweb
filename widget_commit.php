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
	RemovePac($id);
else
	InsertWidget($id);

function InsertWidget($id)
{
	$pac = CreatePageWidget($id);
	if ($pac == null)
		return;
	
	global $alert;
	if (InsertPac($pac, $id) == false)
		$alert = "Wordpress cannot insert your widget";
}

function CreatePageWidget($id)
{
	global $alert;
	global $feedweb_url;
	$data = GetFeedwebOptions();
	
	try
	{
		$query = $feedweb_url."FBanner.aspx?action=cpw&lang=".$data["language"];
		
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
		
		$code = GetPostCode($id);
		if ($code == null)
		{
			$alert = "Error in the Post data";
			return null;
		}
		$query = $query."&code=".$code;
				
		$response = wp_remote_get ($query, array('timeout' => 60));
		if (is_wp_error ($response))
		{
			$alert = "Cannot connect Feedweb server";
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
					$alert = "Unknown server error";
				return null;
			}
		}
		$alert = "Feedweb service cannot create a widget";
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
