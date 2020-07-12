<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileXoopsFaqPlugin extends XmobilePlugin
{
	function XmobileXoopsFaqPlugin()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();
		// define object elements
		$this->initVar('contents_id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('category_id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('contents_title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('contents_contents', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('contents_time', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('contents_order', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('contents_visible', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('contents_nohtml', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('contents_nosmiley', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('contents_noxcode', XOBJ_DTYPE_INT, '0', true);
		// define primary key
		$this->setKeyFields(array('contents_id'));
		$this->setAutoIncrementField('contents_id');
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		$dohtml = 0;
		$dosmiley = 0;
		$doxcode = 0;

		if ($this->getVar('contents_nohtml') == 0) $dohtml = 1;
		if ($this->getVar('contents_nosmiley') == 0) $dosmiley = 1;
		if ($this->getVar('contents_noxcode') == 0) $doxcode = 1;

		$this->initVar('dohtml',XOBJ_DTYPE_INT,$dohtml);
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,$dosmiley);
		$this->initVar('doxcode',XOBJ_DTYPE_INT,$doxcode);
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileXoopsFaqPluginHandler extends XmobilePluginHandler
{
	var $template = 'xmobile_xoopsfaq.html';
	var $moduleDir = 'xoopsfaq';
	var $categoryTableName = 'xoopsfaq_categories';
	var $itemTableName = 'xoopsfaq_contents';
// category parameters
	var $category_id_fld = 'category_id';
	var $category_title_fld = 'category_title';
	var $category_order_fld = 'category_order';
// item parameters
	var $item_id_fld = 'contents_id';
	var $item_cid_fld = 'category_id';
	var $item_title_fld = 'contents_title';
	var $item_description_fld = 'contents_contents';
	var $item_order_fld = 'contents_order';
	var $item_date_fld = 'contents_time';
//	var $item_order_sort = 'ASC';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileXoopsFaqPluginHandler($db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
		$this->item_criteria->add(new Criteria('contents_visible', 0, '<>'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setCategoryId()
	{
		$this->category_id = $this->utils->getGetPost($this->category_id_fld, null);
		if (is_null($this->category_id) && !is_null($this->item_cid_fld))
		{
			$this->category_id = $this->utils->getGetPost($this->item_cid_fld, null);
		}

		if (!is_null($this->category_id))
		{
			$this->category_id = intval($this->category_id);
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'category_id', $this->category_id);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
