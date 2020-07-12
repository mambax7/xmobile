<?php
// NIPOPO OFFICIAL SITE
// http://nipopo.tv/
// I am a musician in Japan.
// Please listen to my tune if there is time.
// NIPOPO
// http://www.youtube.com/profile?user=tongarikids

if (!defined('XOOPS_ROOT_PATH')) exit();

$mydirname = basename(__FILE__,'.php');
$Pluginname = ucfirst($mydirname);
if (!preg_match("/^\w+$/", $Pluginname))
{
	trigger_error('Invalid pluginName');
	exit();
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobilewfsectionPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobilewfsectionPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
//////////////////////////////////////////////////////////////////////////
class XmobilewfsectionPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('itemid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('categoryid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('summary', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('display_summary', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('body', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('uid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('datesub', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('status', XOBJ_DTYPE_INT, '-1', true);
		$this->initVar('counter', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('weight', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('dohtml', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('dosmiley', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('doxcode', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('doimage', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('dobr', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('cancomment', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('comments', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('notifypub', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('created', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('hostname', XOBJ_DTYPE_TXTBOX, '', true, 20);

//////////////////////////////////////////////////////////////////////////

		// define primary key
		$this->setKeyFields(array('itemid'));
		$this->setAutoIncrementField('itemid');
	}
//////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		$this->initVar('dohtml',XOBJ_DTYPE_INT,$this->getVar('dohtml'));
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,$this->getVar('dosmiley'));
		$this->initVar('doxcode',XOBJ_DTYPE_INT,$this->getVar('doxcode'));
		$this->initVar('dobr',XOBJ_DTYPE_INT,$this->getVar('dobr'));
	}
}
//////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////
class XmobilewfsectionPluginHandlerAbstract extends XmobilePluginHandler
{
	var $template = 'xmobile_wfsection.html';
	var $categoryTableName = 'wfsection_categories';
	var $itemTableName = 'wfsection_items';

	var $category_id_fld = 'categoryid';
	var $category_pid_fld = 'parentid';
	var $category_title_fld = 'name';
	var $category_order_fld = 'categoryid';

	var $item_id_fld = 'itemid';
	var $item_cid_fld = 'categoryid';
	var $item_title_fld = 'title';
	var $item_description_fld = 'summary';
	var $item_order_fld = 'display_summary';
	var $item_date_fld = 'datesub';
	var $item_uid_fld = 'uid';
	var $item_extra_fld = array('body'=>'');
//	var $item_order_sort = 'DESC';

	var $category_read = array();
//  For Comment function
	var $item_hits_fld = 'counter';
	var $item_comments_fld = 'comments';
//////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
		$this->moduleDir = $mydirname;

		if (preg_match( '/^(\D+)(\d*)$/' , $mydirname , $regs ))
		{
			$dirnumber = $regs[2];
			$this->categoryTableName = 'wfsection'. $dirnumber . '_categories';
			$this->itemTableName = 'wfsection'. $dirnumber . '_items';
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setCategoryCriteria()
	{
		$this->category_criteria =& new CriteriaCompo();

		//Get group permissions handler
		$gperm_handler =& xoops_gethandler('groupperm');

		//Get user's groups
		$user =& $this->sessionHandler->getUser();
		$groups = $this->utils->getGroupIdArray($user);

		//Get all allowed item ids in this module and for this user's groups
		$this->category_read =& $gperm_handler->getItemIds('category_read', $groups, $this->getMid());

		if (count($this->category_read) > 0)
		{
			$this->category_criteria->add(new Criteria('categoryid', "(".implode(',', $this->category_read).")", 'IN'));
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();

		//Get group permissions handler
		$gperm_handler =& xoops_gethandler('groupperm');

		//Get user's groups
		$user =& $this->sessionHandler->getUser();
		$groups = $this->utils->getGroupIdArray($user);

		//Get all allowed item ids in this module and for this user's groups
		$item_read =& $gperm_handler->getItemIds('item_read', $groups, $this->getMid());

		$this->item_criteria->add(new Criteria('itemid', "(".implode(',', $item_read).")", 'IN'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// カテゴリ一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getCatList()
	{
		$this->setNextViewState('list');
		$this->setBaseUrl();
		$this->setCategoryParameter();

		//Get group permissions handler
//		$gperm_handler =& xoops_gethandler('groupperm');
		//Get user's groups
//		$user =& $this->sessionHandler->getUser();
//		$groups = $this->utils->getGroupIdArray($user);
		//Get all allowed item ids in this module and for this user's groups
//		$this->category_read =& $gperm_handler->getItemIds('category_read', $groups, $this->getMid());

		if (!is_null($this->category_pid_fld) || is_null($this->category_id))
		{
			$categoryArray = $this->categoryTree->getFirstChild($this->category_id);
		}
		else
		{
			$categoryArray = false;
		}

		// カテゴリのパンくずを表示
		$this->controller->render->template->assign('cat_path',$this->getCatPathFromId($this->category_id));

		if (!is_array($categoryArray))
		{
			return false;
		}

		$subcategory_count = count($categoryArray);
		if ($subcategory_count == 0) // 表示するデータ無し
		{
			return false;
		}

		if (!is_null($this->category_id))
		{
			$item_count = $this->getItemCountById();
		}
		else
		{
			$item_count = 0;
		}

		if ($item_count > 0)
		{
			$use_accesskey = false;
		}
		else
		{
			$use_accesskey = true;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getCatList subcategory_count', $subcategory_count);
		$this->utils->setDebugMessage(__CLASS__, 'getCatList item_count', $item_count);


		$cat_list = array();
		$i = 0;
		foreach($categoryArray as $category)
		{
			if (in_array($category[$this->category_id_fld], $this->category_read))
			{
				$id = $category[$this->category_id_fld];
				$title = $category[$this->category_title_fld];
				$url_parameter = $this->getBaseUrl();

				if (!is_null($this->category_pid_fld))
				{
					$pid = $category[$this->category_pid_fld];
					$url_parameter .= '&amp;'.$this->category_pid_fld.'='.$pid;
				}
				if (!is_null($this->category_id_fld))
				{
					$url_parameter .= '&amp;'.$this->category_id_fld.'='.$id;
				}
	//			$htmlBody .= $this->getListTitleLink($number,$id,$title,$url_parameter,$use_accesskey).'<br />';
				$number = $i + 1; // アクセスキー用の番号、1から開始
				$cat_list[$i]['key'] = $number;
				$cat_list[$i]['title'] = $this->adjustTitle($title);
				$cat_list[$i]['url'] = $url_parameter;
				$cat_list[$i]['item_count'] = sprintf(_MD_XMOBILE_NUMBER, $this->getChildItemCountById($id));
				$i++;
			}
		}
		return $cat_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事詳細・コメント・編集用リンクの取得
// ただし、戻り値はオブジェクトではなくHTML
	function getItemDetail()
	{

		// アクセス権限のチェック
		$item_id = intval($this->utils->getGet('itemid', 0));
		if ($item_id != 0)
		{
			//Get group permissions handler
			$gperm_handler =& xoops_gethandler('groupperm');

			//Get user's groups
			$user =& $this->sessionHandler->getUser();
			$groups = $this->utils->getGroupIdArray($user);

			//Get all allowed item ids in this module and for this user's groups
			$item_read =& $gperm_handler->getItemIds('item_read', $groups, $this->getMid());

			if (!in_array($item_id, $item_read))
			{
				$this->item_comments_fld = null;
				return _MD_XMOBILE_NO_DATA;
			}
		}

		return parent::getItemDetail();

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
//////////////////////////////////////////////////////////////////////////
?>
