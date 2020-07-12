<?php
if (!defined('XOOPS_ROOT_PATH')) exit();

$mydirname = strtolower(basename(__FILE__,'.php'));
$Pluginname = ucfirst($mydirname);
if (!preg_match("/^\w+$/", $Pluginname))
{
	trigger_error('Invalid pluginName');
	exit();
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobileXhnewbbPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobileXhnewbbPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileXhnewbbPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('post_id', XOBJ_DTYPE_INT, null, true);
		$this->initVar('pid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('topic_id', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('forum_id', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('post_time', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('uid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('poster_ip', XOBJ_DTYPE_TXTBOX, '', false, 15);
		$this->initVar('subject', XOBJ_DTYPE_TXTBOX, '', false, 255);
		$this->initVar('nohtml', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('nosmiley', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('icon', XOBJ_DTYPE_TXTBOX, '', false, 25);
		$this->initVar('attachsig', XOBJ_DTYPE_INT, 0, false);

		// define primary key
		$this->setKeyFields(array('post_id'));
		$this->setAutoIncrementField('post_id');

//		$this->initFormElements();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initFormElements()
	{
		if (!$this->isNew())
		{
			$this->assignEditFormElement(array('name'=>'post_id','type'=>'hidden','value'=>'post_id'));
			$this->_formCaption = _EDIT;
		}
		else
		{
			$this->_formCaption = _CREATE;
		}
		$this->assignEditFormElement(array('name'=>'pid','type'=>'hidden','value'=>'pid'));
		$this->assignEditFormElement(array('name'=>'topic_id','type'=>'hidden','value'=>'topic_id'));
		$this->assignEditFormElement(array('name'=>'forum_id','type'=>'hidden','value'=>'forum_id'));
		$this->assignEditFormElement(array('name'=>'subject','type'=>'text','title'=>_MD_XMOBILE_TITLE, 'value'=>'subject', 'size'=>20, 'maxlength'=>255));

		return true;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		$dohtml = 0;
		$dosmiley = 0;

		if ($this->getVar('nohtml') == 0) $dohtml = 1;
		if ($this->getVar('nosmiley') == 0) $dosmiley = 1;

		$this->initVar('dohtml',XOBJ_DTYPE_INT,$dohtml);
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,$dosmiley);
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileXhnewbbPluginHandlerAbstract extends XmobilePluginHandler
{
	var $template = 'xmobile_xhnewbb.html';
	var $moduleDir = 'xhnewbb';
	var $categoryTableName = 'xhnewbb_topics';
	var $itemTableName = 'xhnewbb_posts';

	var $categories_table;
	var $forums_table;
	var $topics_table;
	var $posst_table;
	var $forum_mods_table;
	var $forum_access_table;
	var $posts_text_table;
	var $users2topics_table;

	var $category_id_fld = 'topic_id';

	var $item_id_fld = 'post_id';
	var $item_cid_fld = 'topic_id';
	var $item_title_fld = 'subject';
	var $item_order_fld = 'post_time';
	var $item_date_fld = 'post_time';
	var $item_uid_fld = 'uid';

	var $level_array = array('category','forum','topic','post');
	var $levelState = null;
	var $list_pid_fld = null;
	var $list_id_fld = null;
	var $list_title_fld = null;

	var $cat_id = null;
	var $forum_id = null;
	var $topic_id = null;
	var $post_id = null;

	var $forum_access = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;

		$this->categories_table = $this->db->prefix('xhnewbb_categories');
		$this->forums_table = $this->db->prefix('xhnewbb_forums');
		$this->topics_table = $this->db->prefix('xhnewbb_topics');
		$this->posts_table = $this->db->prefix('xhnewbb_posts');
		$this->forum_mods_table = $this->db->prefix('xhnewbb_forum_mods');
		$this->forum_access_table = $this->db->prefix('xhnewbb_forum_access');
		$this->posts_text_table = $this->db->prefix('xhnewbb_posts_text');
		$this->users2topics_table = $this->db->prefix('xhnewbb_users2topics');

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->levelState = trim($this->utils->getGetPost('level','category'));
		$this->cat_id = intval($this->utils->getGetPost('cat_id',0));
		$this->forum_id = intval($this->utils->getGetPost('forum_id',0));
		$this->topic_id = intval($this->utils->getGetPost('topic_id',0));
		$this->post_id = intval($this->utils->getGetPost('post_id',0));

		$this->item_criteria =& new CriteriaCompo();

		$user =& $this->sessionHandler->getUser();
		if (is_object($user))
		{
			$item_criteria_a =& new CriteriaCompo();
			$item_criteria_a->add(new Criteria('f.forum_type', 1));
			$item_criteria_c =& new CriteriaCompo();
			$item_criteria_c->add(new Criteria('a.user_id', $user->getVar('uid')));
			//$groups =& $user->getGroups();
			$groups = $user->getGroups();
			$groupid_array = $this->utils->getGroupIdArray($user);
			foreach($groupid_array as $groupid)
			{
				$item_criteria_c->add(new Criteria('a.groupid', $groupid),'OR');
			}
			$item_criteria_a->add($item_criteria_c);
			$this->item_criteria->add($item_criteria_a);
		}
		else
		{
			$item_criteria_a =& new CriteriaCompo();
			$item_criteria_a->add(new Criteria('f.forum_type', 1));
			$item_criteria_c =& new CriteriaCompo();
			$item_criteria_c->add(new Criteria('a.user_id', 0));
			$item_criteria_c->add(new Criteria('a.groupid', XOOPS_GROUP_ANONYMOUS),'OR');
			$item_criteria_a->add($item_criteria_c);
			$this->item_criteria->add($item_criteria_a);
		}
		$item_criteria_b =& new CriteriaCompo();
		$item_criteria_b->add(new Criteria('f.forum_type', 1, '<>'));
		$this->item_criteria->add($item_criteria_b,'OR');

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
// 詳細画面
// データ詳細・編集用リンクを表示
// データ詳細は丸ごとHTMLでitem_detailとして出力
	function getDetailView()
	{
		global $xoopsConfig;

		$myts =& MyTextSanitizer::getInstance();

		$this->setBaseUrl();
		$this->setItemParameter();
		$this->setItemDetailPageNavi();


		$sql = "SELECT DISTINCT p.post_id, p.subject, p.post_time, p.uid, p.nohtml, p.nosmiley, f.cat_id, t.topic_id, f.forum_id, f.cat_id FROM ".$this->posts_table." p LEFT JOIN ".$this->topics_table." t ON p.topic_id=t.topic_id LEFT JOIN ".$this->forums_table." f ON t.forum_id=f.forum_id LEFT JOIN ".$this->forum_access_table." a ON f.forum_id=a.forum_id WHERE (".$this->item_criteria->render().") AND t.topic_id=".$this->topic_id." ORDER BY p.post_time ".$this->item_order_sort;

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getDetailView sql', $sql);
		if (!$ret = $this->db->query($sql,$this->itemDetailPageNavi->getPerpage(),$this->itemDetailPageNavi->getStart()))
		{
			$this->utils->setDebugMessage(__CLASS__, 'getDetailView db error', $this->db->error());
			return false;
		}

		$ret_n = $this->db->getRowsNum($ret);
		if ($ret_n == 0)
		{
			return false;
		}

		$detail4html = '';
		$detail4html .= $this->getNicePathFromId($this->topic_id, 'post').'<hr />';

		$detail4html .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
		while($row = $this->db->fetchArray($ret))
		{
			$this->item_id = intval($row[$this->item_id_fld]);
			$this->post_id = $this->item_id;
			$url_parameter = $this->getBaseUrl();
			// タイトル
			$title = $myts->makeTboxData4Show($row[$this->item_title_fld]);
			$detail4html .= _MD_XMOBILE_TITLE.$title.'<br />';
			// ユーザ名
			$uid = intval($row[$this->item_uid_fld]);
			$uname = $this->getUserLink($uid);
			$detail4html .= _MD_XMOBILE_CONTRIBUTOR.$uname.'<br />';
			// 日付・時刻
			$date = intval($row[$this->item_date_fld]);
			$detail4html .= _MD_XMOBILE_DATE.$this->utils->getDateLong($date).' '.$this->utils->getTimeLong($date).'<br />';

			if ($row['nohtml'] == 0) $dohtml = 1; else $dohtml = 0;
			if ($row['nosmiley'] == 0) $dosmiley = 1; else $dosmiley = 0;

			$sql = 'SELECT post_text FROM '.$this->posts_text_table.' WHERE post_id = '.$this->item_id;
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getDetailView select post_text sql', $sql);
			if (!$ret = $this->db->query($sql))
			{
				$this->utils->setDebugMessage(__CLASS__, 'getDetailView select post_text db error', $this->db->error());
			}
			$ret_n = $this->db->getRowsNum($ret);
			if ($ret_n > 0)
			{
				list($post_text) = $this->db->fetchRow($ret);
				$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
				$detail4html .= $myts->makeTareaData4Show($post_text,$dohtml,$dosmiley,1).'<br />';
			}
		}

		// increase topic views
		$sql = 'UPDATE '.$this->topics_table.' SET topic_views=topic_views+1 WHERE topic_id='.$this->topic_id;
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getDetailView sql_increment', $sql);
		if (!$ret = $this->db->queryF($sql))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getDetailView sql_increment error', $this->db->error());
		}

		$this->controller->render->template->assign('item_detail',$detail4html);
		$this->controller->render->template->assign('item_detail_page_navi',$this->itemDetailPageNavi->renderNavi());
		$this->controller->render->template->assign('edit_link',$this->getEditLink($this->item_id));
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

				$sql = "SELECT DISTINCT c.cat_id, c.cat_title, SUM(f.forum_posts) AS post_count FROM ".$this->categories_table." c INNER JOIN ".$this->forums_table." f ON c.cat_id=f.cat_id LEFT JOIN ".$this->forum_access_table." a ON a.forum_id=f.forum_id WHERE ".$this->item_criteria->render()." GROUP BY f.cat_id ORDER BY c.cat_order";
				$this->setNextViewState('list');
				// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$extra = 'level=forum';
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$extra = 'level=category&cat_id='.$this->cat_id;
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$parent_path = '';

				$this->list_id_fld = 'cat_id';
				$this->list_title_fld = 'cat_title';
				$list_title = _MD_XMOBILE_CATEGORIES_LIST;

				break;

			case 'forum':

				$sql = "SELECT DISTINCT f.forum_id, f.forum_name, f.forum_posts as post_count FROM ".$this->forums_table." f LEFT JOIN ".$this->forum_access_table." a ON a.forum_id=f.forum_id WHERE (".$this->item_criteria->render().") AND f.cat_id=".$this->cat_id." ORDER BY f.forum_weight";
				$this->setNextViewState('list');
				// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$extra = 'level=topic&cat_id='.$this->cat_id;
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$extra = 'level=forum&cat_id='.$this->cat_id.'&forum_id='.$this->forum_id;
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$parent_path = $this->getNicePathFromId($this->cat_id, $this->levelState).'<hr />';

				$this->list_pid_fld = 'cat_id';
				$this->list_id_fld = 'forum_id';
				$this->list_title_fld = 'forum_name';
				$list_title = _MD_XMOBILE_FORUM_LIST;

				break;

			case 'topic':

				$sql = "SELECT DISTINCT t.topic_id, t.topic_title, COUNT(p.post_id) AS post_count FROM ".$this->posts_table." p LEFT JOIN ".$this->topics_table." t ON p.topic_id=t.topic_id LEFT JOIN ".$this->forums_table." f ON t.forum_id=f.forum_id LEFT JOIN ".$this->forum_access_table." a ON f.forum_id=a.forum_id WHERE (".$this->item_criteria->render().") AND t.forum_id=".$this->forum_id." GROUP BY p.topic_id ORDER BY t.topic_time ".$this->item_order_sort;
				$this->setNextViewState('list');
				// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$extra = 'level=post&cat_id='.$this->cat_id.'&forum_id='.$this->forum_id;
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$extra = 'level=topic&cat_id='.$this->cat_id.'&forum_id='.$this->forum_id.'&topic_id='.$this->topic_id;
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$parent_path = $this->getNicePathFromId($this->forum_id, $this->levelState).'<hr />';

				$this->list_pid_fld = 'forum_id';
				$this->list_id_fld = 'topic_id';
				$this->list_title_fld = 'topic_title';
				$list_title = _MD_XMOBILE_TOPIC_LIST;

				break;

			case 'post':

				$sql = "SELECT DISTINCT p.post_id, p.subject, p.post_time, p.uid FROM ".$this->posts_table." p LEFT JOIN ".$this->topics_table." t ON p.topic_id=t.topic_id LEFT JOIN ".$this->forums_table." f ON t.forum_id=f.forum_id LEFT JOIN ".$this->forum_access_table." a ON f.forum_id=a.forum_id WHERE (".$this->item_criteria->render().") AND p.topic_id=".$this->topic_id." ORDER BY p.post_time ".$this->item_order_sort;
				$this->setNextViewState('detail');
				// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$extra = 'cat_id='.$this->cat_id.'&forum_id='.$this->forum_id;
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$extra = 'level=post&cat_id='.$this->cat_id.'&forum_id='.$this->forum_id.'&topic_id='.$this->topic_id;
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
				$parent_path = $this->getNicePathFromId($this->topic_id, $this->levelState).'<hr />';

				$this->list_pid_fld = 'topic_id';
				$this->list_id_fld = 'post_id';
				$this->list_title_fld = 'subject';
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
					if ($this->levelState == 'post')
					{
						$url_parameter = $url_parameter.'&amp;'.$this->list_pid_fld.'='.$this->topic_id;
					}
//					$list4html .= $this->getListTitleLink($number,$id,$title,$url_parameter,true,false).'<br />';
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

//					if ($this->levelState == 'post')
//					{
//						$date = intval($data['post_time']);
//						$list4html .= '<br />&nbsp;('.$date = $this->utils->getDateLong($date).' '.$this->utils->getTimeLong($date).')';
//					}

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
// 最新記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getRecentList()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();

		if ($xoopsModuleConfig['show_recent_title'] == 0)
		{
			return false;
		}

		$this->setNextViewState('detail');
		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID());

		$sql = 'SELECT DISTINCT f.cat_id, p.forum_id, p.topic_id, p.post_id, p.subject, p.post_time, p.uid FROM '.$this->posts_table.' p LEFT JOIN '.$this->topics_table.' t ON p.topic_id=t.topic_id LEFT JOIN '.$this->forums_table.' f ON t.forum_id=f.forum_id LEFT JOIN '.$this->forum_access_table.' a ON f.forum_id=a.forum_id WHERE '.$this->item_criteria->render().' ORDER BY p.post_time '.$this->item_order_sort;

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
		$i = 0;
		while($data = $this->db->fetchArray($ret))
		{
			$this->topic_id = intval($data['topic_id']);
			$id = intval($data['post_id']);
			$cat_id = intval($data['cat_id']);
			$forum_id = intval($data['forum_id']);
			$topic_id = intval($data['topic_id']);
			$title = $myts->makeTboxData4Show($data['subject']);
			$url_parameter = $baseUrl.'&amp;cat_id='.$cat_id.'&amp;forum_id='.$forum_id.'&amp;topic_id='.$topic_id.'&amp;post_id='.$id;
			$date = intval($data['post_time']);
			$date = $this->utils->getDateLong($date).' '.$this->utils->getTimeLong($date);

			$recent_list[$i]['title'] = $this->adjustTitle($title);
			$recent_list[$i]['url'] = $url_parameter;
			$recent_list[$i]['date'] = $date;
			$i++;
		}
		return $recent_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// @return int $forum_access フォーラムアクセス権限 0:権限なし、1:閲覧許可、2:投稿許可、3:編集許可
	function checkForumAccess()
	{
		$uid = $this->sessionHandler->getUid();
		$user =& $this->sessionHandler->getUser();
		$forum_access_level = 0;
		$forum_type = 1;
		$is_mod = 0;
		$is_admin = 0;


		if ($this->forum_id)
		{
			$sql = 'SELECT forum_access,forum_type FROM '.$this->forums_table.' WHERE forum_id = '.$this->forum_id;
		}
		elseif ($this->topic_id)
		{
			$sql = 'SELECT f.forum_access,f.forum_type FROM '.$this->topics_table.' t LEFT JOIN '.$this->forums_table.' f ON t.forum_id=f.forum_id WHERE t.topic_id='.$this->topic_id;
		}
		else
		{
			return $this->forum_access;
		}


		// debug
		$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess sql', $sql);
		if (!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess sql error', $this->db->error());
		}


		while($data=$this->db->fetchArray($ret))
		{
			$forum_access_level = $data['forum_access'];
			$forum_type = $data['forum_type'];
		}

		if ($forum_type == 1) // private forum
		{
			if (is_object($user))
			{
				$criteria = new CriteriaCompo();
				$groupid_array = $this->utils->getGroupIdArray($user);
				foreach($groupid_array as $groupid)
				{
					$criteria->add(new Criteria('groupid', $groupid),'OR');
				}
				$sql3 = 'SELECT user_id, can_post FROM '.$this->forum_access_table.' WHERE forum_id = '.$this->forum_id.' AND user_id = '.$uid;
				if ($criteria->render() != '')
				{
					$sql3 .= ' OR '.$criteria->render();
				}
				if (!$ret3 = $this->db->query($sql3))
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess xhnewbb_forum_access sql', $sql3);
					$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess xhnewbb_forum_access sql error', $this->db->error());
				}
				$ret3_n = $this->db->getRowsNum($ret3);
				if ($ret3_n > 0)
				{
					list($user_id, $can_post) = $this->db->fetchRow($ret3);
					if ($can_post)
					{
						$this->forum_access = 2;
					}
					else
					{
						$this->forum_access = 1;
					}
				}
			}
		}
		else
		{
			if ($forum_access_level == 2)// 全ての訪問者に投稿許可
			{
				$this->forum_access = 2;
			}
			elseif ($forum_access_level == 1)// 登録ユーザのみ投稿許可
			{
				if ($uid)
				{
					$this->forum_access = 2;
				}
				else
				{
					$this->forum_access = 1;// ゲスト閲覧のみ許可
				}
			}
		}

		//moderator
		$sql2 = 'SELECT * FROM '.$this->forum_mods_table.' WHERE forum_id = '.$this->forum_id .' AND user_id = '.$uid;
		if (!$ret2 = $this->db->query($sql2))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess xhnewbb_forum_mods sql', $sql2);
			$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess xhnewbb_forum_mods sql error', $this->db->error());
		}
		$ret2_n = $this->db->getRowsNum($ret2);
		if ($ret2_n > 0)
		{
			$this->forum_access = 3;
		}

		//module admin
		if ($mod_admin = $this->getModuleAdmin())
		{
			$this->forum_access = 3;
		}

		// poster
		if ($uid)
		{
			$sql4 = 'SELECT post_id FROM '.$this->posts_table.' WHERE uid = '.$uid.' AND post_id = '.$this->post_id;
			if (!$ret4 = $this->db->query($sql4))
			{
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess xhnewbb_posts sql', $sql4);
				$this->utils->setDebugMessage(__CLASS__, 'checkForumAccess xhnewbb_posts sql error', $this->db->error());
			}
			$ret4_n = $this->db->getRowsNum($ret4);
			if ($ret4_n > 0)
			{
				$this->forum_access = 3;
			}
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'forum_access_level', $forum_access_level);
		$this->utils->setDebugMessage(__CLASS__, 'forum_access', $this->forum_access);
		$this->utils->setDebugMessage(__CLASS__, 'forum_type', $forum_type);

		return $this->forum_access;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getItemExtraArg()
	{
/*
		$this->cat_id = intval($this->utils->getGetPost('cat_id',0));
		$this->forum_id = intval($this->utils->getGetPost('forum_id',0));
		$this->topic_id = intval($this->utils->getGetPost('topic_id',0));
		$this->post_id = intval($this->utils->getGetPost('post_id',0));
*/
		// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
		$extra = '';
		if ($this->cat_id)
		{
			$extra .= '&cat_id='.$this->cat_id;
		}
		if ($this->forum_id)
		{
			$extra .= '&forum_id='.$this->forum_id;
		}
		if ($this->topic_id)
		{
			$extra .= '&topic_id='.$this->topic_id;
		}
		if ($this->post_id)
		{
//			$extra .= '&post_id='.$this->post_id;
//			$extra .= '&post_id='.$this->item_id;
		}

		$extra = preg_replace('/^\&/','',$extra);
		$item_extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'item_extra_arg', $item_extra_arg);
		return $item_extra_arg;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getEditLink($id=0)
	{
		global $module_handler;
		global $xoopsDB;
		$config_handler = &xoops_gethandler('config');

		if (defined('XOOPS_CUBE_LEGACY'))
		{
			// for XOOPS Cube Legacy 2.1
			$moduleConfig =& $config_handler->getConfigsByDirname('xhnewbb');
		}
		else {
			// for XOOPS 2.0
			$module_handler =& xoops_gethandler('module');
			$module =& $module_handler->getByDirname('xhnewbb');
			$moduleConfig =& $config_handler->getConfigsByCat(0,$module->getVar('mid'));
		}

		$sql = "SELECT post_time FROM ".$xoopsDB->prefix('xhnewbb_posts')." WHERE post_id='$id'";
		$this->utils->setDebugMessage(__CLASS__, 'getEditLink sql', $sql);
		$ret = $this->db->query($sql);
		if (!$ret)
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getEditLink db error', $this->db->error());
			return false;
		}
		while($data = $this->db->fetchArray($ret))
		{
			$date = intval($data[$this->item_date_fld]);
		}

		$this->checkForumAccess();
		if ($this->forum_access < 2)
		{
			return false;
		}
		else
		{
			$edit_link = '';

			if ($id != 0)
			{
				$reply_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
				$edit_link .= '<a href="'.$reply_url.'&amp;entry_type=reply_entry&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$this->forum_id.'&amp;topic_id='.$this->topic_id.'&amp;post_id='.$id.'">'._REPLY.'</a>&nbsp;';
				if ($this->getModuleAdmin())
				{
					$edit_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
					$delete_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
					$edit_link .= '<a href="'.$edit_url.'&amp;entry_type=edit_entry&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$this->forum_id.'&amp;topic_id='.$this->topic_id.'&amp;post_id='.$id.'">'._EDIT.'</a>&nbsp;';
					$edit_link .= '<a href="'.$delete_url.'&amp;entry_type=delete_entry&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$this->forum_id.'&amp;topic_id='.$this->topic_id.'&amp;post_id='.$id.'">'._DELETE.'</a>';
				}
				elseif ($this->forum_access >= 3)
				{
					$edit_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
					$delete_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
					if (!$moduleConfig['xhnewbb_selfdellimit'] && (date('U') - $date > $moduleConfig['xhnewbb_selfdellimit']))
						$edit_link .= '<a href="'.$edit_url.'&amp;entry_type=edit_entry&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$this->forum_id.'&amp;topic_id='.$this->topic_id.'&amp;post_id='.$id.'">'._EDIT.'</a>&nbsp;';
					if (!$moduleConfig['xhnewbb_selfeditlimit'] && (date('U') - $date > $moduleConfig['xhnewbb_selfeditlimit']))
						$edit_link .= '<a href="'.$delete_url.'&amp;entry_type=delete_entry&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$this->forum_id.'&amp;topic_id='.$this->topic_id.'&amp;post_id='.$id.'">'._DELETE.'</a>';
				}
				$edit_link .= '<hr />';
			}
			$add_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
			$edit_link .= '<a href="'.$add_url.'&amp;entry_type=new_entry&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$this->forum_id.'&amp;topic_id='.$this->topic_id.'">'._MD_XMOBILE_POSTNEW.'</a>&nbsp;';
			return $edit_link;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getNicePathFromId($sel_id, $level, $path='')
	{
		$myts =& MyTextSanitizer::getInstance();

		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'list',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId level', $level);

		switch ($level)
		{
			case 'category':
				return $path;
				break;

			case 'forum':
				$sql = 'SELECT cat_id, cat_title FROM '.$this->categories_table.' WHERE cat_id='.$sel_id;
				$previous_level = 'category';

				if (!$result = $this->db->query($sql))
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId forum sql', $sql);
					$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId forum db error', $this->db->error());
				}
				else
				{
					if ($this->db->getRowsNum($result) == 0)
					{
						return $path;
					}
					list($parentid,$name) = $this->db->fetchRow($result);
					$name = $myts->makeTboxData4Show($name);
					$baseUrl = $baseUrl.'&amp;level=forum&amp;cat_id='.$sel_id;
					$path = '<a href="'.$baseUrl.'">'.$name.'</a>'.$path;
				}
				break;

			case 'topic':
				$sql = 'SELECT cat_id, forum_name FROM '.$this->forums_table.' WHERE forum_id='.$sel_id;
				$previous_level = 'forum';
				if (!$result = $this->db->query($sql))
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId topic sql', $sql);
					$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId topic db error', $this->db->error());
				}
				else
				{
					if ($this->db->getRowsNum($result) == 0)
					{
						return $path;
					}
					list($parentid,$name) = $this->db->fetchRow($result);
					$name = $myts->makeTboxData4Show($name);
					$baseUrl = $baseUrl.'&amp;level=topic&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$sel_id;
					$path = '<br />-<a href="'.$baseUrl.'">'.$name.'</a>'.$path;
				}
				break;

			case 'post':
				$sql = 'SELECT forum_id, topic_title FROM '.$this->topics_table.' WHERE topic_id='.$sel_id;
				$previous_level = 'topic';

				if (!$result = $this->db->query($sql))
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId post sql', $sql);
					$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId post db error', $this->db->error());
				}
				else
				{
					if ($this->db->getRowsNum($result) == 0)
					{
						return $path;
					}
					list($parentid,$name) = $this->db->fetchRow($result);
					$name = $myts->makeTboxData4Show($name);
					$baseUrl = $baseUrl.'&amp;level=post&amp;cat_id='.$this->cat_id.'&amp;forum_id='.$this->forum_id.'&amp;topic_id='.$sel_id;
					$path = '<br />--<a href="'.$baseUrl.'">'.$name.'</a>';
				}
				break;
		}

		$path = $this->getNicePathFromId($parentid, $previous_level, $path);

		return $path;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemDetailPageNavi()
	{
		$sql = "SELECT DISTINCT p.post_id FROM ".$this->posts_table." p LEFT JOIN ".$this->topics_table." t ON p.topic_id=t.topic_id LEFT JOIN ".$this->forums_table." f ON t.forum_id=f.forum_id LEFT JOIN ".$this->forum_access_table." a ON f.forum_id=a.forum_id WHERE (".$this->item_criteria->render().") AND t.topic_id=".$this->topic_id." ORDER BY p.post_time ".$this->item_order_sort;
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi sql', $sql);
		if (!$result = $this->db->query($sql))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi db error', $this->db->error());
		}
		$total = $this->db->getRowsNum($result);

		if (!is_null($this->item_id))
		{
			$page = $this->getItemPageFromID($this->item_id);
			$_GET['start'] = $page;
		}
		$this->itemDetailPageNavi =& new XmobilePageNavigator($total, 1, 'start', $this->getItemExtraArg());
		$this->item_criteria->setLimit($this->itemDetailPageNavi->getPerpage());
		$this->item_criteria->setStart($this->itemDetailPageNavi->getStart());
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi Limit', $this->itemDetailPageNavi->getPerpage());
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi Start', $this->itemDetailPageNavi->getStart());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getItemPageFromID($id)
	{
		$sql = "SELECT DISTINCT p.post_id FROM ".$this->posts_table." p LEFT JOIN ".$this->topics_table." t ON p.topic_id=t.topic_id LEFT JOIN ".$this->forums_table." f ON t.forum_id=f.forum_id LEFT JOIN ".$this->forum_access_table." a ON f.forum_id=a.forum_id WHERE (".$this->item_criteria->render().") AND t.topic_id=".$this->topic_id." ORDER BY p.post_time ".$this->item_order_sort;
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemPageFromID sql', $sql);
		if (!$result = $this->db->query($sql))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getItemPageFromID db error', $this->db->error());
		}
		else
		{
			if ($this->db->getRowsNum($result) > 0)
			{
				$page = 0;
				while($data = $this->db->fetchArray($result))
				{
					if ($id == intval($data['post_id']))
					{
						// debug
						$this->utils->setDebugMessage(__CLASS__, 'getItemPageFromID page', $page);
						return $page;
					}
					$page++;
				}
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderEntryForm()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();
		$this->setItemCriteria();

		$entry_type = htmlspecialchars($this->utils->getGetPost('entry_type', ''), ENT_QUOTES);
		$this->ticket =& new XoopsGTicket;
		$this->checkForumAccess();

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getEdit entry_type', $entry_type);

		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
//		$baseUrl = preg_replace('/&amp;/i','&',$baseUrl);

		$entry_form = '';
		$entry_form .= '<form action="'.$baseUrl.'" method="post">';
		$entry_form .= '<div class="form">';
		$entry_form .= $this->ticket->getTicketHtml();
		$entry_form .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
		$entry_form .= '<input type="hidden" name="HTTP_REFERER" value="'.$baseUrl.'" />';


		switch ($entry_type)
		{
			case 'new_entry':

				if ($this->forum_access < 2)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$subject = '';
				$post_text = '';
				$entry_form .= '<input type="hidden" name="post_id" value="" />';
				break;

			case 'reply_entry':

				if ($this->forum_access < 2)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$subject = '';
				$post_text = '';
				$nohtml = 0;
				$nosmiley = 0;
				$sql = "SELECT p.subject, p.post_time, p.uid, p.nohtml, p.nosmiley , pt.post_text FROM ".$this->posts_table." p LEFT JOIN ".$this->posts_text_table." pt ON p.post_id=pt.post_id WHERE p.post_id = ".$this->post_id;
				$ret = $this->db->query($sql);
				if (!$ret)
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'getEdit db error', $this->db->error());
					return false;
				}
				while($data = $this->db->fetchArray($ret))
				{
					$rep_subject = $myts->makeTboxData4Show($data['subject']);
					$date = intval($data['post_time']);
					$post_time = $this->utils->getDateLong($date).' '.$this->utils->getTimeLong($date).'<br />';
					$uname = $this->utils->getUnameFromId($data['uid']);
					if ($data['nohtml'] == 0) $dohtml = 1; else $dohtml = 0;
					if ($data['nosmiley'] == 0) $dosmiley = 1; else $dosmiley = 0;
					$rep_post_text = $myts->makeTareaData4Show($data['post_text'],$dohtml,$dosmiley,1);
				}
				$entry_form .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
				$entry_form .= _MD_XMOBILE_TITLE.$rep_subject.'<br />';
				$entry_form .= _MD_XMOBILE_CONTRIBUTOR.'&nbsp;'.$uname.'<br />';
				$entry_form .= _MD_XMOBILE_DATE.'&nbsp;'.$post_time.'<br />';
				if ($rep_post_text !== '')
				{
					$entry_form .= _MD_XMOBILE_CONTENTS.'<br />';
					$entry_form .= $rep_post_text;
				}
				$entry_form .= '<hr />'._MD_XMOBILE_PRPLY_THIS.'<br />';
				$entry_form .= '<input type="hidden" name="pid" value="'.$this->post_id.'" />';
				$entry_form .= '<input type="hidden" name="post_id" value="" />';
				if (!preg_match('/^Re:/i', $rep_subject))
				{
					$subject = 'Re:'.$rep_subject;
				}
				else
				{
					$subject = $rep_subject;
				}

				break;

			case 'edit_entry':

				if ($this->forum_access < 3)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$sql = "SELECT p.subject, p.uid, p.nosmiley , pt.post_text FROM ".$this->posts_table." p LEFT JOIN ".$this->posts_text_table." pt ON p.post_id=pt.post_id WHERE p.post_id = ".$this->post_id;

				$ret = $this->db->query($sql);
				if (!$ret)
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'getEdit db error', $this->db->error());
					return false;
				}
				while($data = $this->db->fetchArray($ret))
				{
					$subject = $myts->makeTboxData4Edit($data['subject']);
					$uid = intval($data['uid']);
//					$nohtml = intval($data['nohtml']);
					$nosmiley = intval($data['nosmiley']);
					$post_text = $myts->makeTareaData4Edit($data['post_text']);
				}
				$entry_form .= '<input type="hidden" name="post_id" value="'.$this->post_id.'" />';
				$entry_form .= '<input type="hidden" name="uid" value="'.$uid.'" />';
//				$entry_form .= '<input type="hidden" name="nohtml" value="'.$nohtml.'" />';
				$entry_form .= '<input type="hidden" name="nosmiley" value="'.$nosmiley.'" />';
				break;

			case 'delete_entry':

				if ($this->forum_access < 3)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}

				$subject = '';
				$post_text = '';
				$nohtml = 0;
				$nosmiley = 0;
				$child_count = 0;

				$sql_has_child = 'SELECT post_id FROM '.$this->posts_table.' WHERE pid = '.$this->post_id;
				$ret_has_child = $this->db->query($sql_has_child);
				$child_count = $this->db->getRowsNum($ret_has_child);

				$sql = 'SELECT subject, post_time, uid, nohtml, nosmiley FROM '.$this->posts_table.' WHERE post_id = '.$this->post_id;
				$ret = $this->db->query($sql);
				if (!$ret)
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'getEdit db error', $this->db->error());
					return false;
				}
				while($data = $this->db->fetchArray($ret))
				{
					$rep_subject = $myts->makeTboxData4Show($data['subject']);
					$date = intval($data['post_time']);
					$post_time = $this->utils->getDateLong($date).' '.$this->utils->getTimeLong($date);
					$uname = $this->utils->getUnameFromId($data['uid']);
					if ($data['nohtml'] == 0) $dohtml = 1; else $dohtml = 0;
					if ($data['nosmiley'] == 0) $dosmiley = 1; else $dosmiley = 0;
				}
				$sql2 = 'SELECT post_text FROM '.$this->posts_text_table.' WHERE post_id = '.$this->post_id;
				$ret2 = $this->db->query($sql2);
				if (!$ret2)
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'getEdit db error', $this->db->error());
					return false;
				}
				while($data2 = $this->db->fetchArray($ret2))
				{
					$rep_post_text = $myts->makeTareaData4Show($data2['post_text'],$dohtml,$dosmiley,1);
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
				$entry_form .= '<input type="hidden" name="forum_id" value="'.$this->forum_id.'" />';
				$entry_form .= '<input type="hidden" name="topic_id" value="'.$this->topic_id.'" />';
				$entry_form .= '<input type="hidden" name="post_id" value="'.$this->post_id.'" />';
				$entry_form .= '<input type="hidden" name="entry_type" value="delete_entry" />';
				$entry_form .= '<input type="submit" name="submit" value="'._DELETE.'" />&nbsp;';
				$entry_form .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
				$entry_form .= '</div>';
				$entry_form .= '</form>';

				return $entry_form;

				break;
		}

		if ($entry_type == 'new_entry' || $entry_type == 'reply_entry')
		{
			$entry_form .= _MD_XMOBILE_CONTRIBUTOR.'<br />';
			$poster_uid = $this->sessionHandler->getUid();
			$member_handler =& xoops_gethandler('member');
			$poster =& $member_handler->getUser($poster_uid);
			if (is_object($poster))
			{
				$entry_form .= $poster->getVar('uname').'<br />';
			}
			else
			{
				$entry_form .= '<input type="text" name="poster_name" value="" /><br />';
			}
		}
		$entry_form .= _MD_XMOBILE_TITLE.'<br />';
		$entry_form .= '<input type="text" name="subject" value="'.$subject.'" /><br />';
		$entry_form .= _MD_XMOBILE_MESSAGE.'<br />';
		$entry_form .= '<textarea rows="'.$xoopsModuleConfig['tarea_rows'].'" cols="'.$xoopsModuleConfig['tarea_cols'].'" name="post_text">'.$post_text.'</textarea><br />';
		$entry_form .= '<input type="hidden" name="cat_id" value="'.$this->cat_id.'" />';
		$entry_form .= '<input type="hidden" name="forum_id" value="'.$this->forum_id.'" />';
		$entry_form .= '<input type="hidden" name="topic_id" value="'.$this->topic_id.'" />';
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
			if ($this->topic_id != 0)
			{
				$baseUrl .= '&view=detail';
				$baseUrl .= '&start='.$this->start;
				$baseUrl .= '&topic_id='.$this->topic_id;
			}
			elseif ($this->forum_id != 0)
			{
				$baseUrl .= '&view=list&level=topic';
				$baseUrl .= '&forum_id='.$this->forum_id;
			}
			header('Location: '.$baseUrl);
			exit();
		}

		$this->checkForumAccess();


		$entry_type = htmlspecialchars($this->utils->getGetPost('entry_type', ''), ENT_QUOTES);
		$this->ticket =& new XoopsGTicket;

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView entry_type', $entry_type);

		//チケットの確認
