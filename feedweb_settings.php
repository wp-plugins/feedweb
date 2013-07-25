<?php

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( 'feedweb_util.php');

if (!current_user_can('manage_options'))
	wp_die(__("You are not allowed to be here"));
else
	UpdateSettings();

$error_message = "";

function UpdateSettings()
{
	$data = array();
	$data["delay"] = $_POST["DelayResults"];
	$data["language"] = $_POST["FeedwebLanguage"];
	$data["mp_widgets"] = $_POST["FeedwebMPWidgets"];
	$data["widget_type"] = $_POST["RatingWidgetType"];
	$data["widget_width"] = $_POST["WidgetWidthEdit"];
	$data["add_paragraphs"] = $_POST["AutoAddParagraphs"];
	$data["widget_prompt"] = $_POST["InsertWidgetPrompt"];
	$data["widget_cs"] = $_POST["RatingWidgetColorScheme"];
	$data["widget_place"] = $_POST["RatingWidgetPlacement"];
	$data["widget_ext_bg"] = $_POST["ExternalBackgroundBox"];
	$data["front_widget_items"] = $_POST["FrontWidgetItemCount"];
	$data["front_widget_height"] = $_POST["FrontWidgetHeightEdit"];
	$data["copyright_notice_ex"] = $_POST["FeedwebCopyrightNotice"];
	$data["front_widget_hide_scroll"] = $_POST["FrontWidgetHideScroll"];
	$data["front_widget_color_scheme"] = $_POST["FrontWidgetColorScheme"];
	
	if (SetFeedwebOptions($data))
		$error_message = "";
	else
		$error_message = __("Failed to update settings", "FWTD");
}

function GetErrorMessage()
{
	global $error_message;
	echo $error_message;
}
?>

<html>
<head>
	<script language="javascript" type="text/javascript">
		function OnInit()
		{
			var error = "<?php echo GetErrorMessage();?>";
			if (error != "")
				window.alert (error);

			var href = "<?php echo $_POST["_wp_http_referer"]?>";
			window.location.href = href;
		}
	</script>
</head>
<body onload="OnInit()">
</body>
</html> 