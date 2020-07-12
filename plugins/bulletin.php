<?php
// Special thanks
// wye
// http://never-ever.info/

if (!defined('XOOPS_ROOT_PATH')) exit();
//////////////////////////////////////////////////////////////////////////
$mydirname = basename(__FILE__,'.php');
$Pluginname = ucfirst(strtolower($mydirname));
if ( !preg_match("/^\w+$/", $Pluginname) )
{
	trigger_error('Invalid pluginName');
	exit();
}
//////////////////////////////////////////////////////////////////////////
class XmobileBulletinPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('storyid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('uid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('created', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('published', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('expired', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('hostname', XOBJ_DTYPE_TXTBOX, '', true, 20);
		$this->initVar('html', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('smiley', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('br', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('xcode', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('hometext', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('bodytext', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('counter', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('topicid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('ihome', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('notifypub', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('type', XOBJ_DTYPE_TXTBOX, '', true, 5);
		$this->initVar('topicimg', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('comments', XOBJ_DTYPE_INT, '0', true);

		// define primary key
		$this->setKeyFields(array('storyid'));
		$this->setAutoIncrementField('storyid');
	}
//////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		$dohtml = 0;
		$dosmiley = 0;
		$doxcode = 0;
		$dobr = 0;

		if ( $this->getVar('html') != 0 ) $dohtml = 1;
		if ( $this->getVar('smiley') != 0 ) $dosmiley = 1;
		if ( $this->getVar('xcode') != 0 ) $doxcode = 1;
		if ( $this->getVar('br') != 0 ) $dobr = 1;

		$this->initVar('dohtml',XOBJ_DTYPE_INT,$dohtml);
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,$dosmiley);
		$this->initVar('doxcode',XOBJ_DTYPE_INT,$doxcode);
		$this->initVar('dobr',XOBJ_DTYPE_INT,$dobr);
	}
}
//////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////
class XmobileBulletinPluginHandlerAbstract extends XmobilePluginHandler
{
	var $template = 'xmobile_bulletin.html';
	var $categoryTableName = 'bulletin_topics';
	var $itemTableName = 'bulletin_stories';
	var $category_id_fld = 'topic_id';
	var $category_pid_fld = 'topic_pid';
	var $category_title_fld = 'topic_title';
	var $category_order_fld = 'topic_id';

	var $item_id_fld = 'storyid';
	var $item_cid_fld = 'topicid';
	var $item_title_fld = 'title';
	var $item_description_fld = 'hometext';
	var $item_order_fld = 'published';
	var $item_date_fld = 'published';
	var $item_uid_fld = 'uid';
	var $item_hits_fld = 'counter';
	var $item_comments_fld = 'comments';
	var $item_extra_fld = array('bodytext'=>'');
//////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
		$this->moduleDir = $mydirname;
		$this->categoryTableName = $mydirname. '_topics';
		$this->itemTableName = $mydirname . '_stories';
	}
//////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
		$item_criteria_1 = new CriteriaCompo();
		$item_criteria_1->add(new Criteria('published', 0, '>'));
		$item_criteria_1->add(new Criteria('published', time(), '<='));
		$item_criteria_2 = new CriteriaCompo();
		$item_criteria_2->add(new Criteria('expired', 0));
		$item_criteria_2->add(new Criteria('expired', time(), '>'),'OR');
		$this->item_criteria->add($item_criteria_1);
		$this->item_criteria->add($item_criteria_2);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkPerm()
	{
		// 編集を許可する場合は、各プロパティをtrueに設定
	}
//////////////////////////////////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobileBulletinPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobileBulletinPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
//////////////////////////////////////////////////////////////////////////
?>
