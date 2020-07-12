<?php
$modversion['name']        = _MI_XMOBILE_NAME;
$modversion['version']     = 0.41;
$modversion['description'] = _MI_XMOBILE_DESC;
$modversion['credits']     = 'Original by hiro / Produced by Bluemoon inc.';
$modversion['author']      = 'Yoshi Sakai (bluemooninc.biz), shige-p (9demaio.com)';
$modversion['help']        = '';
$modversion['license']     = 'GPL see LICENSE';
$modversion['official']    = 0;
if(preg_match("/^XOOPS Cube/",XOOPS_VERSION))
{
	$modversion['image'] = 'images/xmobile.png';
}
else
{
	$modversion['image'] = 'images/xmobile_slogo.png';
}
$modversion['dirname'] = basename(dirname(__FILE__));

// Database Tables
$modversion['sqlfile']['mysql'] = 'sql/mysql.sql';
$modversion['tables'][0] = 'xmobile_session';
$modversion['tables'][1] = 'xmobile_subscriber';

// Templates

$modversion['templates'][0]['file'] = 'xmobile_index.html';
$modversion['templates'][0]['description'] = '';
$modversion['templates'][1]['file'] = 'xmobile_header.html';
$modversion['templates'][1]['description'] = '';
$modversion['templates'][2]['file'] = 'xmobile_footer.html';
$modversion['templates'][2]['description'] = '';
$modversion['templates'][3]['file'] = 'xmobile_contents.html';
$modversion['templates'][3]['description'] = '';
$modversion['templates'][4]['file'] = 'xmobile_default.html';
$modversion['templates'][4]['description'] = '';
$modversion['templates'][5]['file'] = 'xmobile_plugin.html';
$modversion['templates'][5]['description'] = '';
$modversion['templates'][6]['file'] = 'xmobile_login.html';
$modversion['templates'][6]['description'] = '';
$modversion['templates'][7]['file'] = 'xmobile_lostpass.html';
$modversion['templates'][7]['description'] = '';
$modversion['templates'][8]['file'] = 'xmobile_notifications.html';
$modversion['templates'][8]['description'] = '';
$modversion['templates'][9]['file'] = 'xmobile_pmessage.html';
$modversion['templates'][9]['description'] = '';
$modversion['templates'][10]['file'] = 'xmobile_register.html';
$modversion['templates'][10]['description'] = '';
$modversion['templates'][11]['file'] = 'xmobile_search.html';
$modversion['templates'][11]['description'] = '';
$modversion['templates'][12]['file'] = 'xmobile_userinfo.html';
$modversion['templates'][12]['description'] = '';
/*
$modversion['templates'][13]['file'] = 'xmobile_bulletin.html';
$modversion['templates'][13]['description'] = '';
$modversion['templates'][14]['file'] = 'xmobile_xoopsfaq.html';
$modversion['templates'][14]['description'] = '';
$modversion['templates'][15]['file'] = 'xmobile_cclinks.html';
$modversion['templates'][15]['description'] = '';
$modversion['templates'][16]['file'] = 'xmobile_contact.html';
$modversion['templates'][16]['description'] = '';
$modversion['templates'][17]['file'] = 'xmobile_d3blog.html';
$modversion['templates'][17]['description'] = '';
$modversion['templates'][18]['file'] = 'xmobile_d3forum.html';
$modversion['templates'][18]['description'] = '';
$modversion['templates'][19]['file'] = 'xmobile_eguide.html';
$modversion['templates'][19]['description'] = '';
$modversion['templates'][20]['file'] = 'xmobile_inquirysp.html';
$modversion['templates'][20]['description'] = '';
$modversion['templates'][21]['file'] = 'xmobile_myalbum.html';
$modversion['templates'][21]['description'] = '';
$modversion['templates'][22]['file'] = 'xmobile_newbb.html';
$modversion['templates'][22]['description'] = '';
$modversion['templates'][23]['file'] = 'xmobile_news.html';
$modversion['templates'][23]['description'] = '';
$modversion['templates'][24]['file'] = 'xmobile_piCal.html';
$modversion['templates'][24]['description'] = '';
$modversion['templates'][25]['file'] = 'xmobile_pico.html';
$modversion['templates'][25]['description'] = '';
$modversion['templates'][26]['file'] = 'xmobile_smartsection.html';
$modversion['templates'][26]['description'] = '';
$modversion['templates'][27]['file'] = 'xmobile_tinyd.html';
$modversion['templates'][27]['description'] = '';
$modversion['templates'][28]['file'] = 'xmobile_weblinks.html';
$modversion['templates'][28]['description'] = '';
$modversion['templates'][29]['file'] = 'xmobile_weblog.html';
$modversion['templates'][29]['description'] = '';
$modversion['templates'][30]['file'] = 'xmobile_wordpress.html';
$modversion['templates'][30]['description'] = '';
$modversion['templates'][31]['file'] = 'xmobile_xhnewbb.html';
$modversion['templates'][31]['description'] = '';
$modversion['templates'][32]['file'] = 'xmobile_xoopspoll.html';
$modversion['templates'][32]['description'] = '';
$modversion['templates'][33]['file'] = 'xmobile_yybbs.html';
$modversion['templates'][33]['description'] = '';
$modversion['templates'][34]['file'] = 'xmobile_xdbase.html';
$modversion['templates'][34]['description'] = '';
*/

