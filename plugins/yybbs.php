<?php
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Xmobile用 YYBBS改プラグイン V0.2 bata
// -----------------------------------------------------------
// 本プラグインは、YYBBS改0.5994、Xmobile0.33をベースに確認しています。
//
// 実装機能＆制限としまして
// ・記事の参照、新規投稿、返信です（修正、削除は未実装）
// ・画像添付機能はありません
// ・イベント通知機能はありません（Xmobileが未実装のため）
// ・個人サイトで運用しているので作りは甘甘です
// ・Xmobile「一般設定」の「カテゴリの表示方法」で、「セレクトボックス」は未対応です
//
// 以下、Xmobile本体を変更し、モジュールのアップデートを実行してください。
// -----------------------------------------------------------

// xmobile\templates\xmobile_yybbs.html
// xmobile\plugins\yybbs.php
//  ・上記2ファイルの追加
//
// xmobile\xoops_version.php
//  ・91行目付近:テンプレートの追加
//      $modversion['templates'][32]['file'] = 'xmobile_xoopspoll.html';
//      $modversion['templates'][32]['description'] = '';
//      $modversion['templates'][33]['file'] = 'xmobile_yybbs.html';     （←追加）
//      $modversion['templates'][33]['description'] = '';                （←追加）
//     
//
// xmobile\class\Plugin.class.php
//  ・253行目付近:取得したカテゴリ情報を格納
//      ------------------
//      〜
//      $number = $i + 1;
//      $cat_list[$i] = $category;                      （←追加）
//      $cat_list[$i]['key'] = $number;
//      $cat_list[$i]['title'] = $this->adjustTitle($title);
//      〜
//      ------------------
//
//  ・357行目付近:取得したアイテム情報を格納
//      ------------------
//      〜
//      $number = $i + 1;
//      $item_list[$i]['_itemObject'] = $itemObject;    （←追加）
//      $item_list[$i]['key'] = $number;
//      $item_list[$i]['title'] = $this->adjustTitle($title);
//      〜
//      ------------------
//
// xmobile\language\japanese\main.php
//  ・YYBBS用の定義文追加
//      define('_MD_XMOBILE_POST_SUCCESS_MSG','投稿が完了しました。');
//      define('_MD_XMOBILE_GO_NEXT','一覧へ戻る');
//      define('_MD_XMOBILE_RES_MSG','へ返信します。');
//
// -----------------------------------------------------------
// - 改版履歴 -
// [2007.11.08] V0.1 bata: 新規作成
// [2008.01.04] V0.2 bata: 投稿、返信時のパーミッションチェック追加
//
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

