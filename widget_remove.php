<?php
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once( ABSPATH.'wp-load.php');
require_once( ABSPATH.'wp-admin/includes/template.php');

function GetAction()
{
	echo "widget_commit.php?wp_post_id=".$_GET["wp_post_id"]."&feedweb_cmd=DEL";
}


function GetRemovePrompt()
{
	$id = intval($_GET["wp_post_id"]);
	$format = __("The rating widget in the post %s will be removed");
	$arg = "'<i>".get_the_title($id)."</i>'";
	printf($format, $arg);
}

?>

<html>
	<head>
		<script type="text/javascript">
			function OnCancel() 
			{ 
				window.parent.tb_remove(); 
			} 
		
			function OnLoad()
			{
		    }

			function OnSubmitForm()
			{
				document.forms[0].action = "<?php GetAction()?>";
				document.forms[0].method = "post";
				return true;
			}
		</script>

		<link rel='stylesheet' href='<?php echo get_bloginfo('url') ?>/wp-admin/load-styles.php?c=0&amp;dir=ltr&amp;load=wp-admin' type='text/css' media='all' />
		<link rel='stylesheet' id='thickbox-css'  href='<?php echo get_bloginfo('url') ?>/wp-includes/js/thickbox/thickbox.css' type='text/css' media='all' />
		<link rel='stylesheet' id='colors-css'  href='<?php echo get_bloginfo('url') ?>/wp-admin/css/colors-fresh.css' type='text/css' media='all' />
		
	</head>
	<body onload="OnLoad()" style="margin: 0px;">
		<div id="RevoveWidgetDialog" >
		 	<form id="RemoveWidgetDialogForm" onsubmit="return OnSubmitForm();">
		 		<table>
		 			<tbody>
		 				<tr style="height:5px;">
		 					<td style="width: 10px;"/>
 						 	<td style="width: 555px;"/>
 						 	<td style="width: 10px;"/>
		 				</tr>
		 				<tr style="height:50px;">
		 					<td/>
		 					<td align="center">
 								<span id='PromptLabel'><b><?php GetRemovePrompt()?></b></span>
		 					</td>
		 					<td/>
		 				</tr>
		 				<tr valign="bottom">
		 					<td/>
		 					<td align="center">
								<?php echo get_submit_button(__("Remove Widget"), "primary", "submit", false, "style='width: 100%;'") ?>
		 					</td>
		 					<td/>
		 				</tr>
		 			</tbody>
		 		</table>
		  	</form>
		</div>
	</body>
</html>