//NE+ QR code Block
$modversion['blocks'][1]['file'] = 'xmobile_qr.php';
$modversion['blocks'][1]['name'] = _MI_XMOBILE_BLOCK_QR ;
$modversion['blocks'][1]['description'] = _MI_XMOBILE_BLOCK_QR_DESC ;
$modversion['blocks'][1]['show_func'] = 'b_xmobile_qr_show';
$modversion['blocks'][1]['edit_func'] = 'b_xmobile_qr_edit';
$modversion['blocks'][1]['options'] = '0|qrcode';
$modversion['blocks'][1]['template'] = 'xmobile_block_qr.html' ;
$modversion['blocks'][1]['can_clone'] = true ;

$modversion['blocks'][2]['file'] = 'xmobile_redirect.php';
$modversion['blocks'][2]['name'] = _MI_XMOBILE_BLOCK_REDIRECT;
$modversion['blocks'][2]['description'] = _MI_XMOBILE_BLOCK_REDIRECT_DESC;
$modversion['blocks'][2]['show_func'] = 'b_xmobile_redirect_show';

//Admin Menus
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = 'admin/index.php';

//Main Menus
$modversion['hasMain'] = 1;

//Search
$modversion['hasSearch'] = 0;

//Comments
$modversion['hasComments'] = 0;

// Notification
$modversion['hasNotification'] = 0;


//Config
$modversion['config'][] = array(
	'name'			=> 'access_level',
	'title'			=> '_MI_XMOBILE_ACCESS_LEVEL' ,
	'description'	=> '_MI_XMOBILE_ACCESS_LEVEL_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'		=> 2,
	'options'		=> array('_MI_XMOBILE_ALLOW_GUEST'=>0,'_MI_XMOBILE_ALLOW_USER'=>1,'_MI_XMOBILE_ALLOW_ALL'=>2)
) ;

$modversion['config'][] = array(
	'name'				=> 'access_terminal',
	'title'				=> '_MI_XMOBILE_ACCESS_TERM',
	'description'	=> '_MI_XMOBILE_ACCESS_TERM_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'		=> 1,
	'options'		=> array('_MI_XMOBILE_ALLOW_MOBILE_H'=>0,'_MI_XMOBILE_ALLOW_MOBILE_A'=>1,'_MI_XMOBILE_ALLOW_ALL_TERM'=>2)
) ;

$modversion['config'][] = array(
	'name'				=> 'login_terminal',
	'title'				=> '_MI_XMOBILE_LOGIN_TERM',
	'description'	=> '_MI_XMOBILE_LOGIN_TERM_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'		=> 0,
	'options'		=> array('_MI_XMOBILE_ALLOW_MOBILE_H'=>0,'_MI_XMOBILE_ALLOW_MOBILE_A'=>1,'_MI_XMOBILE_ALLOW_ALL_TERM'=>2)
) ;