if(!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileYybbsPlugin extends XmobilePlugin
{
	function XmobileYybbsPlugin()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();
		// define object elements
		$this->initVar('id',			XOBJ_DTYPE_INT, '0', true);
		$this->initVar('serial',		XOBJ_DTYPE_INT, '0', true);
		$this->initVar('bbs_id',		XOBJ_DTYPE_INT, '1', true);
		$this->initVar('uid',			XOBJ_DTYPE_INT, '0', true);
		$this->initVar('name',			XOBJ_DTYPE_TXTBOX, '', true, 64);
		$this->initVar('email',			XOBJ_DTYPE_TXTBOX, '', true, 64);
		$this->initVar('url',			XOBJ_DTYPE_TXTBOX, '', true, 64);
		$this->initVar('title',			XOBJ_DTYPE_TXTBOX, '', true, 64);
		$this->initVar('message',		XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('icon',			XOBJ_DTYPE_TXTBOX, '', true, 24);
		$this->initVar('col',			XOBJ_DTYPE_TXTBOX, '0', true, 8);
		$this->initVar('passwd',		XOBJ_DTYPE_TXTBOX, '', true, 34);
		$this->initVar('parent',		XOBJ_DTYPE_INT, '0', true);
		$this->initVar('inputdate',		XOBJ_DTYPE_INT, '0', true);
		$this->initVar('update_date',	XOBJ_DTYPE_INT, '0', true);
		$this->initVar('ip',			XOBJ_DTYPE_TXTBOX, '', true, 22);
		$this->initVar('thumb_w',		XOBJ_DTYPE_INT, '0', true);
		$this->initVar('thumb_h',		XOBJ_DTYPE_INT, '0', true);
		$this->initVar('ext',			XOBJ_DTYPE_TXTBOX, '', true, 5);

		// define primary key
		$this->setKeyFields(array('id'));
		$this->setAutoIncrementField('id');
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileYybbsPluginHandler extends XmobilePluginHandler
{
//	var $moduleDir = 'yybbs';
//	var $categoryTableName = 'yybbs_bbs';
//	var $itemTableName = 'yybbs';
	var $template = 'xmobile_yybbs.html';

// category parameters
	var $category_id_fld = 'bbs_id';
	var $category_title_fld = 'title';
	var $category_order_fld = 'priority';

// item parameters
	var $item_id_fld = 'id';
	var $item_cid_fld = 'bbs_id';
	var $item_uid_fld = 'uid';
	var $item_title_fld = 'title';
	var $item_description_fld = 'message';
	var $item_order_fld = 'update_date';
	var $item_date_fld = 'update_date';
	var $item_order_sort = 'ASC';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileYybbsPluginHandler($db)
	{
		global $xoopsConfig;
		XmobilePluginHandler::XmobilePluginHandler($db);

		$pluginName = strtolower(basename(__FILE__,'.php'));
		if(!preg_match("/^\w+$/", $pluginName))
		{
			trigger_error('Invalid pluginName');
			exit();
		}
		$this->moduleDir = $pluginName;
		$this->categoryTableName = $pluginName.'_bbs';
		$this->itemTableName = $pluginName;
		
		$langFileDir = XOOPS_ROOT_PATH.'/modules/'.$this->moduleDir.'/language/'.$xoopsConfig['language'];
		$langFileName1 = $langFileDir.'/main.php';
		$langFileName2 = $langFileDir.'/modinfo.php';
		if(file_exists($langFileName1))
		{
			include_once $langFileName1;
		}
		if(file_exists($langFileName2))
		{
			include_once $langFileName2;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 初期画面
// カテゴリ一覧・最新データ一覧・編集用リンクを表示
	function getDefaultView()
	{
		parent::getDefaultView();

		if($this->getCatList() == false){
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 一覧画面
// カテゴリ一覧・データ一覧・編集用リンクを表示
	function getListView()
	{
		parent::getListView();

		//パーミッションチェック
		if(!$this->checkPermission("post_new_thread",$this->category_id)){
			return false;
		}
		
		$editURL = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$editURL .= '&'.$this->category_id_fld.'='.$this->category_id. '&proc='._MD_XMOBILE_POSTNEW.'&back_view=list';
		$editURL = preg_replace('/&amp;/i','&',$editURL);
		
		$editLink = '<form action="'.$editURL.'" method="post">';
		$editLink .= '<input type="submit" value="'._MD_XMOBILE_POSTNEW.'"></form>';
		
		$this->controller->render->template->assign('cat_link',$editLink);
		$this->controller->render->template->assign('item_link',$editLink);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 詳細画面
// データ詳細・コメント・編集用リンクを表示
// データ詳細は丸ごとHTMLでitem_detailとして出力
	function getDetailView()
	{
		parent::getDetailView();
		$this->controller->render->template->assign('item_detail_page_navi','');	//ページ遷移でidパラメタがなくなってしまうため、画面遷移後に記事IDが取得できない
		
		//パーミッションチェック
		if(!$this->checkPermission("post_response",$this->category_id)){
			return false;
		}

		$id = htmlspecialchars($this->utils->getGetPost($this->item_id_fld, ''), ENT_QUOTES);
		$editURL = $this->utils->getLinkUrl($this->controller->getActionState(),'edit',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$editURL .= '&'.$this->category_id_fld.'='.$this->category_id. '&proc='._MD_XMOBILE_REPLY.'&back_view=detail&id='.$id;
		$editURL = preg_replace('/&amp;/i','&',$editURL);
		
		$editLink = '<form action="'.$editURL.'" method="post">';
		$editLink .= '<input type="submit" value="'._MD_XMOBILE_REPLY.'"></form>';
		
		$this->controller->render->template->assign('cat_link',$editLink);
		$this->controller->render->template->assign('item_link',$editLink);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 編集画面
	function getEditView()
	{
		$this->controller->render->template->assign('cat_list',$this->getCatList());
		$this->controller->render->template->assign('cat_list_page_navi',$this->categoryPageNavi->renderNavi());
		$this->controller->render->template->assign('item_detail',$this->renderEntryForm());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 投稿画面
	function getConfirmView()
	{
		$this->controller->render->template->assign('item_detail',$this->saveEntry());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// カテゴリ一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getCatList()
	{
		$categoryArray = parent::getCatList();

		//パーミッションで許可されているIDと比較して、ダメなものはハジク
		$cat_list = array();
		$i = 0;
		foreach($categoryArray as $category)
		{
			//掲示板IDの取得
			$bbs_id = $category[$this->category_id_fld];
			//パーミッションチェック
			if($this->checkPermission("view_bbs",$bbs_id)){
				$cat_list[$i] = $category;
				$i++;
			}
		}
		if(!count($cat_list)) {		//パーミッションにより、表示する情報無し
			return false;
		}
		return $cat_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// カテゴリセレクトボックスの取得
// 戻り値はHTML
	function getCatSelect()
	{
		$categoryArray = $this->getCatList();

		$cat_select = '';
		$cat_select .= '<select name="'.$this->category_id.'">';
		$cat_select .= '<option value="0">----</option>';

		$i = 0;
		foreach($categoryArray as $category)
		{
			//掲示板IDの取得
			$bbs_id = $category[$this->category_id_fld];
			$title = $category['title'];

			$sel = '';
			if($bbs_id == $this->category_id)
			{
				$sel = ' selected="selected"';
			}
			$cat_select .= '<option value="'.$bbs_id.'"'.$sel.'>'.$title.'</option>';
			$i++;
		}
		$cat_select .= '</select>';
		
		if($cat_select != '')
		{
			$base_url = preg_replace("/&amp;/i",'&',$this->getBaseUrl());
			$catselect4html = '';
			$catselect4html .= '<form action="'.$base_url.'" method="post">';
			$catselect4html .= '<div class ="form">';
			$catselect4html .= _MD_XMOBILE_CATEGORY.'<br />';
			$catselect4html .= $cat_select.'<br />';
			$catselect4html .= '<input type="submit" name="submit" value="'._MD_XMOBILE_SHOW.'" />';
			$catselect4html .= '</div>';
			$catselect4html .= '</form>';
		}
		else // 表示するデータ無し
		{
			$catselect4html = false;
		}

		return $catselect4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getItemList()
	{
		$itemObjectArray = parent::getItemList();
		//パーミッションチェック
		if(!$this->checkPermission("view_bbs",$this->category_id)){
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_PERM_MESSAGE);
			return false;
		}

		if($itemObjectArray == false || count($itemObjectArray) == 0) // 表示するデータ無し
		{
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		$item_list = array();
		$i = 0;
		foreach($itemObjectArray as $itemObject)
		{
			$item_list[$i] = $itemObject;

			$workObject = $itemObject['_itemObject'];

			$uid = $workObject->getVar($this->item_uid_fld);
			$uname = $this->getUserLink($uid);
			$title = $itemObject['title'];
			$item_list[$i]['title'] = $title. " (".$uname.")";
			$i++;
		}
		return $item_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 最新記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getRecentList()
	{
		return parent::getRecentList();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事詳細・コメント・編集用リンクの取得
// ただし、戻り値はオブジェクトではなくHTML
	function getItemDetail()
	{
		//パーミッションチェック
		if(!$this->checkPermission("view_bbs",$this->category_id)){
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_PERM_MESSAGE);
			return false;
		}

		$detail4html = parent::getItemDetail();

		$workCriteria=new CriteriaCompo();
		$workCriteria->add(new Criteria('parent', $this->item_id, '='));
		$workCriteria->setSort($this->item_date_fld);
		$workCriteria->setOrder('DESC');
		$itemObjectArray = $this->getObjects($workCriteria);

		foreach($itemObjectArray as $itemObject)
		{
			if(!is_object($itemObject))
			{
				continue;
			}

			$itemObject->assignSanitizerElement();

			$detail4html .= "<hr>";
			$detail4html .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
			// タイトル
			if(!is_null($this->item_title_fld))
			{
				$detail4html .= _MD_XMOBILE_TITLE;
				$detail4html .= $itemObject->getVar($this->item_title_fld).'<br />';
			}
			// ユーザ名
			if(!is_null($this->item_uid_fld))
			{
				$uid = $itemObject->getVar($this->item_uid_fld);
				$uname = $this->getUserLink($uid);
				$detail4html .= _MD_XMOBILE_CONTRIBUTOR.$uname.'<br />';
			}
			// 日付・時刻
			if(!is_null($this->item_date_fld))
			{
				$date = $itemObject->getVar($this->item_date_fld);
				$detail4html .= _MD_XMOBILE_DATE.$this->utils->getDateLong($date).'<br />';
				// 変更点
				$detail4html .= _MD_XMOBILE_TIME.$this->utils->getTimeLong($date).'<br />';
			}
			// ヒット数
			if(!is_null($this->item_hits_fld))
			{
				$detail4html .= _MD_XMOBILE_HITS.$itemObject->getVar($this->item_hits_fld).'<br />';
				// ヒットカウントの増加
				$this->increaseHitCount($this->item_id);
			}
			// コメント
			if(!is_null($this->item_comments_fld))
			{
	//			$detail4html .= _MD_XMOBILE_COMMENT.$itemObject->getVar($this->item_comments_fld).'<br />';
			}
			// 詳細
			$description = '';
			if(!is_null($this->item_description_fld))
			{
				$description = $itemObject->getVar($this->item_description_fld);
				$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
				$detail4html .= $description.'<br />';
			}
			// その他の表示フィールド
			if(count($this->item_extra_fld) > 0)
			{
				foreach($this->item_extra_fld as $key=>$caption)
				{
					if($itemObject->getVar($key))
					{
						$detail4html .= $caption;
						$detail4html .= $itemObject->getVar($key).'<br />';
					}
				}
			}
		}
		return $detail4html;

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setCategoryId()
	{
		$this->category_id = $this->utils->getGetPost($this->category_id_fld, null);
		if(is_null($this->category_id) && !is_null($this->item_cid_fld))
		{
			$this->category_id = $this->utils->getGetPost($this->item_cid_fld, null);
		}

		if(!is_null($this->category_id))
		{
			$this->category_id = intval($this->category_id);
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'category_id', $this->category_id);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//カテゴリ一覧表示（setItemParameter()）でセットされる、記事データの抽出方法
	//スレッドの親のみの一覧を表示するため、'parent'カラム"0"のものだけ表示する。
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
		$this->item_criteria->add(new Criteria('parent', 0, '='));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderEntryForm()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();
		$this->setItemCriteria();

		$this->ticket = new XoopsGTicket;

		$paramList = $this->getParams();
		$comment = '';
		switch($paramList["proc"])
		{
			case _MD_XMOBILE_POSTNEW:
				break;

			case _MD_XMOBILE_REPLY:
				$itemObjectArray = parent::getItemList();
				
				//自記事の情報取得
				$itemObject = array();
				foreach($itemObjectArray as $_itemObject)
				{
					$workObject = $_itemObject['_itemObject'];
					$uid = $workObject->getVar($this->item_id_fld);
					if($uid == $paramList["id"])
					{
						$itemObject = $_itemObject;
						break;
					}
				}
				$paramList["title"] = "Re: ".$itemObject['title'];
				$comment = _MD_XMOBILE_TITLEJ.' ['.$itemObject['title'].'] '._MD_XMOBILE_RES_MSG;
				break;
		}

		$member_handler =& xoops_gethandler('member');
		$uid = $this->sessionHandler->getUid();
		$user =& $member_handler->getUser($uid);

		if(is_object($user))
		{
			$paramList["name"] = $user->getVar('uname');
		}
//		$paramList["passwd"] = $uid;

		$submitURL = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$submitURL = preg_replace('/&amp;/i','&',$submitURL);
		$cancelURL = $this->utils->getLinkUrl($this->controller->getActionState(),$paramList["back_view"],$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$cancelURL = preg_replace('/&amp;/i','&',$cancelURL);

		$innerHTML = '';
		$innerHTML .= '<strong>'.$paramList["proc"].'</strong><br />'.$comment.'<br />&nbsp;';
		$innerHTML .= '<form action="'.$submitURL.'" method="post"><div class ="form">';
		$innerHTML .= $this->ticket->getTicketHtml();
		$innerHTML .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
		$innerHTML .= '<input type="hidden" name="HTTP_REFERER" value="'.$submitURL.'" />';
		$innerHTML .= $this->getHiddenParams($paramList);
		$innerHTML .= _MD_XMOBILE_NAME.'<br /><input type="text" name="name" value="'.$paramList["name"].'" /><br />';
		$innerHTML .= _MD_XMOBILE_TITLE.'<br /><input type="text" name="title" value="'.$paramList["title"].'" /><br />';
		$innerHTML .= _MD_XMOBILE_PASSWORD.'<br /><input type="text" name="passwd" value="" /><br />';
		$innerHTML .= _MD_XMOBILE_MESSAGE.'<br /><textarea rows="'.$xoopsModuleConfig['tarea_rows'].'" cols="'.$xoopsModuleConfig['tarea_cols'].'" name="message">'.$paramList["message"].'</textarea><br />';
		$innerHTML .= '<input type="submit" name="submit" value="'._SUBMIT.'" />';
		$innerHTML .= '</div></form>';
		$innerHTML .= '<form action="'.$cancelURL.'" method="post"><div class ="form">';
		$innerHTML .= $this->getHiddenParams($paramList);
		$innerHTML .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
		$innerHTML .= '</form>';

		return $innerHTML;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function saveEntry()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();
		$this->setItemCriteria();

		$paramList = $this->getParams();

		$this->ticket = new XoopsGTicket;
		if(!$ticket_check = $this->ticket->check(true,'',false))
		{
			return _MD_XMOBILE_TICKET_ERROR;
		}

		$uid = $this->sessionHandler->getUid();
		$now_time = time();
		
		//全記事IDの最大値取得
		$sql = '';
		$sql .= 'select MAX('. $this->item_id_fld .') as '. $this->item_id_fld. ' from '. $this->itemTableName;
//echo "<br>SQL:".$sql. "<br>";
		if(!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'SQL error:', $this->db->error());
			return _MD_XMOBILE_INSERT_FAILED ."<br />".$sql;
		}
		$max_id = 1;
		while($row = $this->db->fetchArray($ret))
		{
			$max_id = intval($row[$this->item_id_fld]) + 1;
		}

		//該当掲示板内の最大記事IDを取得
		$sql = '';
		$sql .= 'select MAX(serial) as serial from '. $this->itemTableName. ' where bbs_id = '. $paramList["bbs_id"];
//echo "<br>SQL:".$sql. "<br>";
		if(!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'SQL error:', $this->db->error());
			return _MD_XMOBILE_INSERT_FAILED ."<br />".$sql;
		}
		$max_serial = 1;
		while($row = $this->db->fetchArray($ret))
		{
			$max_serial = intval($row['serial']) + 1;
		}

		//新規投稿でも、返信でもとりあえず登録
		$_sql = "";
		$_sql .= "insert into %s ";
		$_sql .= "(id, serial, bbs_id, uid, name, url, title, message, col, passwd, parent, inputdate, update_date, ip, thumb_w, thumb_h) values ";
		$_sql .= "(%u, %u, %u, %u, '%s', 'http://', '%s', '%s', '#800000', '%s', '0', '%u', '%u', '%s', '0', '0') ";

		$sql = sprintf( $_sql, 
						$this->itemTableName,
						$max_id,
						$max_serial,
						$paramList["bbs_id"],
						$uid,
						$myts->addSlashes($paramList["name"]),
						$myts->addSlashes($paramList["title"]),
						$myts->addSlashes($paramList["message"]),
						$myts->addSlashes(md5($paramList["passwd"])),
						$now_time,
						$now_time,
						$myts->addSlashes($_SERVER['REMOTE_ADDR'])
						);
//echo "<br>SQL:".$sql. "<br>";
		$this->utils->setDebugMessage(__CLASS__, 'saveEntry SQL:', $sql);
		if(!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'SQL error:', $this->db->error());
			return _MD_XMOBILE_INSERT_FAILED ."<br />".$sql;
		}
		
		//掲示板テーブルのシリアル番号更新
		$_sql = "";
		$_sql .= "update %s set serial = %u where bbs_id = %u";
		$sql = sprintf( $_sql, $this->categoryTableName, $max_serial, $paramList["bbs_id"] );
		$this->utils->setDebugMessage(__CLASS__, 'saveEntry SQL:', $sql);
//echo "<br>SQL:".$sql. "<br>";
		if(!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'SQL error:', $this->db->error());
			return _MD_XMOBILE_INSERT_FAILED ."<br />".$sql;
		}

		switch($paramList["proc"])
		{
			case _MD_XMOBILE_POSTNEW:
			
				break;

			case _MD_XMOBILE_REPLY:
				//返信だった場合、自分のparentに親のid情報を格納する
				$_sql = "";
				$_sql .= "update %s a, %s b set a.parent = case when b.parent != 0 then b.parent else b.id end where a.id=%u and b.id = %u";
				$sql = sprintf( $_sql, $this->itemTableName, $this->itemTableName, $max_id, $paramList["id"] );
				$this->utils->setDebugMessage(__CLASS__, 'saveEntry SQL:', $sql);
//echo "<br>SQL:".$sql. "<br>";
				if(!$ret = $this->db->query($sql))
				{
					$this->utils->setDebugMessage(__CLASS__, 'SQL error:', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED ."<br />".$sql;
				}

				//返信だった場合、自分の親のupdate_dateを更新する
				$_sql = "";
				$_sql .= "update %s a, %s b set a.update_date = b.update_date where a.id = b.parent and b.id = %u";
				$sql = sprintf( $_sql, $this->itemTableName, $this->itemTableName, $max_id );
				$this->utils->setDebugMessage(__CLASS__, 'saveEntry SQL:', $sql);
//echo "<br>SQL:".$sql. "<br>";
				if(!$ret = $this->db->query($sql))
				{
					$this->utils->setDebugMessage(__CLASS__, 'SQL error:', $this->db->error());
					return _MD_XMOBILE_INSERT_FAILED ."<br />".$sql;
				}
				
				break;
		}

/*
		// 通知メール送信
		$notify =& xoops_gethandler('notification');
		$pageuri = sprintf('%s/modules/yybbs/viewbbs.php?bbs_id=%d',XOOPS_URL,$obj->getVar('bbs_id'));
		$tags = array('BBS_TITLE'=>$bbs->getVar('title'),
		      'NAME'=>$obj->getVar('name'),
		      'TITLE'=>$obj->getVar('title'),
		      'PAGE_URI'=>$pageuri);
		$notify->triggerEvent('yybbs_bbs', $obj->getVar('bbs_id'), 'entry', $tags);
		$notify->triggerEvent('yybbs', 0, 'entry', $tags);
*/

		$nextURL = $this->utils->getLinkUrl($this->controller->getActionState(),$paramList["back_view"],$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$nextURL .= $this->getHiddenURL($paramList);
		$nextURL = preg_replace('/&amp;/i','&',$nextURL);

		$innerHTML = "";
		$innerHTML .= _MD_XMOBILE_POST_SUCCESS_MSG ."<br />&nbsp;<br />";
		$innerHTML .= '<a href="'.$nextURL . '">'._MD_XMOBILE_GO_NEXT.'</a>';
		return $innerHTML;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getParams()
	{
		$pramaNames = array("name", "title", "message", "id", "bbs_id", "parent", "passwd", "proc", "submit", "cancel", "back_view");

		$paramList = array();
		foreach($pramaNames as $name)
		{
			$value = htmlspecialchars($this->utils->getGetPost($name, ''), ENT_QUOTES);
			$paramList[$name] = $value;

			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getParams ', $name. "=". $value);
		}
		return $paramList;
	}
	
	function getHiddenParams($paramList)
	{
		$hiddenHTML = "";
		foreach ($paramList as $name => $value)
		{
			if($value != ""){
				$hiddenHTML .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
			}
		}
		return $hiddenHTML;
	}

	function getHiddenURL($paramList)
	{
		$hiddenURL = "";
		foreach ($paramList as $name => $value)
		{
			if($value != ""){
				$hiddenURL  .= '&'.$name.'='.$value;
			}
		}
		return $hiddenURL;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkPermission($perm_name, $category_id)
	{
		require_once XOOPS_ROOT_PATH."/modules/".$this->moduleDir."/class/global.php";
		require_once XOOPS_ROOT_PATH."/modules/exFrame/frameloader.php";
		require_once XOOPS_ROOT_PATH."/modules/exFrame/xoops/perm.php";

		$xoopsUser =& $this->sessionHandler->getUser();
		$criteria=new CriteriaCompo();
		$module_handler =& xoops_gethandler('module');
		$config_handler =& xoops_gethandler('config');
		$xoopsYybbsModule =& $module_handler->getByDirname($this->moduleDir);
		$xoopsYybbsModuleConfig =& $config_handler->getConfigsByCat(0,$xoopsYybbsModule->getVar('mid'));

		// パーミッション制限があれば許可されている BBS を調べる
		if($xoopsYybbsModuleConfig['permission']) {
			if(!exPerm::Guard($perm_name)) {
				$handler=&exXoopsGroupPermHandler::getInstance();

				$pCriteria=new CriteriaCompo();
				$pCriteria->add(new Criteria('gperm_modid',$xoopsYybbsModule->mid()));
				$pCriteria->add(new Criteria('gperm_name',$perm_name));
				$groups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
				$pCriteria->add(new criteria('gperm_groupid', '('.implode(",", $groups).')', 'IN'));
			
				$objs=&$handler->getObjects($pCriteria);
				if(!count($objs)) {		//パーミッションにより、表示する情報無し
					return false;
				} else {
		    		foreach($objs as $obj) {
		    			$criteria->add(new Criteria('bbs_id',$obj->getVar('gperm_itemid')), 'OR');
		    		}
				}
			}
		}

		//パーミッション許可されている掲示板IDの配列を作成
		$handler=&YYBBS::getHandler('bbs');
		$objs=&$handler->getObjects($criteria,null,'priority');

		foreach($objs as $obj)
		{
			$ok_bbs_id = $obj->getVar($this->category_id_fld);
			if($ok_bbs_id == $category_id){
				return true;
			}
		}
		return false;
	}

}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ページナビゲーション用パラメータの取得
/*	function getItemExtraArg()
	{
		// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
		$extra = parent::getItemExtraArg();
		//カレントの記事IDを設定する
		$id = htmlspecialchars($this->utils->getGetPost($this->item_id_fld, ''), ENT_QUOTES);
		if($id != ""){
			$extra .= '&amp;'.$this->item_id_fld.'='.$id;
		}
		echo "=======>".$extra;
		return $extra;
	}
*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
