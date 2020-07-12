<?php
if (!defined('XOOPS_ROOT_PATH')) exit();

$mydirname = strtolower(basename(__FILE__,'.php'));
$Pluginname = ucfirst($mydirname);
if (!preg_match("/^\w+$/", $Pluginname))
{
	trigger_error('Invalid pluginName');
	exit();
}
require XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/class/bloginfo.php';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobilePopnupblogPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobilePopnupblogPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// アクセス権限 0：権限なし、1：一覧閲覧許可、2：詳細閲覧許可、4：投稿許可、8：編集許可
if (!defined('XMOBILE_NOPERM')) define('XMOBILE_NOPERM', 0);
if (!defined('XMOBILE_CAN_READ_LIST')) define('XMOBILE_CAN_READ_LIST', 1);
if (!defined('XMOBILE_CAN_READ_DETAIL')) define('XMOBILE_CAN_READ_DETAIL', 2);
if (!defined('XMOBILE_CAN_POST')) define('XMOBILE_CAN_POST', 4);
if (!defined('XMOBILE_CAN_EDIT')) define('XMOBILE_CAN_EDIT', 8);
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobilePopnupblogPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('postid',	 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('uid',		 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('blogid',	 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('blog_count', XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('blog_date',	 XOBJ_DTYPE_TXTBOX, '0000-00-00 00:00:00', true, 20);
		$this->initVar('postid',	 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('postid',	 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('title',		 XOBJ_DTYPE_TXTBOX,	 '', true, 200);
		$this->initVar('post_text',	 XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('last_update',XOBJ_DTYPE_TXTBOX, '0000-00-00 00:00:00', true, 20);
		$this->initVar('votes_yes',	 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('votes_no',	 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('notifypub',	 XOBJ_DTYPE_INT,	 '0', true);
		$this->initVar('status',	 XOBJ_DTYPE_INT,	 '0', true);
	
		// define primary key
		$this->setKeyFields(array('postid'));
		$this->setAutoIncrementField('postid');
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
// example:
//		assignEditFormElement($key, $elementParams(array($name, $caption, )));

		$this->_formCaption = _MD_XMOBILE_POSTNEW;
		$this->assignFormElement('blogid', array('type'=>'hidden', 'caption'=>'blogid'));
//		$this->assignFormElement('user_id', array('type'=>'hidden', 'caption'=>'user_id'));
		$this->assignFormElement('title', array('type'=>'text', 'caption'=>_MD_XMOBILE_TITLE, 'params'=>array('size'=>20, 'maxlength'=>40)));
		$this->assignFormElement('contents', array('type'=>'textarea', 'caption'=>_MD_XMOBILE_CONTENTS, 'params'=>'contents'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initEditFormElements()
	{
		$this->_formCaption = _EDIT;
		$this->assignFormElement('blogid', array('type'=>'hidden', 'caption'=>'blogid'));
		$this->assignFormElement('user_id', array('type'=>'hidden', 'caption'=>'user_id'));
		$this->assignFormElement('title', array('type'=>'text', 'caption'=>_MD_XMOBILE_TITLE, 'params'=>array('size'=>20, 'maxlength'=>40)));
		$this->assignFormElement('contents', array('type'=>'textarea', 'caption'=>_MD_XMOBILE_CONTENTS, 'params'=>'contents'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initDeleteFormElements()
	{
		$this->_formCaption = _DELETE;
		$this->assignFormElement('blogid', array('type'=>'hidden', 'caption'=>'blogid'));
		$this->assignFormElement('user_id', array('type'=>'hidden', 'caption'=>'user_id'));
		$this->assignFormElement('cat_id', array('type'=>'hidden', 'caption'=>'cat_id'));
		$this->assignFormElement('title', array('type'=>'label', 'caption'=>_MD_XMOBILE_TITLE));
		$this->assignFormElement('agreement', array('type'=>'label', 'caption'=>_MD_XMOBILE_DELETE_AGREEMENT));
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobilePopnupblogPluginHandlerAbstract extends XmobilePluginHandler
{
	var $moduleDir = 'popnupblog';
	var $categoryTableName = 'popnupblog_categories';
	var $itemTableName = 'popnupblog';

	var $template = 'xmobile_popnupblog.html';
	var $category_id_fld = 'cat_id';
	var $category_pid_fld = 'topic_pid';
	var $category_title_fld = 'cat_title';
	var $category_order_fld = 'cat_order';

	var $item_id_fld = 'postid';
	var $item_cid_fld = 'cat_id';
	var $item_title_fld = 'title';
	var $item_description_fld = 'post_text';
	var $item_order_fld = 'blog_date';
	var $item_date_fld = 'blog_date';
	var $item_uid_fld = 'uid';
	var $item_hits_fld = 'blog_count';
	var $item_comments_fld = 'blogid';

	var $level_array = array('category','blog','post');
	var $levelState = null;
	
	var $blogid = null;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;
		if ( preg_match("/^\D+(\d*)$/", $mydirname,$matches) ){
			$number = $matches[1];
			$this->categoryTableName = 'popnupblog_categories';
			$this->itemTableName = 'popnupblog';
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->levelState = trim($this->utils->getGetPost('level','category'));
		$this->cat_id = intval($this->utils->getGetPost('cat_id',0));
		$this->blogid = intval($this->utils->getGetPost('blogid',0));
		$this->postid = intval($this->utils->getGetPost('postid',0));
		
		$this->item_criteria =& new CriteriaCompo();
		$item_criteria = new CriteriaCompo();
		$item_criteria->add(new Criteria('blog_date', 0, '>'));
		$this->item_criteria->add($item_criteria);
		global $xoopsModuleConfig;
		$this->item_order_sort = $xoopsModuleConfig['title_order_sort'];
		if (!is_null($this->item_order_sort))
		{
			$this->item_criteria->setOrder($this->item_order_sort);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 初期画面
	function getDefaultView()
	{
		$this->controller->render->template->assign('plugin_contents',$this->getItemList());
		$this->controller->render->template->assign('recent_item_list',$this->getRecentList());
//		$this->controller->render->template->assign('edit_link',$this->getEditLink());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 一覧画面
	function getListView()
	{
		$this->controller->render->template->assign('plugin_contents',$this->getItemList());
		$this->controller->render->template->assign('edit_link',$this->getEditLink());
	}	
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事詳細・コメント・編集用リンクの取得
// ただし、戻り値はオブジェクトではなくHTML
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
			$date = strtotime( $itemObject->getVar($this->item_date_fld) );
			$detail4html .= _MD_XMOBILE_DATE.$this->utils->getDateLong($date).'<br />';
			$detail4html .= _MD_XMOBILE_TIME.strftime('%H:%M',$date).'<br />';
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
					$memberonly_string = '<br /><a href="'.$register_url.'">'._BL_MEMBER_ONLY_READ_MORE.'</a>';
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
					$ext = 'cat_id='.$this->category_id.'&blogid='.$this->item_id.'&show_letterhalf=1';
					$read_next_url = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$ext);
					$division_next_string = '<br /><a href="'.$read_next_url.'">'._BL_ENTRY_SEPARATOR_NEXT.'</a>';
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
// 最新記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getRecentList()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();

//		$this->setItemCriteria();
		if ($xoopsModuleConfig['show_recent_title'] == 0)
		{
			return false;
		}
		$this->setNextViewState('detail');
		$this->setBaseUrl();
		$sql = 'SELECT DISTINCT i.cat_id, p.blogid, p.postid, p.title, p.blog_date, p.uid FROM '
			.$this->db->prefix('popnupblog').' p LEFT JOIN '.$this->db->prefix('popnupblog_info')
			.' i ON p.blogid=i.blogid ORDER BY p.blog_date DESC';

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getRecentList sql', $sql);

		$ret = $this->db->query($sql,$xoopsModuleConfig['recent_title_row']);
		if (!$ret)
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getRecentList db error', $this->db->error());
			return false;
		}

		$count = $this->db->getRowsNum($ret);
		if ($count == 0) // 表示するデータ無し
		{
			return false;
		}

		$recent_list = array();
		$baseUrl = $this->getBaseUrl();
		while($data = $this->db->fetchArray($ret)){
			$this->postid = intval($data['postid']);
			$id = intval($data['postid']);
			$cat_id = intval($data['cat_id']);
			$blogid = intval($data['blogid']);
			$postid = intval($data['postid']);
			$title = $myts->makeTboxData4Show($data['title']);
			//$url_parameter = '&amp;cat_id='.$cat_id.'&amp;blogid='.$blogid.'&amp;postid='.$postid;
			$url_parameter = '&amp;blogid='.$blogid.'&amp;postid='.$postid;
			$recent_list[] = array(
				'title' => $this->adjustTitle($title),
				'url' => $this->getBaseUrl() . $url_parameter,
				'date' => $data['blog_date']);
		}
		return $recent_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 編集画面
	function getEditView()
	{
		$this->controller->render->template->assign('item_detail',$this->renderEntryForm());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 投稿画面
	function getConfirmView()
	{
		$this->controller->render->template->assign('item_detail',$this->saveEntry());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getItemList()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();

		$this->setItemCriteria();

		if (!in_array($this->levelState, $this->level_array))
		{
			trigger_error('Invalid Level');
			exit();
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemList level', $this->levelState);

		switch ($this->levelState)
		{
			case 'category':

				$sql = "SELECT DISTINCT c.cat_id, c.cat_title, COUNT(f.blogid) AS post_count FROM "
					.$this->db->prefix('popnupblog_categories')." c INNER JOIN ".$this->db->prefix('popnupblog_info')
					." f ON c.cat_id=f.cat_id LEFT JOIN ".$this->db->prefix('popnupblog')." b ON b.blogid=f.blogid WHERE "
					.$this->item_criteria->render()." GROUP BY c.cat_id ORDER BY c.cat_order";
				$this->setNextViewState('list');
				// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$parent_path = '';
				$extra = 'level=blog';
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$extra = 'level=category&cat_id='.$this->cat_id;
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$this->list_id_fld = 'cat_id';
				$this->list_title_fld = 'cat_title';
				$list_title = _MD_XMOBILE_CATEGORIES_LIST;
				break;

			case 'blog':

				$sql = "SELECT DISTINCT f.blogid, f.title, COUNT(b.postid) AS post_count FROM "
					.$this->db->prefix('popnupblog_info')." f LEFT JOIN ".$this->db->prefix('popnupblog')
					." b ON b.blogid=f.blogid WHERE (".$this->item_criteria->render().") AND f.cat_id=".$this->cat_id." GROUP BY f.blogid ORDER BY f.title";
				$this->setNextViewState('list');
				// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$extra = 'level=post&cat_id='.$this->cat_id;
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$extra = 'level=blog&cat_id='.$this->cat_id.'&blogid='.$this->blogid;
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$parent_path = $this->getNicePathFromId($this->cat_id, $this->levelState).'<hr />';
				$this->list_pid_fld = 'cat_id';
				$this->list_id_fld = 'blogid';
				$this->list_title_fld = 'title';
				$list_title = _MD_XMOBILE_TOPIC_LIST;
				break;

			case 'post':

				$sql = "SELECT DISTINCT p.postid, p.title, p.post_text, p.blog_date, p.uid FROM "
					.$this->db->prefix('popnupblog')." p LEFT JOIN ".$this->db->prefix('popnupblog_info')
					." f ON p.blogid=f.blogid WHERE (".$this->item_criteria->render()
					.") AND p.blogid=".$this->blogid." ORDER BY p.blog_date ".$this->item_order_sort;
				$this->setNextViewState('detail');
				// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$extra = '';
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$extra = 'level=post';
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$parent_path = $this->getNicePathFromId($this->blogid, $this->levelState).'<hr />';
				$this->list_pid_fld = 'blogid';
				$this->list_id_fld = 'postid';
				$this->list_title_fld = 'title';
				$list_title = _MD_XMOBILE_POST_LIST;
				break;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemList', $sql);

		$list4html = $parent_path;

		$ret = $this->db->query($sql);
		$count = $this->db->getRowsNum($ret);
		if (!$ret)
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getItemList db error', $this->db->error());
			return false;
		}
		else
		{
			$list4html .= $list_title.'<br />';

			$pageNavi =& new XmobilePageNavigator($count, $xoopsModuleConfig['max_title_row'], 'start', $extra_arg);

			if (!$result = $this->db->query($sql,$xoopsModuleConfig['max_title_row'],$pageNavi->getStart()))
			{
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'getItemList db error', $this->db->error());
			}

			$result_n = $this->db->getRowsNum($result);
			if ($result_n > 0)
			{
				$number = 1;
				while($data = $this->db->fetchArray($result))
				{
					$id = intval($data[$this->list_id_fld]);

					$title = $myts->makeTboxData4Show($data[$this->list_title_fld]);
					$title = mb_strimwidth($title, 0, $xoopsModuleConfig['max_title_length'], '..', SCRIPT_CODE);

					$url_parameter = $baseUrl.'&amp;'.$this->list_id_fld.'='.$id;
					if ($xoopsModuleConfig['use_accesskey'])
					{
						$list4html .= '['.$number.']';
						$list4html .= '<a href="'.$url_parameter.'" accesskey="'.$number.'">'.$title.'</a>';
					}
					else
					{
						$list4html .= '<a href="'.$url_parameter.'">'.$title.'</a>';
					}
					if ($xoopsModuleConfig['show_item_count'])
					{
						if ($this->levelState != 'post')
						{
							$item_count = intval($data['post_count']);
							$list4html .= '('.sprintf(_MD_XMOBILE_NUMBER, $item_count).')';
						}
					}
					$list4html .= '<br />';
					$number++;
				}
				$list4html .= '<hr />';
				$list_page_navi = $pageNavi->renderNavi();
				if ($list_page_navi != '')
				{
					$list4html .= $list_page_navi.'<hr />';
				}
			}
			else
			{
				$list4html .= _MD_XMOBILE_NO_DATA.'<hr />';
			}

			return $list4html;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getNicePathFromId($sel_id, $level, $path='')
	{
		$myts =& MyTextSanitizer::getInstance();

		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'list',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());

		switch ($level)
		{
			case 'category':
				return $path;
				break;

			case 'blog':
				$sql = 'SELECT cat_id, cat_title FROM '.$this->db->prefix('popnupblog_categories').' WHERE cat_id='.$sel_id;
				$previous_level = 'category';
				$result = $this->db->query($sql);
				if ($this->db->getRowsNum($result) == 0)
				{
					return $path;
				}
				list($parentid,$name) = $this->db->fetchRow($result);
				$name = $myts->makeTboxData4Show($name);
				$baseUrl = $baseUrl.'&amp;level=blog&amp;cat_id='.$this->cat_id;
				$path = ' > <a href="'.$baseUrl.'">'.$name.'</a>'.$path;

				break;

			case 'post':
				$sql = 'SELECT blogid, title FROM '.$this->db->prefix('popnupblog_info').' WHERE blogid='.$sel_id;
				$previous_level = 'blog';

				$result = $this->db->query($sql);
				if ($this->db->getRowsNum($result) == 0)
				{
					return $path;
				}
				list($parentid,$name) = $this->db->fetchRow($result);
				$name = $myts->makeTboxData4Show($name);
				$baseUrl = $baseUrl.'&amp;level=post&amp;blogid='.$this->blogid;
				$path = ' > <a href="'.$baseUrl.'">'.$name.'</a>'.$path;
				break;

			default:
				return $path;
				break;
		}

		$path = $this->getNicePathFromId($parentid, $previous_level, $path);

		return $path;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getEditLink($id=0){
		$this->checkBlogAccess();
		if ($this->blog_access < 2){
			return false;
		}else{
			$edit_link = '';
			if ($id != 0)
			{
				$reply_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
				if ($this->blog_access >= 3){
					$edit_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
					$delete_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
					$edit_link .= '<a href="'.$edit_url.'&amp;entry_type=edit_entry&amp;cat_id='.$this->cat_id.'&amp;blogid='.$this->blogid.'&amp;postid='.$this->postid.'">'._EDIT.'</a>&nbsp;';
					$edit_link .= '<a href="'.$delete_url.'&amp;entry_type=delete_entry&amp;cat_id='.$this->cat_id.'&amp;blogid='.$this->blogid.'&amp;postid='.$this->postid.'">'._DELETE.'</a>';
				}
				$edit_link .= '<hr />';
			}
			$add_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
			$edit_link .= '<a href="'.$add_url.'&amp;entry_type=new_entry&amp;cat_id='.$this->cat_id.'&amp;blogid='.$this->blogid.'&amp;postid='.$this->item_id.'">'._MD_XMOBILE_POSTNEW.'</a>&nbsp;';
			return $edit_link;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderEntryForm()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();
		$this->setItemCriteria();

		$entry_type = htmlspecialchars($this->utils->getGetPost('entry_type', ''), ENT_QUOTES);
		$this->ticket = new XoopsGTicket;
		$this->checkBlogAccess();

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getEdit entry_type', $entry_type);

		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$baseUrl = preg_replace('/&amp;/i','&',$baseUrl);

		$entry_form = '';
		$entry_form .= '<form action="'.$baseUrl.'" method="post">';
		$entry_form .= '<div class ="form">';
		$entry_form .= $this->ticket->getTicketHtml();
		$entry_form .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
		$entry_form .= '<input type="hidden" name="HTTP_REFERER" value="'.$baseUrl.'" />';


		switch ($entry_type)
		{
			case 'new_entry':

				if ($this->blog_access < 2)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$subject = '';
				$post_text = '';
				$entry_form .= sprintf('<input type="hidden" name="blogid" value="%s" />', $this->blogid );
				break;

			case 'edit_entry':

				if ($this->blog_access < 3)	{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$sql = 'SELECT title,post_text FROM '.$this->db->prefix('popnupblog').' WHERE postid = '.$this->postid;
				$ret = $this->db->query($sql);
				if (!$ret){
					$this->utils->setDebugMessage(__CLASS__, 'getEdit db error', $this->db->error());	// debug
					return false;
				}
				while($data = $this->db->fetchArray($ret)){
					$subject = $myts->makeTboxData4Show($data['title']);
					$post_text = $myts->makeTareaData4Edit($data['post_text']);
				}
				$entry_form .= '<input type="hidden" name="postid" value="'.$this->postid.'" />';
				break;


			case 'delete_entry':

				if ($this->blog_access < 3){
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$sql = 'SELECT title,post_text,blog_date,uid FROM '.$this->db->prefix('popnupblog').' WHERE postid = '.$this->postid;
				$ret = $this->db->query($sql);
				if (!$ret){
					$this->utils->setDebugMessage(__CLASS__, 'getEdit db error', $this->db->error());	// debug
					return false;
				}
				while($data = $this->db->fetchArray($ret)){
					$rep_subject = $myts->makeTboxData4Show($data['title']);
					$rep_post_text = $myts->makeTareaData4Edit($data['post_text']);
					$post_time = $data['blog_date'];
					$uname = $this->utils->getUnameFromId($data['uid']);
				}

				$entry_form .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
				$entry_form .= _MD_XMOBILE_TITLE.'<br />';
				$entry_form .= $rep_subject.'<hr />';
				if ($rep_post_text !== '')
				{
					$entry_form .= $rep_post_text.'<hr />';
				}
				$entry_form .= _MD_XMOBILE_CONTRIBUTOR.'&nbsp;'.$uname.'<br />';
				$entry_form .= _MD_XMOBILE_DATE.'&nbsp;'.$post_time.'<br />';
				if ($child_count > 0)
				{
					$entry_form .= _MD_XMOBILE_ASK_DELETE_ALL.'<hr />';
				}
				else
				{
					$entry_form .= _MD_XMOBILE_ASK_DELETE_THIS.'<hr />';
				}
				$entry_form .= '<input type="hidden" name="cat_id" value="'.$this->cat_id.'" />';
				$entry_form .= '<input type="hidden" name="blogid" value="'.$this->blogid.'" />';
				$entry_form .= '<input type="hidden" name="postid" value="'.$this->postid.'" />';
				$entry_form .= '<input type="hidden" name="entry_type" value="delete_entry" />';
				$entry_form .= '<input type="submit" name="submit" value="'._DELETE.'" />&nbsp;';
				$entry_form .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
				$entry_form .= '</div>';
				$entry_form .= '</form>';

				return $entry_form;

				break;
		}

			$entry_form .= _MD_XMOBILE_TITLE.'<br />';
			$entry_form .= '<input type="text" name="subject" value="'.$subject.'" /><br />';
			$entry_form .= _MD_XMOBILE_MESSAGE.'<br />';
			$entry_form .= '<textarea rows="'.$xoopsModuleConfig['tarea_rows'].'" cols="'.$xoopsModuleConfig['tarea_cols'].'" name="post_text">'.$post_text.'</textarea><br />';
			$entry_form .= '<input type="hidden" name="cat_id" value="'.$this->cat_id.'" />';
			$entry_form .= '<input type="hidden" name="blogid" value="'.$this->blogid.'" />';
			$entry_form .= '<input type="hidden" name="postid" value="'.$this->postid.'" />';
			$entry_form .= '<input type="hidden" name="entry_type" value="'.$entry_type.'" />';
			$entry_form .= '<input type="submit" name="submit" value="'._SUBMIT.'" />&nbsp;';
			$entry_form .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
			$entry_form .= '</div>';
			$entry_form .= '</form>';

		return $entry_form;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function saveEntry()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();
		$this->setItemCriteria();

		if (isset($_POST['cancel']))
		{
			$baseUrl = XMOBILE_URL.'/?act='.$this->controller->getActionState().'&plg='.$this->controller->getPluginState();
			if ($this->sessionHandler->getSessionID() != '')
			{
				$baseUrl .= '&sess='.$this->sessionHandler->getSessionID();
			}
			if ($this->postid != 0)
			{
				$baseUrl .= '&view=detail';
				$baseUrl .= '&start='.$this->start;
				$baseUrl .= '&postid='.$this->postid;
			}
			elseif ($this->blogid != 0)
			{
				$baseUrl .= '&view=list&level=topic';
				$baseUrl .= '&blogid='.$this->blogid;
			}
			header('Location: '.$baseUrl);
			exit();
		}
		$this->checkBlogAccess();
		$entry_type = htmlspecialchars($this->utils->getGetPost('entry_type', ''), ENT_QUOTES);
		$this->ticket = new XoopsGTicket;

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView entry_type', $entry_type);

		//チケットの確認
//		if (!$ticket_check = $this->ticket->check())
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
//			return $this->ticket->getErrors();
			return _MD_XMOBILE_TICKET_ERROR;
		}
		$new_id = 0;
		$allow_html = 0;

		$pid = intval($this->utils->getPost('pid', 0));
		$post_time = time();
		$uid = $this->sessionHandler->getUid();
		$poster_ip = $myts->makeTboxData4Save($_SERVER['REMOTE_ADDR']);
		$subject = $myts->makeTboxData4Save($this->utils->getPost('subject', ''));
		$nosmiley = intval($this->utils->getPost('nosmiley', 0));
		$icon = $myts->makeTboxData4Save($this->utils->getPost('icon', ''));
		$attachsig = intval($this->utils->getPost('attachsig', 0));
		$post_text = $myts->makeTareaData4Save($this->utils->getPost('post_text', ''));

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView sql', $sql);
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView allow_html', $allow_html);
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView nohtml', $nohtml);

		if ($entry_type != 'delete_entry' && $subject == '')
		{
			$body = _MD_XMOBILE_NEED_DATA;
			$body .= $this->getEditView();
			return $body;
		}

		switch ($entry_type)
		{
			case 'new_entry':

				if ($this->blog_access < 2)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$blogDate = date("Y-m-d H:i:s", time());
				$blog_count= $notifypub = NULL;
				$status = 1;
				$sql_insert_blog = sprintf("INSERT INTO %s(uid, blogid, blog_count, blog_date, title, post_text,status,notifypub) values(%u,%u,%u,'%s','%s','%s',%u,%u)"
					,$this->db->prefix('popnupblog'), $uid, $this->blogid, $blog_count, $blogDate, $subject, $post_text,$status,$notifypub);
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_blog', $sql_insert_blog);
				if (!$ret_insert_blog = $this->db->query($sql_insert_blog)){
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_blog error', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED;
				}
				$this->utils->setDebugMessage(__CLASS__, 'insert new_entry', 'Success ');
				return _MD_XMOBILE_INSERT_SUCCESS;
				break;

			case 'edit_entry':
				if ($this->blog_access < 3){
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$sql_edit_posts = "UPDATE ".$this->db->prefix('popnupblog')." SET title='$subject',post_text='$post_text',last_update=NOW() WHERE postid=".$this->postid;
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts', $sql_edit_posts);
				if (!$ret_insert_posts = $this->db->query($sql_edit_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sqlUpdate error', $this->db->error());
					return _MD_XMOBILE_UPDATE_FAILED;
				}
				$this->utils->setDebugMessage(__CLASS__, 'edit_entry', 'Success');
				return _MD_XMOBILE_UPDATE_SUCCESS;
				break;

			case 'delete_entry':

				if ($this->blog_access < 3){
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$sql_delete_posts = "DELETE FROM ".$this->db->prefix('popnupblog')." WHERE postid = ".$this->postid;
				$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts', $sql_delete_posts);
				if (!$ret_delete_posts = $this->db->query($sql_delete_posts)){
					$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}
				$this->utils->setDebugMessage(__CLASS__, 'delete_entry', 'Success');
				return _MD_XMOBILE_DELETE_SUCCESS;
				break;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// @return int $blog_access フォーラムアクセス権限 0：権限なし、1：閲覧許可、2：投稿許可、3：編集許可
	function checkBlogAccess()
	{
		$uid = $this->sessionHandler->getUid();
		$user =& $this->sessionHandler->getUser();
		$blog_access_level = 0;
		$blog_type = 1;
		$is_mod = 0;
		$is_admin = 0;


		if ($this->blogid){
			$sql = 'SELECT group_read,group_post,uid FROM '.$this->db->prefix('popnupblog_info').' t WHERE t.blogid='.$this->blogid;
		} else {
			return $this->blog_access;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'checkBlogAccess sql', $sql);
		if (!$ret = $this->db->query($sql)){
			$this->utils->setDebugMessage(__CLASS__, 'checkBlogAccess sql error', $this->db->error());
		}
		$data=$this->db->fetchArray($ret);
		$group_read = explode(" ", $data['group_read'] );
		$group_post = explode(" ", $data['group_post'] );
		$this->blog_access = 0;
		if ($uid==$data['uid']){
			$this->blog_access = 3;
		}else{
			if (is_object($user)){
				$groupid_array = $this->utils->getGroupIdArray($user);
				$result = array_intersect($groupid_array, $group_post);
				if ( is_array($result) ){
					$this->blog_access = 2;
				} else {
					$result = array_intersect($groupid_array, $group_read);
					if ( is_array($result) ){
						$this->blog_access = 1;
					}
				}
			}
		}
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'blog_access', $this->blog_access);

		return $this->blog_access;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