$modversion['config'][] = array(
	'name'				=> 'allow_register',
	'title'				=> '_MI_XMOBILE_ALLOW_REGIST',
	'description'	=> '_MI_XMOBILE_ALLOW_REGIST_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 0,
	'options'			=> array()
) ;
/*
$modversion['config'][] = array(
	'name'				=> 'check_ip_address',
	'title'				=> '_MI_XMOBILE_CHK_IPADDRESS',
	'description'	=> '_MI_XMOBILE_CHK_IPADDRESS_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 1,
	'options'			=> array()
) ;
*/
$modversion['config'][] = array(
	'name'				=> 'use_easy_login',
	'title'				=> '_MI_XMOBILE_USE_EZLOGIN',
	'description'	=> '_MI_XMOBILE_USE_EZLOGIN_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 0,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'easy_login_limit',
	'title'				=> '_MI_XMOBILE_EZLOGIN_LIMIT',
	'description'	=> '_MI_XMOBILE_EZLOGIN_LIMIT_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 2592000,
	'options'			=> array('1day'=>86400,'3days'=>259200,'7days'=>604800,'14days'=>1209600,'30days'=>2592000,'60days'=>5184000,'180days'=>15552000)
) ;

$modversion['config'][] = array(
	'name'				=> 'debug_mode',
	'title'				=> '_MI_XMOBILE_DEBUG_MODE',
	'description'	=> '_MI_XMOBILE_DEBUG_MODE_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 0,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'logo',
	'title'				=> '_MI_XMOBILE_LOGO',
	'description'	=> '_MI_XMOBILE_LOGO_DESC',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'text',
	'default'			=> '',
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'sitename',
	'title'				=> '_MI_XMOBILE_SITE_NAME',
	'description'	=> '_MI_XMOBILE_SITE_NAME_DESC',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'text',
	'default'			=> @$xoopsConfig['sitename']
) ;

$modversion['config'][] = array(
	'name'				=> 'max_data_size',
	'title'				=> '_MI_XMOBILE_MAX_DATA_SIZE',
	'description'	=> '_MI_XMOBILE_MAX_DATA_SIZE_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 0,
	'options'			=> array('2K'=>2,'3K'=>3,'4K'=>4,'5K'=>5,'6K'=>6,'7K'=>7,'8K'=>8,'10K'=>10,'20K'=>20,'30K'=>30,'50K'=>50,'80K'=>80,'100K'=>100,'150K'=>150,'200K'=>200,'400K'=>400,'500K'=>500,'750K'=>750,'NO LIMIT'=>0)
) ;

$modversion['config'][] = array(
	'name'				=> 'session_limit',
	'title'				=> '_MI_XMOBILE_SESSION_LIMIT',
	'description'	=> '_MI_XMOBILE_SESSION_LIMIT_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 300,
	'options'			=> array('30sec'=>30,'1min'=>60,'5min'=>300,'10min'=>600,'30min'=>1800,'1h'=>3600,'3h'=>10800,'12h'=>43200,'24h'=>86400,'NO LIMIT'=>0)
) ;

$modversion['config'][] = array(
	'name'				=> 'use_accesskey',
	'title'				=> '_MI_XMOBILE_USE_ACCESSKEY',
	'description'	=> '_MI_XMOBILE_USE_ACCESSKEY_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 0,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'max_title_row',
	'title'				=> '_MI_XMOBILE_MAX_TITLE_ROW',
	'description'	=> '_MI_XMOBILE_MAX_TITLE_ROW_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 5,
	'options'			=> array('2TITLE'=>2,'3TITLE'=>3,'4TITLE'=>4,'5TITLE'=>5,'6TITLE'=>6,'7TITLE'=>7,'8TITLE'=>8,'9TITLE'=>9)
) ;

$modversion['config'][] = array(
	'name'				=>'max_title_length',
	'title'				=> '_MI_XMOBILE_MAX_TITLE_L',
	'description'	=> '_MI_XMOBILE_MAX_TITLE_L_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 16,
	'options'			=> array('5'=>10,'7'=>14,'8'=>16,'9'=>18,'10'=>20,'15'=>30,'20'=>40,'25'=>50,'30'=>60,'40'=>80,'50'=>100)
) ;

$modversion['config'][] = array(
	'name'				=>'title_order_sort',
	'title'				=> '_MI_XMOBILE_TITLE_SORT',
	'description'	=> '_MI_XMOBILE_TITLE_SORT_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'text',
	'default'			=> 'DESC',
	'options'			=> array('_MI_XMOBILE_SORT_ASC'=>'ASC','_MI_XMOBILE_SORT_DESC'=>'DESC')
) ;

$modversion['config'][] = array(
	'name'				=> 'cat_type',
	'title'				=> '_MI_XMOBILE_CAT_TYPE',
	'description'	=> '_MI_XMOBILE_CAT_TYPE_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 0,
	'options'			=> array('_MI_XMOBILE_TYPE_LIST'=>0,'_MI_XMOBILE_TYPE_SELECT'=>1)
) ;

