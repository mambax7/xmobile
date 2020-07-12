<?php
if( !defined('XOOPS_ROOT_PATH') ) exit();

function b_xmobile_redirect_show()
{
	$block = array();

	$mobile_url = XOOPS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/';
//	$mobile_url = '/modules/'.basename(dirname(dirname(__FILE__))).'/';
	$ua = $_SERVER['HTTP_USER_AGENT'];
	if (
	preg_match("/DoCoMo\//",$ua) ||
	preg_match("/UP\.Browser/",$ua) ||
	preg_match("/\AVodafone/",$ua) ||
	preg_match("/\ASoftBank/",$ua) ||
	preg_match("/\AMOT-/",$ua) ||
	preg_match("/DDIPOCKET;/",$ua) ||
	preg_match("/WILLCOM;/",$ua) ||
	preg_match("/L-mode\/\//",$ua))
	{
		header('Location: '.$mobile_url);
		exit();
	}
	return $block;
}

?>