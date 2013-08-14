<?php
function GetDefaultLanguage()
{
	$lang = get_locale();
	$pos = strpos($lang, "_");
	if ($pos > 0)
		$lang = substr($lang, 0, $pos);
	return $lang;
}

function GetMonitorPath()
{
	return GetFeedwebUrl()."MR/Monitor.aspx?lang=".GetDefaultLanguage()."&bac=".GetBac(true);
}
?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"><br /></div>
	<h2><?php _e("Feedweb Monitor", "FWTD");?>*</h2><br/>
	<span style='color: #A04000'><b><i>*A limited beta preview. Please report any errors / problems to </i><a href="mailto://contact@feedweb.net">contact@feedweb.net</a>. Time is according to GMT.</b></span><br/><br/>
	<iframe id='FeedwebMonitor' style='width: 1100px; height: 600px;' scrolling='no' src='<?php echo GetMonitorPath();?>'></iframe>
</div>