//		if (!$ticket_check = $this->ticket->check())
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
//			return $this->ticket->getErrors();
			return _MD_XMOBILE_TICKET_ERROR;
		}

		$allow_html = 0;
		$new_id = 0;

		$sql = 'SELECT allow_html FROM '.$this->forums_table.' WHERE forum_id = '.$this->forum_id;
		$ret = $this->db->query($sql);
		if (!$ret)
		{
			$this->utils->setDebugMessage(__CLASS__, 'getConfirmView db error', $this->db->error());
			return false;
		}
		else
		{
			$row = $this->db->fetchRow($ret);
			$allow_html = intval($row[0]);
			if ($allow_html == 0) $nohtml = 1; else $nohtml = 0;
		}

		$pid = intval($this->utils->getPost('pid', 0));
		$post_time = time();
		$uid = $this->sessionHandler->getUid();
		$poster_ip = $myts->makeTboxData4Save($_SERVER['REMOTE_ADDR']);
		$subject = $myts->makeTboxData4Save($this->utils->getPost('subject', ''));
		$nosmiley = intval($this->utils->getPost('nosmiley', 0));
		$icon = $myts->makeTboxData4Save($this->utils->getPost('icon', ''));
		$attachsig = intval($this->utils->getPost('attachsig', 0));
		$post_text = $myts->makeTareaData4Save($this->utils->getPost('post_text', ''));
		$poster_name = $myts->makeTareaData4Save($this->utils->getPost('poster_name', ''));
		if ($poster_name != '')
		{
			$post_text = sprintf(_MD_XHNEWBB_FMT_GUESTSPOSTHEADER,$poster_name).$post_text;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView sql', $sql);
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView allow_html', $allow_html);
		$this->utils->setDebugMessage(__CLASS__, 'getConfirmView nohtml', $nohtml);


		if ($entry_type != 'delete_entry' && $subject == '' || $post_text == '')
		{
			$body = _MD_XMOBILE_NEED_DATA;
			$body .= $this->getEditView();
			return $body;
		}

		switch ($entry_type)
		{
			case 'new_entry':

				if ($this->forum_access < 2)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}

				$sql_insert_topics = "INSERT INTO ".$this->topics_table." (topic_title,topic_poster,topic_time,topic_views,topic_replies,topic_last_post_id,forum_id) VALUES ('$subject',$uid,$post_time,0,0,0,".$this->forum_id.")";
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_topics', $sql_insert_topics);
				if (!$ret_insert_topics = $this->db->query($sql_insert_topics))
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_topics error', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED;
				}
				$new_topic_id = $this->db->getInsertId();

				$sql_insert_posts = "INSERT INTO ".$this->posts_table." (pid,topic_id,forum_id,post_time,uid,poster_ip,subject,nohtml,nosmiley,icon,attachsig) VALUES($pid,$new_topic_id,$this->forum_id,$post_time,$uid,'$poster_ip','$subject',$nohtml,$nosmiley,'$icon',$attachsig)";
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts', $sql_insert_posts);
				if (!$ret_insert_posts = $this->db->query($sql_insert_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts error', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED;
				}
				$new_id = $this->db->getInsertId();

				$sql_insert_posts_text = "INSERT INTO ".$this->posts_text_table." (post_id,post_text) VALUES($new_id,'$post_text')";
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts_text', $sql_insert_posts_text);
				if (!$ret_insert_posts_text = $this->db->query($sql_insert_posts_text))
				{
					$sql_delete_topics = "DELETE FROM ".$this->topics_table." WHERE topic_id = $new_topic_id";
					$this->db->query($sql_delete_topics);

					$sql_delete_posts = "DELETE FROM ".$this->posts_table." WHERE post_id = $new_id";
					$this->db->query($sql_delete_posts);
					$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts error', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED;
				}

				$sql_update_forums = "UPDATE ".$this->forums_table." SET forum_topics = forum_topics+1, forum_posts = forum_posts+1, forum_last_post_id = $new_id WHERE forum_id = $this->forum_id";
				$this->utils->setDebugMessage(__CLASS__, 'update_forums', $sql_update_forums);
				if (!$ret_update_forums = $this->db->query($sql_update_forums))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_update_forums error', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED;
				}

				$sql_update_topics= "UPDATE ".$this->topics_table." SET topic_last_post_id = $new_id WHERE topic_id = $new_topic_id";
				$this->utils->setDebugMessage(__CLASS__, 'sql_update_topics', $sql_update_topics);
				if (!$ret_update_topics = $this->db->query($sql_update_topics))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_update_topics error', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED;
				}

				$sql_incremant_posts = sprintf("UPDATE %s SET posts=posts+1 WHERE uid = %u", $this->db->prefix("users"), $uid);
				$this->utils->setDebugMessage(__CLASS__, 'sql_incremant_posts', $sql_incremant_posts);
				if (!$ret_incremant_posts = $this->db->query($sql_incremant_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_incremant_posts error', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED;
				}

				$this->utils->setDebugMessage(__CLASS__, 'insert new_entry', 'Success ');

				return _MD_XMOBILE_INSERT_SUCCESS;

				break;

			case 'reply_entry':

				if ($this->forum_access < 2)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}

				$sql_insert_posts = "INSERT INTO ".$this->posts_table." (pid,topic_id,forum_id,post_time,uid,poster_ip,subject,nohtml,nosmiley,icon,attachsig) VALUES($pid,$this->topic_id,$this->forum_id,$post_time,$uid,'$poster_ip','$subject',$nohtml,$nosmiley,'$icon',$attachsig)";
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts', $sql_insert_posts);
				if (!$ret_insert_posts = $this->db->query($sql_insert_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts error', $this->db->error());
					return false;
				}
				$new_id = $this->db->getInsertId();

				$sql_insert_posts_text = "INSERT INTO ".$this->posts_text_table." (post_id,post_text) VALUES($new_id,'$post_text')";
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts_text', $sql_insert_posts_text);
				if (!$ret_insert_posts_text = $this->db->query($sql_insert_posts_text))
				{
					$sql_delete_posts = "DELETE FROM ".$this->posts_table." WHERE post_id = $this->post_id";
					$this->db->query($sql_delete_posts);
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts_text error', $this->db->error());
					return false;
				}
// thanks elric 
// トピックの投稿者は変更の必要なし
//				$sql_update_topics = "UPDATE ".$this->topics_table." SET topic_poster=".$uid.", topic_time=".$post_time.", topic_replies=topic_replies+1, topic_last_post_id=".$new_id." WHERE topic_id=".$this->topic_id;
				$sql_update_topics = "UPDATE ".$this->topics_table." SET topic_time=".$post_time.", topic_replies=topic_replies+1, topic_last_post_id=".$new_id." WHERE topic_id=".$this->topic_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_update_topics', $sql_update_topics);
				if (!$ret_update_topics = $this->db->query($sql_update_topics))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_update_topics error', $this->db->error());
					return false;
				}

				$sql_update_forums = "UPDATE ".$this->forums_table." SET forum_posts = forum_posts+1, forum_last_post_id = $new_id WHERE forum_id = $this->forum_id";
				$this->utils->setDebugMessage(__CLASS__, 'sql_update_forums', $sql_update_forums);
				if (!$ret_update_forums = $this->db->query($sql_update_forums))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_update_forums error', $this->db->error());
					return false;
				}


				$sql_incremant_posts = sprintf("UPDATE %s SET posts=posts+1 WHERE uid = %u", $this->db->prefix("users"), $uid);
				$this->utils->setDebugMessage(__CLASS__, 'sql_incremant_posts', $sql_incremant_posts);
				if (!$ret_incremant_posts = $this->db->query($sql_incremant_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_incremant_posts error', $this->db->error());
					return false;
				}

				$this->utils->setDebugMessage(__CLASS__, 'reply_entry', 'Success');

				return _MD_XMOBILE_INSERT_SUCCESS;

				break;

			case 'edit_entry':

				if ($this->forum_access < 3)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}

				$sql_insert_posts = "UPDATE ".$this->posts_table." SET pid=$pid, topic_id=".$this->topic_id.", forum_id=".$this->forum_id.",post_time=$post_time,poster_ip='$poster_ip',subject='$subject',nohtml=$nohtml,nosmiley=$nosmiley,icon='$icon',attachsig=$attachsig WHERE post_id=".$this->post_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts', $sql_insert_posts);
				if (!$ret_insert_posts = $this->db->query($sql_insert_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts error', $this->db->error());
					return _MD_XMOBILE_UPDATE_FAILED;
				}

				$sql_insert_posts_text = "UPDATE ".$this->posts_text_table." SET post_id=".$this->post_id.",post_text='$post_text' WHERE post_id=".$this->post_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts_text', $sql_insert_posts_text);
				if (!$ret_insert_posts_text = $this->db->query($sql_insert_posts_text))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_posts_text error', $this->db->error());
					return _MD_XMOBILE_UPDATE_FAILED;
				}

				$sql_insert_topics = "UPDATE ".$this->topics_table." SET topic_time=$post_time WHERE topic_id=".$this->topic_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_insert_topics', $sql_insert_topics);
				if (!$ret_insert_topics = $this->db->query($sql_insert_topics))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_insert_topics error', $this->db->error());
					return _MD_XMOBILE_UPDATE_FAILED;
				}

				$this->utils->setDebugMessage(__CLASS__, 'edit_entry', 'Success');

				return _MD_XMOBILE_UPDATE_SUCCESS;

				break;

			case 'delete_entry':

				if ($this->forum_access < 3)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}

				$poster_uid = 0;
				$sql_posts = "SELECT uid FROM ".$this->posts_table." WHERE post_id = ".$this->post_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_posts', $sql_posts);
				if (!$ret_posts = $this->db->query($sql_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_posts error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}
				while($row = $this->db->fetchArray($ret_posts))
				{
					$poster_uid = intval($row['uid']);
				}

				$istopic = 0;
				$pid = 0;
				$sql_istopic = 'SELECT pid FROM '.$this->posts_table.' WHERE post_id = '.$this->post_id;
				if (!$ret_istopic = $this->db->query($sql_istopic))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_istopic error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}
				while($row = $this->db->fetchArray($ret_istopic))
				{
					$pid = intval($row['pid']);
				}
				if ($pid == 0)
				{
					$istopic = true;
				}

				$sql_delete_posts = "DELETE FROM ".$this->posts_table." WHERE post_id = ".$this->post_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts', $sql_delete_posts);
				if (!$ret_delete_posts = $this->db->query($sql_delete_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}
				$sql_delete_posts_text = "DELETE FROM ".$this->posts_text_table." WHERE post_id = ".$this->post_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts_text', $sql_delete_posts_text);
				if (!$ret_delete_posts_text = $this->db->query($sql_delete_posts_text))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts_text error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}

				$sql_decrement_posts = sprintf("UPDATE %s SET posts=posts-1 WHERE uid = %u", $this->db->prefix('users'), $poster_uid);
				$this->utils->setDebugMessage(__CLASS__, 'sql_decrement_posts', $sql_decrement_posts);
				if (!$ret_decrement_posts = $this->db->query($sql_decrement_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_decrement_posts error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}

				include_once XOOPS_ROOT_PATH.'/class/xoopstree.php';
				$child_count = 0;
				$mytree = new XoopsTree($this->posts_table, 'post_id', 'pid');
				$child_arr = $mytree->getAllChild($this->post_id);
				$child_count = count($child_arr);
				$post_count = $child_count+1;
				if ($child_count > 0)
				{
					foreach($child_arr as $child)
					{
						$sql_delete_posts_child = "DELETE FROM ".$this->posts_table." WHERE pid=".$child['post_id'];
						$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts_child', $sql_delete_posts_child);
						if (!$ret_delete_posts_child = $this->db->query($sql_delete_posts_child))
						{
							$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts_child error', $this->db->error());
							return _MD_XMOBILE_DELETE_FAILED;
						}

						$sql_delete_posts_text_child = "DELETE FROM ".$this->posts_text_table." WHERE post_id=".$child['post_id'];
						$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts_text_child', $sql_delete_posts_text_child);
						if (!$ret_delete_posts_text_child = $this->db->query($sql_delete_posts_text_child))
						{
							$this->utils->setDebugMessage(__CLASS__, 'sql_delete_posts_text_child error', $this->db->error());
							return _MD_XMOBILE_DELETE_FAILED;
						}

						$sql_decrement_posts_child = sprintf("UPDATE %s SET posts=posts-1 WHERE uid = %u", $this->db->prefix('users'), $child['uid']);
						$this->utils->setDebugMessage(__CLASS__, 'sql_decrement_posts_child', $sql_decrement_posts_child);
						if (!$ret_decrement_posts_child = $this->db->query($sql_decrement_posts_child))
						{
							$this->utils->setDebugMessage(__CLASS__, 'sql_decrement_posts_child error', $this->db->error());
							return _MD_XMOBILE_DELETE_FAILED;
						}
					}
				}

				if ($istopic)
				{
					$sql_delete_topics = "DELETE FROM ".$this->topics_table." WHERE topic_id=".$this->topic_id;
					$this->utils->setDebugMessage(__CLASS__, 'sql_delete_topics', $sql_delete_topics);
					if (!$ret_delete_topics = $this->db->query($sql_delete_topics))
					{
						$this->utils->setDebugMessage(__CLASS__, 'sql_delete_topics error', $this->db->error());
						return _MD_XMOBILE_DELETE_FAILED;
					}

					$sql_delete_users2topics = "DELETE FROM ".$this->users2topics_table." WHERE topic_id=".$this->topic_id;
					$this->utils->setDebugMessage(__CLASS__, 'sql_delete_users2topics', $sql_delete_users2topics);
					if (!$ret_delete_users2topics = $this->db->query($sql_delete_users2topics))
					{
						$this->utils->setDebugMessage(__CLASS__, 'sql_delete_users2topics error', $this->db->error());
						return _MD_XMOBILE_DELETE_FAILED;
					}

					$sql_update_forum_topics = "UPDATE ".$this->forums_table." SET forum_topics=forum_topics-1 WHERE forum_id=".$this->forum_id;
					$this->utils->setDebugMessage(__CLASS__, 'sql_update_forum_topics', $sql_update_forum_topics);
					if (!$ret_update_forum_topics = $this->db->query($sql_update_forum_topics))
					{
						$this->utils->setDebugMessage(__CLASS__, 'sql_update_forum_topics error', $this->db->error());
						return _MD_XMOBILE_DELETE_FAILED;
					}
				}
				else
				{
					$topic_last_post_id = 0;
					$topic_time = 0;
					$sql_topic_last_post_id = "SELECT post_id,post_time FROM ".$this->posts_table." WHERE topic_id=".$this->topic_id." ORDER BY post_time ".$this->item_order_sort;
					if (!$ret_topic_last_post_id = $this->db->query($sql_topic_last_post_id,1,0))
					{
						$this->utils->setDebugMessage(__CLASS__, 'sql_topic_last_post_id error', $this->db->error());
						return _MD_XMOBILE_DELETE_FAILED;
					}
					while($row = $this->db->fetchArray($ret_topic_last_post_id))
					{
						$topic_last_post_id = intval($row['post_id']);
						$topic_time = intval($row['post_time']);
					}

					$sql_update_topics = "UPDATE ".$this->topics_table." SET topic_time=".$topic_time.", topic_replies=topic_replies-".$post_count.", topic_last_post_id=".$topic_last_post_id." WHERE topic_id=".$this->topic_id;
					$this->utils->setDebugMessage(__CLASS__, 'sql_update_topics', $sql_update_topics);
					if (!$ret_update_topics = $this->db->query($sql_update_topics))
					{
						$this->utils->setDebugMessage(__CLASS__, 'sql_update_topics error', $this->db->error());
						return _MD_XMOBILE_DELETE_FAILED;
					}
				}

				$forum_last_post_id = 0;
				$sql_forum_last_post_id = "SELECT post_id FROM ".$this->posts_table." WHERE forum_id=".$this->forum_id." ORDER BY post_time ".$this->item_order_sort;
				if (!$ret_forum_last_post_id = $this->db->query($sql_forum_last_post_id,1,0))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_forum_last_post_id error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}
				while($row = $this->db->fetchArray($ret_forum_last_post_id))
				{
					$forum_last_post_id = intval($row['post_id']);
				}

				$sql_update_forum_posts = "UPDATE ".$this->forums_table." SET forum_posts=forum_posts-".$post_count.", forum_last_post_id=".$forum_last_post_id." WHERE forum_id=".$this->forum_id;
				$this->utils->setDebugMessage(__CLASS__, 'sql_update_forum_posts', $sql_update_forum_posts);
				if (!$ret_update_forum_posts = $this->db->query($sql_update_forum_posts))
				{
					$this->utils->setDebugMessage(__CLASS__, 'sql_update_forum_posts error', $this->db->error());
					return _MD_XMOBILE_DELETE_FAILED;
				}

				$this->utils->setDebugMessage(__CLASS__, 'delete_entry', 'Success');

				return _MD_XMOBILE_DELETE_SUCCESS;

				break;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