$modversion['config'][] = array(
	'name'				=> 'show_item_count',
	'title'				=> '_MI_XMOBILE_SHOW_COUNT',
	'description'	=> '_MI_XMOBILE_SHOW_COUNT_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 1,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'show_recent_title',
	'title'				=> '_MI_XMOBILE_SHOW_RECENT',
	'description'	=> '_MI_XMOBILE_SHOW_RECENT_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 1,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'recent_title_row',
	'title'				=> '_MI_XMOBILE_RECENTTITLE_R',
	'description'	=> '_MI_XMOBILE_RECENTTITLE_R_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 3,
	'options'			=> array('1TITLE'=>1,'2TITLE'=>2,'3TITLE'=>3,'5TITLE'=>5,'10TITLE'=>10)
) ;

$modversion['config'][] = array(
	'name'				=> 'search_title_row',
	'title'				=> '_MI_XMOBILE_SEARCH_R',
	'description'	=> '_MI_XMOBILE_SEARCH_R_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 3,
	'options'			=> array('1TITLE'=>1,'2TITLE'=>2,'3TITLE'=>3,'5TITLE'=>5,'10TITLE'=>10)
) ;

$modversion['config'][] = array(
	'name'				=> 'comment_title_row',
	'title'				=> '_MI_XMOBILE_COMMENT_R',
	'description'	=> '_MI_XMOBILE_COMMENT_R_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 3,
	'options'			=> array('1TITLE'=>1,'2TITLE'=>2,'3TITLE'=>3,'5TITLE'=>5,'10TITLE'=>10)
) ;

$modversion['config'][] = array(
	'name'				=> 'pm_title_row',
	'title'				=> '_MI_XMOBILE_PM_R',
	'description'	=> '_MI_XMOBILE_PM_R_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 3,
	'options'			=> array('1TITLE'=>1,'2TITLE'=>2,'3TITLE'=>3,'5TITLE'=>5,'10TITLE'=>10)
) ;

$modversion['config'][] = array(
	'name'				=> 'tarea_rows',
	'title'				=> '_MI_XMOBILE_TAREA_ROWS',
	'description'	=> '_MI_XMOBILE_TAREA_ROWS_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 6,
	'options'			=> array('ROWS=3'=>3,'ROWS=4'=>4,'ROWS=5'=>5,'ROWS=6'=>6,'ROWS=7'=>7,'ROWS=8'=>8,'ROWS=10'=>10)
) ;

$modversion['config'][] = array(
	'name'				=> 'tarea_cols',
	'title'				=> '_MI_XMOBILE_TAREA_COLS',
	'description'	=> '_MI_XMOBILE_TAREA_COLS_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'int',
	'default'			=> 14,
	'options'			=> array('COLS=10'=>10,'COLS=12'=>12,'COLS=14'=>14,'COLS=15'=>15,'COLS=17'=>17,'COLS=20'=>20)
) ;

$modversion['config'][] = array(
	'name'				=> 'replace_link',
	'title'				=> '_MI_XMOBILE_REPLACE_LINK',
	'description'	=> '_MI_XMOBILE_REPLACE_LINK_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 0,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'use_thumbnail',
	'title'				=> '_MI_XMOBILE_USE_THUMBNAIL',
	'description'	=> '_MI_XMOBILE_USE_THUMBNAIL_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'			=> 1,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'thumbnail_width',
	'title'				=> '_MI_XMOBILE_THUMBNAIL',
	'description'	=> '_MI_XMOBILE_THUMBNAIL_DESC',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int',
	'default'			=> 200,
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'thumbnail_path',
	'title'				=> '_MI_XMOBILE_THUMBPASS',
	'description'	=> '_MI_XMOBILE_THUMBPASS_DESC',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'text',
	'default'			=> '/uploads/thumbs',
	'options'			=> array()
) ;

$modversion['config'][] = array(
	'name'				=> 'modules_can_use',
	'title'				=> '_MI_XMOBILE_MODULES',
	'description'	=> '_MI_XMOBILE_MODULES_DESC',
	'formtype'		=> 'select_multi',
	'valuetype'		=> 'array',
	'default'			=> ''
//	'options'			=> array()
) ;



?>
