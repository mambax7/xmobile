<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
// Render.class.php から呼び出し

function replaceURI($uristr)
{
	global $xoopsDB , $xoopsUser ;

	$request_uri = $uristr[1];
	$replacement_uri= '';
	$xoops_url = preg_replace('/\//','\\\/',XOOPS_URL);

	if (preg_match('/[\S|\s]+\/modules\/'.basename(dirname(dirname(__FILE__))).'\/[\S|\s]+/', $request_uri))
	{
//		return '<a href="'.$request_uri.'">';
		return '<a href="'.$request_uri.'"';
	}


	$sess = '';
	if (preg_match('/.*(sess=[\w]{32}).*/', $_SERVER['HTTP_REFERER'],$matches) )
	{
		$sess = '&'.htmlspecialchars($matches[1], ENT_QUOTES);
	}

	$request_pattern[0] = '/'.$xoops_url.'\/register\.php/';
	$request_pattern[1] = '/'.$xoops_url.'\/user\.php./';
	$request_pattern[2] = '/'.$xoops_url.'\/userinfo\.php\?uid=(\d+)/';
	$request_pattern[3] = '/'.$xoops_url.'\/lostpass\.php/';
	$request_pattern[4] = '/'.$xoops_url.'\/viewpmsg\.php/';

	$replacement_pattern[0] = XOOPS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/?act=register';
	$replacement_pattern[1] = XOOPS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/?act=login';
	$replacement_pattern[2] = XOOPS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/?act=userinfo&uid=$1';
	$replacement_pattern[3] = XOOPS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/?act=lostpass';
	$replacement_pattern[4] = XOOPS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/?act=pmessage';

	$replacement_uri = preg_replace($request_pattern, $replacement_pattern, $request_uri);


	if ( preg_match('/.*\/modules\/(.*)\/$/', $request_uri, $matches) )
	{
		$module_name = $matches[1];
		$usemodules = xmobile_option('modules_can_use');
		if (in_array($module_name, $usemodules))
		{
			$replacement_uri = XOOPS_URL . '/modules/'.basename(dirname(dirname(__FILE__))).'/?act=plugin&plg='. htmlspecialchars($module_name,ENT_QUOTES);
		}
	}
	elseif ( preg_match('/.*\/modules\/(.*)\/.*\?(.*)/', $request_uri, $matches) )
	{
		$module_name = $matches[1];
		$usemodules = xmobile_option('modules_can_use');
		if (in_array($module_name, $usemodules))
		{
			$replacement_uri = XOOPS_URL . '/modules/'.basename(dirname(dirname(__FILE__))).'/?act=plugin&plg='. htmlspecialchars($module_name,ENT_QUOTES);
			if ( file_exists(XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/plugins/'.$module_name.'.php') )
			{
				require_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/Plugin.class.php' ;
				require_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/plugins/'.$module_name.'.php' ;
				$pluginClassName = 'Xmobile'. ucfirst($module_name) .'PluginHandler';
				if ( class_exists($pluginClassName) )
				{
					$hl =& new $pluginClassName($xoopsDB);
					$item_id_fld	= $hl->item_id_fld ;
					$item_cid_fld = $hl->item_cid_fld ;
					if ( !isset($item_cid_fld) ) $item_cid_fld = $hl->category_id_fld ;

					if ( isset($item_id_fld) )
					{
						if (preg_match('/[\S|\s]+\/modules\/'.$module_name.'\/[\S|\s]+('.$item_id_fld.'=\d+)/', $request_uri, $matches))
						{
							$ext = $matches[1];
							$detailUrl = '&view=detail&'. $ext;
						}
						$replacement_uri .= $detailUrl ;
					}
					//category ID
					if ( isset($item_cid_fld) )
					{
						if (preg_match('/[\S|\s]+\/modules\/'.$module_name.'\/[\S|\s]+('.$item_cid_fld.'=\d+)/', $request_uri, $matches))
						{
							$ext = $matches[1];
							if ( empty($detailUrl) )
							{
								$replacement_uri .= '&view=list&' . $ext;
							}
							else
							{
								$replacement_uri .= '&'. $ext;
							}
						}
					}
				}
			}
		}
	}

//	return '<a href="'.$replacement_uri.$sess.'">';
	return '<a href="'.$replacement_uri.$sess.'"';
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function xmobile_option($conf_name)
{
	$module_handler = & xoops_gethandler('module');
	$module = $module_handler->getByDirname(basename(dirname(dirname(__FILE__))));
	$mid = $module->getVar('mid');
	$xmobileConfig = & xoops_gethandler('config');
	$records = & $xmobileConfig->getConfigList($mid);
	$value = $records[$conf_name];
	return ($value);
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>