<?php
// Special thanks
// hodaka
// http://www.kuri3.net/

if (!defined('XOOPS_ROOT_PATH')) exit();

$mydirname = basename(__FILE__,'.php');
if (! defined( 'XOOPS_TRUST_PATH' )) die( 'set XOOPS_TRUST_PATH into mainfile.php' );
// set $mytrustdirname
require XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/mytrustdirname.php';
$mytrustdirpath = XOOPS_TRUST_PATH.'/modules/'.$mytrustdirname;

$Pluginname = ucfirst($mydirname);
if (!preg_match("/^\w+$/", $Pluginname))
{
	trigger_error('Invalid pluginName');
	exit();
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// アクセス権限 0：権限なし、1：一覧閲覧許可、2：詳細閲覧許可、4：投稿許可、8：編集許可
if (!defined('XMOBILE_NOPERM')) define('XMOBILE_NOPERM', 0);
if (!defined('XMOBILE_CAN_READ_LIST')) define('XMOBILE_CAN_READ_LIST', 1);
if (!defined('XMOBILE_CAN_READ_DETAIL')) define('XMOBILE_CAN_READ_DETAIL', 2);
if (!defined('XMOBILE_CAN_POST')) define('XMOBILE_CAN_POST', 4);
if (!defined('XMOBILE_CAN_EDIT')) define('XMOBILE_CAN_EDIT', 8);
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileWeblogd3PluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('blog_id', XOBJ_DTYPE_INT, null, true);
		$this->initVar('user_id', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('cat_id', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('created', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', false, 128);
		$this->initVar('contents', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('private', XOBJ_DTYPE_TXTBOX, '', false, 1);
		$this->initVar('comments', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('reads', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('trackbacks', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('description', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('dohtml', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('dobr', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('permission_group', XOBJ_DTYPE_TXTBOX, 'all', false, 255);

		// define primary key
		$this->setKeyFields(array('blog_id'));
		$this->setAutoIncrementField('blog_id');

//	  $this->initFormElements();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,1);
		$this->initVar('doxcode',XOBJ_DTYPE_INT,1);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initNewFormElements()
	{
		$this->_formCaption = _MD_XMOBILE_POSTNEW;
		$this->assignFormElement('blog_id', array('type'=>'hidden', 'caption'=>'blog_id'));
		$this->assignFormElement('title', array('type'=>'text', 'caption'=>_MD_XMOBILE_TITLE, 'params'=>array('size'=>20, 'maxlength'=>40)));
		$this->assignFormElement('contents', array('type'=>'textarea', 'caption'=>_MD_XMOBILE_CONTENTS, 'params'=>'contents'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initEditFormElements()
	{
		$this->_formCaption = _EDIT;
		$this->assignFormElement('blog_id', array('type'=>'hidden', 'caption'=>'blog_id'));
		$this->assignFormElement('user_id', array('type'=>'hidden', 'caption'=>'user_id'));
		$this->assignFormElement('title', array('type'=>'text', 'caption'=>_MD_XMOBILE_TITLE, 'params'=>array('size'=>20, 'maxlength'=>40)));
		$this->assignFormElement('contents', array('type'=>'textarea', 'caption'=>_MD_XMOBILE_CONTENTS, 'params'=>'contents'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initDeleteFormElements()
	{
		$this->_formCaption = _DELETE;
		$this->assignFormElement('blog_id', array('type'=>'hidden', 'caption'=>'blog_id'));
		$this->assignFormElement('user_id', array('type'=>'hidden', 'caption'=>'user_id'));
		$this->assignFormElement('cat_id', array('type'=>'hidden', 'caption'=>'cat_id'));
		$this->assignFormElement('title', array('type'=>'label', 'caption'=>_MD_XMOBILE_TITLE));
		$this->assignFormElement('agreement', array('type'=>'label', 'caption'=>_MD_XMOBILE_DELETE_AGREEMENT));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileWeblogd3PluginHandlerAbstract extends XmobilePluginHandler
{
	var $moduleDir = '';
	var $categoryTableName = '';
	var $itemTableName = '';

	var $category_id_fld = 'cat_id';
	var $category_pid_fld = 'cat_pid';
	var $category_title_fld = 'cat_title';
	var $category_order_fld = 'cat_id';

	var $item_id_fld = 'blog_id';
	var $item_cid_fld = 'cat_id';
	var $item_title_fld = 'title';
	var $item_description_fld = 'contents';
	var $item_order_fld = 'created';
	var $item_date_fld = 'created';
	var $item_uid_fld = 'user_id';
	var $item_hits_fld = 'reads';
	var $item_comments_fld = 'comments';

//	var $item_order_sort = 'DESC';

	var $weblog_perm = 0;
	var $weblog_cat_post = null;
	var $new_id = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;
		$this->categoryTableName = $mydirname.'_category';
		$this->itemTableName = $mydirname.'_entry';
		$this->ticket = new XoopsGTicket;
	}

	function prepare(&$controller)
	{
		parent::prepare($controller);

		$mytrustdirname_file = XOOPS_ROOT_PATH.'/modules/'.$this->moduleDir.'/mytrustdirname.php';
		if (file_exists($mytrustdirname_file) && defined('XOOPS_TRUST_PATH')) {
			include $mytrustdirname_file;
			global $xoopsConfig;
			$fileName = XOOPS_TRUST_PATH.'/modules/'.$mytrustdirname.'/language/'.$xoopsConfig['language'].'/main.php';
			if (file_exists($fileName)) {
				include_once $fileName;
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// モジュールのグループアクセス権限チェック
	function setModulePerm($gperm_name='module_read')
	{
		$pluginState = $this->controller->getPluginState();
		if ($pluginState == 'default')
		{
			$this->modulePerm = true;
		}
		else
		{
			$user =& $this->sessionHandler->getUser();
			$this->modulePerm = $this->utils->getModulePerm($user, $this->mid, $gperm_name='module_read');
		}
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'modulePerm', $this->modulePerm);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		function setItemCriteria()
		{
			$this->item_criteria =& new CriteriaCompo();
			$this->item_criteria->add(new Criteria('user_id', $this->sessionHandler->getUid()));
			$item_criteria_1 = new CriteriaCompo();
			$item_criteria_1->add(new Criteria('private', 'N'));
			$item_criteria_1->add(new criteria('created', time(), '<'));

			if ($this->moduleConfig['use_permissionsystem'])
			{
				$item_criteria_2 = new CriteriaCompo();
				$user =& $this->sessionHandler->getUser();
				$groupid_array = $this->utils->getGroupIdArray($user);
				if (is_object($user))
				{
					foreach($groupid_array as $groupid)
					{
						$item_criteria_2->add(new Criteria('permission_group', '%|'.$groupid.'|%', 'LIKE'),'OR');
					}
				}
				else
				{
					$groupid = 3;
					$item_criteria_2->add(new Criteria('permission_group', '%|'.$groupid.'|%', 'LIKE'),'OR');
				}
				$item_criteria_2->add(new Criteria('permission_group', 'all', 'LIKE'),'OR');
				$item_criteria_1->add($item_criteria_2);
			}
			$this->item_criteria->add($item_criteria_1,'OR');
		}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事詳細・コメント・編集用リンクの取得
// ただし、戻り値はオブジェクトではなくHTML
//  function getDetailView()
	function getItemDetail()
	{
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemDetail criteria', $this->item_criteria->render());
		// 一意のidではなくcriteriaで検索する為、オブジェクトの配列が返される
		if (!$itemObjectArray = $this->getObjects($this->item_criteria))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getItemDetail Error', $this->getErrors());
		}

		if (count($itemObjectArray) == 0) // 表示するデータ無し
		{
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		$itemObject = $itemObjectArray[0];

		if (!is_object($itemObject))
		{
			return false;
		}

		$this->item_id = $itemObject->getVar($this->item_id_fld);
		$url_parameter = $this->getBaseUrl();
		$itemObject->assignSanitizerElement();

		$detail4html = '';
		$detail4html .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
		// タイトル
		if (!is_null($this->item_title_fld))
		{
			$title = $itemObject->getVar($this->item_title_fld);
			$detail4html .= _MD_XMOBILE_TITLE.$title.'<br />';
		}
		// ユーザ名
		if (!is_null($this->item_uid_fld))
		{
			$uid = $itemObject->getVar($this->item_uid_fld);
			$uname = $this->getUserLink($uid);
			$detail4html .= _MD_XMOBILE_CONTRIBUTOR.$uname.'<br />';
		}
		// 日付・時刻
		if (!is_null($this->item_date_fld))
		{
			$date = $itemObject->getVar($this->item_date_fld);
			$detail4html .= _MD_XMOBILE_DATE.$this->utils->getDateLong($date).'<br />';
			// 変更点
			$detail4html .= _MD_XMOBILE_TIME.$this->utils->getTimeLong($date).'<br />';
		}
		// ヒット数
		if (!is_null($this->item_hits_fld))
		{
			$detail4html .= _MD_XMOBILE_HITS.$itemObject->getVar($this->item_hits_fld).'<br />';
			// ヒットカウントの増加
			$this->increaseHitCount($this->item_id);
		}
		// 詳細
		$description = '';
		if (!is_null($this->item_description_fld))
		{
			$description = $itemObject->getVar($this->item_description_fld);
			// メンバーのみ公開
			if ($this->moduleConfig['use_memberonly'])
			{
				if (!is_object($this->sessionHandler->getUser()))
				{
					$register_url = $this->utils->getLinkUrl('register',null,null,$this->sessionHandler->getSessionID());
					$memberonly_string = '<br /><a href="'.$register_url.'">'._MD_WEBLOG_MEMBER_ONLY_READ_MORE.'</a>';
					$description = preg_replace('/(---AnonymousUserCantReadUnderHere---).*$/sm',$memberonly_string,$description);
				}
				else
				{
					$description = preg_replace('/---AnonymousUserCantReadUnderHere---/','',$description);
				}
			}
			else
			{
				$description = preg_replace('/---AnonymousUserCantReadUnderHere---/','',$description);
			}
			// 前半後半分割
			$show_letterhalf = intval($this->utils->getGet('show_letterhalf', 0));
			if ($this->moduleConfig['use_separator'])
			{
				if (!$show_letterhalf)
				{
					$ext = 'cat_id='.$this->category_id.'&blog_id='.$this->item_id.'&show_letterhalf=1';
					$read_next_url = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$ext);
					$division_next_string = '<br /><a href="'.$read_next_url.'">'._MD_WEBLOG_ENTRY_SEPARATOR_NEXT.'</a>';
					$description = preg_replace('/(---UnderThisSeparatorIsLatterHalf---).*$/sm',$division_next_string,$description);
				}
				else
				{
					$description = preg_replace('/---UnderThisSeparatorIsLatterHalf---/','',$description);
				}
			}
			else
			{
				$description = preg_replace('/---UnderThisSeparatorIsLatterHalf---/','',$description);
			}

			$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
			$detail4html .= $description.'<br />';
		}
		// その他の表示フィールド
		if (count($this->item_extra_fld) > 0)
		{
			foreach($this->item_extra_fld as $key=>$caption)
			{
				if ($itemObject->getVar($key))
				{
					$detail4html .= $caption;
					$detail4html .= $itemObject->getVar($key).'<br />';
				}
			}
		}
		return $detail4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkPerm()
	{
		$privilege_system = $this->moduleConfig['privilege_system'];

		$groupid_array = $this->utils->getGroupIdArray($this->user);

		if (count($groupid_array) > 0)
		{
			$groups = join(',',$groupid_array);
		}
		else
		{
			$groups = $groups_array[0];
		}

		if ($this->getModuleAdmin())
		{
			$this->allowAdd = true;
			if (!is_null($this->item_id))
			{
				$this->allowEdit = true;
				$this->allowDelete = true;
			}
		}

		if ($privilege_system == 'weblog')
		{
			$sql = "SELECT priv_gid FROM ".$this->db->prefix($this->moduleDir.'_priv')." WHERE priv_gid IN(".$groups.")";

			// debug
			$this->utils->setDebugMessage(__CLASS__, 'privilege sql', $sql);

			$ret = $this->db->query($sql);
			$count = $this->db->getRowsNum($ret);
			if (is_object($this->user) && !$this->moduleConfig['adminonly'] && $count)
			{
				$this->allowAdd = true;
			}
		}
		elseif ($privilege_system == 'XOOPS')
		{
			$groupperm_handler =& xoops_gethandler('groupperm');
			$global_prems = $groupperm_handler->getItemIds('weblog_global', $groupid_array, $this->mid);

			foreach($global_prems as $value)
			{
				if ($value >= 4)
				{
					$this->allowAdd = true;
				}
			}

			if ($this->moduleConfig['category_post_permission'])
			{
				$this->weblog_cat_post_array = $groupperm_handler->getItemIds('weblog_cat_post', $groupid_array, $this->mid);
				if (count($this->weblog_cat_post_array) > 0)
				{
					$this->weblog_cat_post = join($this->weblog_cat_post_array,',');
				}
			}

		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'weblog_perm', $this->weblog_perm);
		$this->utils->setDebugMessage(__CLASS__, 'weblog_cat_post', $this->weblog_cat_post);

		$this->checkEntryAccess($this->item_id);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkEntryAccess($id=0)
	{
		if ($id == 0)
		{
			return false;
		}

//		$itemObject =& $this->get($id);
		$itemObject = $this->get($id);
		if (is_object($itemObject))
		{
			$cat_id = $itemObject->getVar('cat_id');
			$user_id = $itemObject->getVar('user_id');
			
			if ($user_id != 0)
			{
				if ($this->allowAdd == true && $this->uid == $user_id )
				{
					if ($this->moduleConfig['category_post_permission'])
					{
						if (in_array($cat_id, $this->weblog_cat_post_array))
						{
							$this->allowEdit = true;
						}
					}
					else
					{
						$this->allowEdit = true;
					}
				}
			}
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'checkEntryAccess', $this->weblog_perm);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setNewVars(&$record)
	{
		$record->setVar('private', 'N');

		if ($this->moduleConfig['default_dobr'])
		{
			$record->setVar('dobr', 1);
		}
		if ($this->moduleConfig['use_permissionsystem'])
		{
			if (count($this->moduleConfig['default_permission']) > 0)
			{
				$permission_group = '|1|'.join('|',$this->moduleConfig['default_permission']).'|';
			}
		}
		else
		{
			$permission_group = 'all';
		}
		$record->setVar('permission_group', $permission_group);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getForm()
	{
		$type = htmlspecialchars($this->utils->getGetPost('type', ''), ENT_QUOTES);

		switch ($type)
		{
			case 'new':

				$record =& $this->create();
				if (!is_object($record))
				{
					return false;
				}

				break;

			case 'edit':

			case 'delete':

				$record =& $this->get($this->item_id);

				if (!is_object($record))
				{
					return false;
				}
				break;
		}



		if (is_object($this->categoryTree) && !is_null($this->category_id_fld))
		{
			$all_cat_tree_arr = $this->categoryTree->getAllTreeArray();
			if ($this->moduleConfig['category_post_permission'] && count($this->weblog_cat_post_array) > 0)
			{
				$cat_tree_arr = array();
				foreach($all_cat_tree_arr as $key => $value)
				{
					if (in_array($key, $this->weblog_cat_post_array))
					{
						$cat_tree_arr[$key] = $value;
					}
				}
			}
			else
			{
				$cat_tree_arr = $all_cat_tree_arr;
			}
			$record->assignFormElement($this->category_id_fld, array('type'=>'select', 'caption'=>_MD_XMOBILE_CATEGORY, 'params'=>$cat_tree_arr));
		}

		if (!is_null($this->item_date_fld))
		{
			$date = $this->utils->getDateLong($record->getVar($this->item_date_fld));
			$record->assignFormElement($this->item_date_fld, array('type'=>'label', 'caption'=>_MD_XMOBILE_DATE, 'value'=>$date));
		}
		if (!is_null($this->item_uid_fld))
		{
			$record->assignFormElement($this->item_date_fld, array('type'=>'hidden', 'value'=>$this->uid));
		}

		$baseUrl = preg_replace('/&amp;/i','&',$this->baseUrl);
		$record->assignFormElement('HTTP_REFERER', array('type'=>'hidden', 'value'=>$this->baseUrl));
		$record->assignFormElement(session_name(), array('type'=>'hidden', 'value'=>session_id()));
		$record->assignFormElement('op', array('type'=>'hidden', 'value'=>'save'));
		$record->assignFormElement('type', array('type'=>'hidden', 'value'=>$type));
		$record->initFormElements($type);

//		return $this->renderForm(&$record, $this->baseUrl, $type);
		return $this->renderForm($record, $this->baseUrl, $type);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobileWeblogd3PluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobileWeblogd3PluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
