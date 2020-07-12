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
class Xmobile'.$Pluginname.'Plugin extends XmobileInukshukgtdPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}
class Xmobile'.$Pluginname.'PluginHandler extends XmobileInukshukgtdPluginHandlerAbstract
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
class XmobileInukshukgtdPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('msg_id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('msg_image', XOBJ_DTYPE_TXTBOX, '', true, 100);
		$this->initVar('subject', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('from_userid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('to_userid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('msg_time', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('msg_text', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('gtd_done', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('gtd_attr', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('remind_date', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('start_date', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('parent_mid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('location', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('groupid', XOBJ_DTYPE_TXTBOX, '', true, 255);
		
		// define primary key
		$this->setKeyFields(array('msg_id'));
		$this->setAutoIncrementField('msgid');
	}
	function assignSanitizerElement()
	{
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,1);
		$this->initVar('doxcode',XOBJ_DTYPE_INT,1);
	}
	function initNewFormElements()
	{
		$this->_formCaption = _MD_XMOBILE_POSTNEW;
		$this->assignFormElement('msg_id', array('type'=>'hidden', 'caption'=>'msg_id'));
		$this->assignFormElement('title', array('type'=>'text', 'caption'=>_MD_XMOBILE_TITLE, 'params'=>array('size'=>20, 'maxlength'=>40)));
		$this->assignFormElement('contents', array('type'=>'textarea', 'caption'=>_MD_XMOBILE_CONTENTS, 'params'=>'contents'));
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileInukshukgtdPluginHandlerAbstract extends XmobilePluginHandler
{
	var $template = 'xmobile_inukshukGTD.html';
	var $moduleDir = 'InukshukGTD';
	var $itemTableName = 'inukshuk_gtd';
	var $category_pid_fld ='gtd_attr';
	var $category_pid = null;
	var $action_id_fld ='action';
	var $item_id_fld = 'msg_id';
	var $item_title_fld = 'subject';
	var $item_description_fld = 'msg_text';
	var $item_order_fld = 'start_date';
	var $item_date_fld = 'start_date';
	var $item_uid_fld = 'to_userid';
	var $item_id = null;

	var $item_order_sort = 'DESC';
	var $gtdAttr = null;		// GTD分類
	var $action = null;			// GTD分類別アクション
	var $parent_mid = 0;		// プロジェクトの親ID
	var $actionWhere = null;
	var $gtdItemList = null;	// GTDグループ格納配列
	var $delegateListSince = 30; // Delegate trace to n days
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		global $xoopsConfig;
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;
		if ( preg_match("/^\D+(\d*)$/", $mydirname,$matches) ){
			$number = $matches[1];
			$this->itemTableName = 'inukshuk_gtd';
		}
		$mydirname = strtolower(basename(__FILE__,'.php'));
		$fileName = XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/config.php';
		if ( file_exists($fileName) ){
			include_once XOOPS_ROOT_PATH.'/modules/'.$mydirname.'/language/'.$xoopsConfig['language'].'/main.php';
			include_once $fileName;
		}
		$this->gtdItemList = $gtdItemList;

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		// $_GET, $POST のパラメータより値を取得し変数へセット
		$this->msg_id = intval($this->utils->getGetPost('msg_id',0));
		$this->gtdAttr = intval($this->utils->getGetPost('gtdAttr',0));
		$this->action = htmlspecialchars($this->utils->getGetPost('action', ''), ENT_QUOTES);
		$this->parent_mid = intval($this->utils->getGetPost('parent_mid',0));

		// action パラメータによるWHERE条件をセット
		//$this->actionWhere = $this->gtdItemList[$this->action ]['wstr'];
		//$this->category_pid = $this->gtdItemList[$this->action ]['wstr'];
		
		$this->item_criteria =& new CriteriaCompo();
		$item_criteria = new CriteriaCompo();
		
		$xoopsUser =& $this->sessionHandler->getUser();
		$item_criteria->add(new Criteria('start_date', 0, '>'));
		switch($this->action){
			case 'doit':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_attr', 4, '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '<'));
				break;
			case 'next':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_attr', 7, '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '<'));
				break;
			case 'calender':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_attr', 6, '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '<'));
				break;
			case 'delegate':
				$item_criteria->add(new Criteria('from_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_attr', 5, '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '<'));
				break;
			case 'project':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_attr', 3, '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '<'));
				if($this->parent_mid) 
					$item_criteria->add(new Criteria('parent_mid', $this->parent_mid, '='));
				break;
			case 'reference':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_attr', 1, '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '<'));
				break;
			case 'someday':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_attr', 2, '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '<'));
				break;
			case 'donelist':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_done', 5, '='));
				break;
			case 'trashbox':
				$item_criteria->add(new Criteria('to_userid', $xoopsUser->uid(), '='));
				$item_criteria->add(new Criteria('gtd_done', 9, '='));
				break;
		}
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
		// 分類別メニュー
		$this->controller->render->template->assign('cat_list',$this->getCatList());
		// 直近GTDをリストアップ
		$this->controller->render->template->assign('recent_item_list',$this->getRecentList());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 一覧画面
	function getListView()
	{
		global $xoopsModuleConfig;
		$this->setNextViewState('list');
		$this->setItemCriteria();
		$this->setBaseUrl();
		$limit = 1;
		$this->total = $this->getCount($this->item_criteria);
		$extraArg = $this->getItemExtraArg() . "action=".$this->action . "&amp;";
		$itemListPageNavi =& new XmobilePageNavigator($this->total, $xoopsModuleConfig['max_title_row'], 'start', $extraArg);

		// 分類別メニュー
		$this->controller->render->template->assign('cat_list',$this->getCatList());
		// 現在の条件で一覧表示
		$this->controller->render->template->assign('item_list',$this->getItemList());
		// ページ切り替えのコントロールを表示
		$this->controller->render->template->assign('item_list_page_navi',$itemListPageNavi->renderNavi());
	}	
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 詳細画面
	function getDetailView()
	{
		$this->setItemCriteria();
		if ( strcmp($this->action,'readpmsg')==0 ){
			$xoopsUser =& $this->sessionHandler->getUser();
			// for PM
			$pm_handler =& xoops_gethandler('privmessage');
			$pm =& $pm_handler->get( $this->msg_id );
			if (!is_object($pm) || $pm->getVar('from_userid') != $xoopsUser->getVar('uid') ) {
				exit();
			}
			$detail4html = '';
			$detail4html .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
			$detail4html .= _MD_XMOBILE_TITLE.$pm->getVar('subject').'<br />';
	    	$pm_uname = XoopsUser::getUnameFromId($pm->getVar("to_userid"));
			$detail4html .= _MD_XMOBILE_PM_TO.$pm_uname.'<br />';
			$detail4html .= _MD_XMOBILE_DATE. formatTimestamp($pm->getVar("msg_time")) .'<br />';
			$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
			$detail4html .= $pm->getVar("msg_text").'<br />';
			$this->controller->render->template->assign('item_detail',$detail4html);
			$this->controller->render->template->assign('item_detail_page_navi','');
		}else{
			parent::getDetailView();
			$this->controller->render->template->assign('item_detail_page_navi','');	// 省略
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getItemList()
	{
		$i = 0;
		$item_list = array();

		$this->setNextViewState('detail');
		$this->setBaseUrl();
		//
		// for PM
		//
		if (strcmp($this->action,'delegate')==0){
			$xoopsUser =& $this->sessionHandler->getUser();
			$pm_handler =& xoops_gethandler('privmessage');
			$criteria =new CriteriaCompo( new Criteria('from_userid', $xoopsUser->getVar('uid')));
			$criteria->add( new Criteria('to_userid', $xoopsUser->getVar('uid'),"!=") );
			$criteria->add( new Criteria('msg_time', time() - ($this->delegateListSince * 86400),">" ));
			$pm_arr =& $pm_handler->getObjects($criteria);
			$total_messages = count($pm_arr);
		    for ($i = 0; $i < $total_messages; $i++) {
		    	$pm_uname = XoopsUser::getUnameFromId($pm_arr[$i]->getVar("to_userid"));
				$item_list[$i]['key'] = $i + 1;
		    	$item_list[$i]['title'] = $pm_arr[$i]->getVar("subject"). " (".$pm_uname.")";
				$item_list[$i]['date'] =  formatTimestamp($pm_arr[$i]->getVar("msg_time"));
				$item_list[$i]['url'] = $this->getBaseUrl().'&amp;action=readpmsg&amp;msg_id='.$pm_arr[$i]->getVar("msg_id");
			}
			// PM経由で頼んだ事のGTDトレース
			$where_delegate = "(to_userid!=".$xoopsUser->uid() . " AND from_userid=".$xoopsUser->uid() ." AND msg_time>".$this->delegateListSince.")";
			$sql = "SELECT * FROM ".$this->itemTableName."  WHERE ". $where_delegate . " ORDER BY start_date,msg_id;";
			$ret = $this->db->query($sql);
			while($itemObject = $this->db->fetchArray($ret)){
		    	$gtd_uname = XoopsUser::getUnameFromId($itemObject["to_userid"]);
				$i++;
				$item_list[$i]['key'] = $i;
		    	$item_list[$i]['title'] = $itemObject["subject"]. " (".$gtd_uname.")";
				$item_list[$i]['date'] =  formatTimestamp($itemObject["msg_time"]);
				$item_list[$i]['url'] = $this->getBaseUrl() . '&amp;msg_id=' . $itemObject["msg_id"];
				$item_list[$i]['gtd_done'] = $itemObject["gtd_done"];
				$item_list[$i]['msg_image'] = $itemObject["msg_image"];
			}
		}
		// Normal GTD
		$sql = "SELECT * FROM ".$this->itemTableName."  WHERE ". $this->item_criteria->render() 
			. " ORDER BY " . $this->item_order_fld ." ". $this->item_order_sort;
		$ret = $this->db->query($sql);
		while($itemObject = $this->db->fetchArray($ret)){
			$i++;
			$item_list[$i]['key'] = $i;
	    	$item_list[$i]['title'] = $itemObject["subject"];
			$item_list[$i]['date'] =  formatTimestamp($itemObject["msg_time"]);
			$item_list[$i]['url'] = $this->getBaseUrl() . '&amp;msg_id=' . $itemObject["msg_id"];
			$item_list[$i]['gtd_done'] = $itemObject["gtd_done"];
			$item_list[$i]['msg_image'] = $itemObject["msg_image"];
		}
		if($i==0 and ($item_list == false || count($item_list) == 0)) // 表示するデータ無し
		{
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}
		return $item_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// GTD分類一覧の取得
// ただし、戻り値はオブジェクトではなく文字列
	function getCatList()
	{
		global $xoopsModuleConfig;
		$xoopsUser =& $this->sessionHandler->getUser();

		if ($this->action){
			// カテゴリのパンくずを表示
			$this->controller->render->template->assign('cat_path', $this->gtdItemList[$this->action]['desc']);
			if (strcmp($this->action,'project')!=0) return false;
		}
		$this->setNextViewState('list');
		$this->setBaseUrl();
		$this->setItemParameter();
		$this->setItemListPageNavi();
		$i = 0;
		$cat_list = array();
		if ( strcmp($this->action,'project')==0 ){
			// 親プロジェクト専用
			$sql = 'SELECT * FROM '.$this->itemTableName;
			$sql .= $this->parent_mid ? ' WHERE msg_id=' . $this->parent_mid : ' WHERE parent_mid=0 AND ' . $this->item_criteria->render();
			$ret = $this->db->query($sql);
			while($itemObject = $this->db->fetchArray($ret)){
				$url_parameter = $this->getBaseUrl().'&amp;action='.$this->action .'&amp;parent_mid='.$itemObject['msg_id'];
				$cat_list[$i]['key'] = $i+1;	//$itemObject['msg_id'];
				$cat_list[$i]['title'] = $this->adjustTitle( $itemObject['subject'] );
				$cat_list[$i]['url'] = $url_parameter;
				if ($xoopsModuleConfig['show_item_count']){
					// 子プロジェクトのカウント
					$sql = 'SELECT count(*) FROM '.$this->itemTableName.' WHERE parent_mid='.$itemObject['msg_id'];
					$ret = $this->db->query($sql);
					if (!$ret)
					{
						$this->utils->setDebugMessage(__CLASS__, 'getRecentList db error', $this->db->error());
						return false;
					}
					list($item_count) = $this->db->fetchRow($ret);
					$cat_list[$i]['item_count'] = sprintf(_MD_XMOBILE_NUMBER, $item_count);
				}
				$i++;
			}
		}else{
			// アクション別
			foreach($this->gtdItemList as $itemObject)
			{
				$url_parameter = $this->getBaseUrl().'&amp;'.$this->action_id_fld.'='.$itemObject['id'];
				$cat_list[$i]['key'] = $i+1;	//$itemObject['id'];
				$cat_list[$i]['title'] = $this->adjustTitle( $itemObject['desc'] );
				$cat_list[$i]['url'] = $url_parameter;
				if ($xoopsModuleConfig['show_item_count']){
					$sql = 'SELECT count(*) FROM ' . $this->itemTableName
						. ' WHERE to_userid=' . $xoopsUser->uid() . ' AND ' . $itemObject['wstr'];
					$ret = $this->db->query($sql);
					if (!$ret)
					{
						// debug
						$this->utils->setDebugMessage(__CLASS__, 'getRecentList db error', $this->db->error());
						return false;
					}
					list($item_count) = $this->db->fetchRow($ret);
					if (strcmp($itemObject['id'],'delegate')==0){
						$xoopsUser =& $this->sessionHandler->getUser();
						// プライベートメッセージ分のカウントアップ
						$pm_handler =& xoops_gethandler('privmessage');
						$criteria =new CriteriaCompo( new Criteria('from_userid', $xoopsUser->getVar('uid')));
						$criteria->add( new Criteria('to_userid', $xoopsUser->getVar('uid'),"!=") );
						$criteria->add( new Criteria('msg_time', time() - ($this->delegateListSince * 86400),">" ));
						$pm_arr =& $pm_handler->getObjects($criteria);
						$item_count += count($pm_arr);
						// PM経由で頼んだ事のカウントアップ
						$where_delegate = "(to_userid!=".$xoopsUser->uid() . " AND from_userid=".$xoopsUser->uid() ." AND msg_time>".$this->delegateListSince.")";
						$sql = "SELECT count(*) FROM ".$this->itemTableName."  WHERE ". $where_delegate . " ORDER BY start_date,msg_id;";
						$ret = $this->db->query($sql);
						list($cnt) = $this->db->fetchRow($ret);
						$item_count += $cnt;
					}
					$cat_list[$i]['item_count'] = sprintf(_MD_XMOBILE_NUMBER, $item_count);
				}
				$i++;
			}
		}
		return $cat_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 最新記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getRecentList()
	{
		global $xoopsModuleConfig;
		$xoopsUser =& $this->sessionHandler->getUser();

		$myts =& MyTextSanitizer::getInstance();

//		$this->setItemCriteria();
		if ($xoopsModuleConfig['show_recent_title'] == 0)
		{
			return false;
		}
		$this->setNextViewState('detail');
		$this->setBaseUrl();

		// copy from inukshukGTD/index.php
		$lang['delegateWithin'] = sprintf(_IG_DELEGETELIST_WITHIN,$this->delegateListSince);
		$things = array();
		$delegateSince = time() - ($this->delegateListSince * 86400);
		
		// Show your not yet list and your delegate within N days list
		$sql = "SELECT * FROM ".$this->db->prefix("inukshuk_gtd");
		$sql .= " WHERE ( to_userid=".$xoopsUser->uid();

		//if ($this->actionWhere) $sql .= " AND " . $this->actionWhere;
		$sql .= " )";

		if ( strcmp($this->action,"review")==0 ){
			$sql .= " OR (to_userid!=" . $xoopsUser->uid() . " AND from_userid=" . $xoopsUser->uid() ." AND msg_time>".$delegateSince .")";
		}
		$sql .= " ORDER BY start_date,gtd_attr,msg_id";

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
			$this->msg_id = intval($data['msg_id']);
			$id = intval($data['msg_id']);
			$title = $myts->makeTboxData4Show($data['subject']);
			$url_parameter = '&amp;msg_id='.$id;
			$recent_list[] = array(
				'title' => $this->adjustTitle($title),
				'url' => $this->getBaseUrl() . $url_parameter,
				'date' => $this->utils->getDateLong($data['start_date'], 1).' '.$this->utils->getTimeLong($data['start_date'], 1)
			);
		}
		return $recent_list;
	}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getEditLink()
	{
		$edit_link = '';
//		if ($this->allowEdit == true)
//		{
			$edit_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
			$edit_link .= '<a href="'.$edit_url.'&amp;type=edit&amp;'.$this->item_id_fld.'='.$this->item_id.'">'._EDIT.'</a>&nbsp;';
//		}
		if ($this->allowDelete == true)
		{
			$delete_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
			$edit_link .= '<a href="'.$delete_url.'&amp;type=delete&amp;'.$this->item_id_fld.'='.$this->item_id.'">'._DELETE.'</a>';
		}
		if ($this->allowAdd == true)
		{
			if ($this->allowEdit == true || $this->allowDelete == true)
			{
				$edit_link .= '<hr />';
			}
			$add_url = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
			$catlink = '';
			if (!is_null($this->category_id_fld) && !is_null($this->category_id))
			{
				$catlink = '&amp;'.$this->category_id_fld.'='.$this->category_id;
			}
			$edit_link .= '<a href="'.$add_url.'&amp;type=new'.$catlink.'">'._MD_XMOBILE_POSTNEW.'</a>&nbsp;';
		}
		return $edit_link;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 編集画面
	function getEditView()
	{
		global $xoopsModuleConfig,$xoopsDB;
		$xoopsUser =& $this->sessionHandler->getUser();

		$this->ticket = new XoopsGTicket;
		$this->setNextViewState('confirm');
		$this->setCategoryParameter();
		$this->setItemParameter();
		$this->checkPerm();

		$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$base_url = preg_replace('/&amp;/i','&',$base_url);
		
		$this->controller->render->template->assign('item_detail',$this->getForm());
		$this->controller->render->template->assign('show_edit',true);
		$this->controller->render->template->assign('base_url',$base_url);
		$this->controller->render->template->assign('tarea_cols',$xoopsModuleConfig['tarea_cols']);
		$this->controller->render->template->assign('tarea_rows',$xoopsModuleConfig['tarea_rows']);
		$msg_id = $this->item_id;

		include_once XOOPS_ROOT_PATH.'/modules/inukshukGTD/class/gtdmessage.php';
		include_once XOOPS_ROOT_PATH."/modules/inukshukGTD/include/groupaccess.php";
		include_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
		$gtd_handler = new GtdmessageHandler($xoopsDB);
		$gtd =& $gtd_handler->get( $msg_id );

		if ($gtd->getVar("to_userid") == $xoopsUser->getVar('uid')) {
			$pm_uname = XoopsUser::getUnameFromId($gtd->getVar("from_userid"));
			$subject = $gtd->getVar("subject", "E");
			$msg_text = $gtd->getVar("msg_text", "E");
			$to_userid = $gtd->getVar("to_userid");
			$msg_image = $gtd->getVar("msg_image");
			$gtd_attr = $gtd->getVar("gtd_attr");
			$gtd_done = $gtd->getVar("gtd_done");
			$share_group = $gtd->getVar("groupid");
		}
		$imageNo = array_search( $msg_image, $pmImages );
//		$this->controller->render->template->assign('ticket_html',$token->getHtml());
		$this->controller->render->template->assign('msg_id', $msg_id);
		$this->controller->render->template->assign('imageNo', $imageNo);
		$this->controller->render->template->assign('subject', $subject );
		$this->controller->render->template->assign('msg_text',$msg_text );
		$this->controller->render->template->assign('gtd_done', $gtd_done );
		$this->controller->render->template->assign('to_userid', $to_userid);
		if( $gtd_attr==3 )
			$this->controller->render->template->assign('share_group', grp_listGroups($share_group,"share_group[]"));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 投稿画面
	function getConfirmView()
	{
		$this->controller->render->template->assign('item_detail',$this->saveEntry());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 投稿データの保存(オーバーライド無)
	function saveEntry()
	{
		include_once XOOPS_ROOT_PATH.'/modules/inukshukGTD/config.php';
		include_once XOOPS_ROOT_PATH.'/modules/inukshukGTD/class/gtdmessage.php';
		include_once XOOPS_ROOT_PATH."/modules/inukshukGTD/include/groupaccess.php";
		global $xoopsModuleConfig,$xoopsDB;

		$gtd_handler = new GtdmessageHandler($xoopsDB);
		$myts =& MyTextSanitizer::getInstance();

		if (isset($_POST['cancel'])){
			$baseUrl = preg_replace('/&amp;/i','&',$this->baseUrl);
			header('Location: '.$baseUrl);
			exit();
		}
		$op = $myts->makeTboxData4Show($this->utils->getGetPost('op', ''));
		$uid = $this->sessionHandler->getUid();
		if ($uid){
			if ($op == 'submit'){
				$msg_id = intval($this->utils->getGetPost('msg_id', 0));
				$msg_image = $pmImages[ intval($this->utils->getPost('pmPriority','')) ];
				$groupid = isset($_POST['share_group']) ? grp_saveAccess( $this->utils->getPost('share_group') ) : "NULL";
				$gtd =& $gtd_handler->get($msg_id);
				$gtd->setVar("subject", $myts->makeTboxData4Save($this->utils->getPost('subject', '')));
				$gtd->setVar("msg_text", $myts->makeTareaData4Save($this->utils->getPost('msg_text', ''),0,1,1));
				$gtd->setVar("msg_image", $msg_image );
				$gtd->setVar("gtd_done", intval($this->utils->getPost('igProgress', 0 )) );
				$gtd->setVar("groupid", $groupid );
				if (!$gtd_handler->insert($gtd)) {
					return $gtd_handler->msg;
				}else{
					return _MD_XMOBILE_UPDATE_SUCCESS;
				}
			}
		}
	}
}
?>
