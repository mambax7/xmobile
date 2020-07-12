<?php
require_once '../../../include/cp_header.php';

if (preg_match("/^XOOPS Cube/",XOOPS_VERSION))
{
	$target_url = XOOPS_URL.'/modules/legacy/admin/index.php?action=PreferenceEdit&confmod_id='.$xoopsModule->getVar('mid');
}
else
{
	$target_url = XOOPS_URL.'/modules/system/admin.php?fct=preferences&op=showmod&mod='.$xoopsModule -> getVar('mid');
}



// Xmobile Module でアクセス可能なモジュールの設定
// プラグインファイル名を取得
$plugin_path = XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/plugins/';
if (is_dir($plugin_path))
{
	$handle = opendir($plugin_path);
	$module_dir_array = array();
	while (($file = readdir($handle)) !== false)
	{
		$file = preg_replace("/^([\w\-]+)\.php/","$1",$file);	//ファイル名にハイフンも許容する
		if (preg_match("/^[\w\-]+$/",$file))
		{
			array_push($module_dir_array,$file);
		}
	}
	closedir($handle);
	if (!empty($module_dir_array))
	{
		asort($module_dir_array);
	}
}

// アクティブなモジュールを取得
$module_handler =& xoops_gethandler('module');
$criteria = new CriteriaCompo(new Criteria('isactive', 1));
$mids = array_keys($module_handler->getList($criteria));

// xmobileで使用可能なモジュールの設定
$module_option = array();
foreach($mids as $mid)
{
	$module =& $module_handler->get($mid);
	$module_dirname = $module->getVar('dirname');
	$module_name = $module->getVar('name');
	if (in_array($module_dirname, $module_dir_array))
	{
		$module_option[$module_name] = $module_dirname;
	}
	unset($module);
}

//var_dump($module_option); die;
//print_r($module_option);
//echo implode(",",$module_option);
//$config_option_handler =& new XoopsConfigOptionHandler($xoopsDB);


$module =& $xoopsModule;
$config_handler =& xoops_gethandler('config');
$criteria =& new CriteriaCompo();
$criteria->add(new Criteria('conf_modid', $module->getVar('mid')));
$criteria->add(new Criteria('conf_name', 'modules_can_use'));
$config_array =& $config_handler->getConfigs($criteria);


$config =& $config_array[0];
$conf_id = intval($config->getVar('conf_id'));

$sql = "DELETE FROM ".$xoopsDB->prefix('configoption')." WHERE conf_id=".$conf_id;
$xoopsDB->queryF($sql);

foreach ($module_option as $confop_name=>$confop_value)
{
	$sql = "INSERT INTO ".$xoopsDB->prefix('configoption')." (conf_id, confop_name, confop_value) "." VALUES (".$conf_id.",'".$confop_name."','".$confop_value."')";
	$xoopsDB->queryF($sql);

// POSTでないので利用出来ない？
//	$config_option =& $config_handler->createConfigOption();
//	$config_option->setVar('conf_id', $conf_id);
//	$config_option->setVar('confop_name', $confop_name);
//	$config_option->setVar('confop_value', $confop_value);
//	$config_handler->insertConfig($config_option);
}


header('Location:'.$target_url);
?>
