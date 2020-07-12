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
class XmobilePicalPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('uid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('groupid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('summary', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('location', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('organizer', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('sequence', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('contact', XOBJ_DTYPE_TXTBOX, '', true, 255);
//		$this->initVar('tzid', XOBJ_DTYPE_TXTBOX, 'GMT', true, 255);
		$this->initVar('description', XOBJ_DTYPE_TXTAREA, '', true);
//		$this->initVar('dtstamp', XOBJ_DTYPE_TXTBOX, '', true, 14);
		$this->initVar('categories', XOBJ_DTYPE_TXTBOX, '', true, 255);
//		$this->initVar('transp', XOBJ_DTYPE_INT, '1', true);
//		$this->initVar('priority', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('admission', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('class', XOBJ_DTYPE_TXTBOX, 'PUBLIC', true, 255);
//		$this->initVar('rrule', XOBJ_DTYPE_TXTBOX, '', true, 255);
//		$this->initVar('rrule_pid', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('unique_id', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('allday', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('start', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('end', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('start_date', XOBJ_DTYPE_TXTBOX, '', true, 255);
//		$this->initVar('end_date', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('cid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('comments', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('event_tz', XOBJ_DTYPE_FLOAT, '0', true);
//		$this->initVar('server_tz', XOBJ_DTYPE_FLOAT, '0', true);
//		$this->initVar('poster_tz', XOBJ_DTYPE_FLOAT, '0', true);
//		$this->initVar('extkey0', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('extkey1', XOBJ_DTYPE_INT, '0', true);

		// define primary key
		$this->setKeyFields(array('id'));
		$this->setAutoIncrementField('id');
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobilePicalPluginHandlerAbstract extends XmobilePluginHandler
{
	var $moduleDir = 'piCal';
	var $categoryTableName = 'pical_cat';
	var $itemTableName = 'pical_event';

	var $template = 'xmobile_piCal.html';
	var $category_id_fld = 'cid';
	var $category_pid_fld = 'pid';
	var $category_title_fld = 'cat_title';
	var $category_order_fld = 'weight';

	var $item_id_fld = 'id';
	var $item_cid_fld = 'categories';
	var $item_title_fld = 'summary';
	var $item_description_fld = 'description';
	var $item_order_fld = 'start';
	var $item_date_fld = 'start';
	var $item_uid_fld = 'uid';
	var $item_comments_fld = 'comments';

	var $item_order_sort = 'ASC';
	var $year = null;
	var $month = null;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		global $xoopsConfig;
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;

		if (preg_match("/^(\D+)(\d*)$/", $mydirname,$matches))
		{
			$number = $matches[2];
			$this->categoryTableName = 'pical'.$number.'_cat';
			$this->itemTableName = 'pical'.$number.'_event';
		}
		else
		{
			trigger_error( 'Invalid pluginName '. htmlspecialchars( $mydirname ) );
		}

		$fileName = XOOPS_ROOT_PATH.'/modules/'.$this->moduleDir.'/language/'.$xoopsConfig['language'].'/pical_constants.php';
		if (file_exists($fileName))
		{
			include_once $fileName;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// データ取得用criteriaの設定
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function addItemCriteria()
	{
		global $xoopsConfig;

		if (!is_object($this->item_criteria))
		{
			return;
		}
		// 夏時間を含めた補正
		$tz_offset = ((date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600);

		// 開始日が選択した年月
// 全日イベントの場合月末の予定が次月に含まれてしまうので、1秒加えた
		$start_date = mktime(0,0,1,$this->month,1,$this->year)+$tz_offset;
		$end_date = mktime(0,0,0,$this->month+1,1,$this->year)+$tz_offset-1;
		$criteria_date_s =& new CriteriaCompo();
		$criteria_date_s->add(new Criteria('start', $start_date, '>='));
// thx seungjun
//		$criteria_date->add(new Criteria('end', $end_date, '<='));
		$criteria_date_s->add(new Criteria('start', $end_date, '<='));
		$criteria_date_e =& new CriteriaCompo();
		$criteria_date_e->add(new Criteria('end', $start_date, '>='));
		$criteria_date_e->add(new Criteria('end', $end_date, '<='));
		$criteria_date =& new CriteriaCompo();
		$criteria_date->add($criteria_date_s);
		$criteria_date->add($criteria_date_e,'OR');

		$this->item_criteria->add($criteria_date);
		// debug
		$debug_start_date = $this->utils->getDateLong($start_date, 1).' '.$this->utils->getTimeLong($start_date, 1);
		$this->utils->setDebugMessage(__CLASS__, 'start_date', $debug_start_date);
		$debug_end_date = $this->utils->getDateLong($end_date, 1).' '.$this->utils->getTimeLong($end_date, 1);
		$this->utils->setDebugMessage(__CLASS__, 'end_date', $debug_end_date);

		// カテゴリーのアクセス許可
		$user =& $this->sessionHandler->getUser();
		$groupid_array = $this->utils->getGroupIdArray($user);
		$groupperm_handler =& xoops_gethandler('groupperm');
		$cid_array = $groupperm_handler->getItemIds('pical_cat', $groupid_array, $this->mid);

		if (!is_null($this->item_cid_fld) && !is_null($this->category_id) && $this->category_id != 0)
		{
			$this->category_id = sprintf('%05d',$this->category_id);
			if (in_array($this->category_id,$cid_array) === false)
			{
				$this->controller->render->redirectHeader(_MD_XMOBILE_NO_DATA,5,$this->baseUrl);
				exit();
			}
			else
			{
				$this->item_criteria->add(new Criteria($this->item_cid_fld, '%'.$this->category_id.'%', 'LIKE'));
			}
		}
		else
		{
			$criteria_cat = new CriteriaCompo();
			if (count($cid_array) > 1)
			{
				foreach($cid_array as $cid)
				{
					$cid = sprintf('%05d',$cid);
					$criteria_cat->add(new Criteria($this->item_cid_fld, '%'.$cid.'%', 'LIKE'),'OR');
				}
			}
			elseif (count($cid_array) == 1)
			{
				$cid = sprintf('%05d',$cid_array[0]);
				$criteria_cat->add(new Criteria($this->item_cid_fld, '%'.$cid.'%', 'LIKE'));
			}
			$criteria_cat->add(new Criteria($this->item_cid_fld, null),'OR');
			$this->item_criteria->add($criteria_cat);
		}

		// 公開・非公開
		// 閲覧者が管理者なら全て表示
		if (!$this->getModuleAdmin())
		{
			$this->item_criteria->add($this->getClassCriteria());
		}

		if (!is_null($this->item_order_fld))
		{
			$this->item_criteria->setSort($this->item_order_fld);
		}
		if (is_null($this->item_order_sort))
		{
			$this->item_order_sort = $xoopsModuleConfig['title_order_sort'];
		}
		$this->item_criteria->setOrder($this->item_order_sort);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CLASS(公開・非公開)関係のWHERE用条件を生成
	function getClassCriteria()
	{
		$criteria =& new CriteriaCompo();
		$user =& $this->sessionHandler->getUser();

//		if (!is_object($user))
//		{
//			// 閲覧者がゲストなら公開(PUBLIC)レコードのみ
//			$criteria->add(new Criteria('class','PUBLIC'));
//		}
//		else
//		{
			// 通常ユーザなら、PUBLICレコードか、ユーザIDが一致するレコード、または、所属しているグループIDのうちの一つがレコードのグループIDと一致するレコード
			$criteria_sub =& new CriteriaCompo();
			$criteria_sub->add(new Criteria('class','PUBLIC'));
			$criteria_sub->add(new Criteria('uid',$this->sessionHandler->getUid()), 'OR');

			$groupid_array = $this->utils->getGroupIdArray($user);
			$ids = ' ';
			foreach($groupid_array as $groupid)
			{
				$ids .= "$groupid,";
			}
			$ids = substr( $ids, 0, -1);
			if (intval($ids) != 0)
			{
				$criteria_sub->add(new Criteria('groupid', '('.$ids.')', 'IN'), 'OR');
				$criteria->add($criteria_sub);
			}
//		}
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getClassCriteria', $criteria->render());
		return $criteria;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		$this->setDate();
//		$htmlBody = $this->getSelect();
//		$htmlBody .= $this->getItemList();
//		return $htmlBody;

//		$this->controller->render->template->assign('cat_select',$this->getSelect());
		$this->controller->render->template->assign('cat_select',$this->getSelect().$this->renderCalendar($this->year,$this->month));
		$this->controller->render->template->assign('item_list',$this->getItemList());
		$this->controller->render->template->assign('item_list_page_navi',$this->itemListPageNavi->renderNavi());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDetailView()
	{
		$this->setDate();
		$this->setBaseUrl();
		$this->setCategoryParameter();
		$this->setItemParameter();
		$this->setItemDetailPageNavi();

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getDetail criteria', $this->item_criteria->render());
		if (!$itemObjectArray = $this->getObjects($this->item_criteria))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getDetail Error', $this->getErrors());
		}

		if (count($itemObjectArray) == 0)
		{
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
		$title = $itemObject->getVar($this->item_title_fld);
		$detail4html .= _MD_XMOBILE_TITLE.$title.'<br />';
		// ユーザ名
		$uid = $itemObject->getVar($this->item_uid_fld);
		$uname = $this->getUserLink($uid);
		$detail4html .= _MD_XMOBILE_CONTRIBUTOR.$uname.'<br />';
		// 変更点 日付・時刻
		$date = $itemObject->getVar($this->item_date_fld);
		$detail4html .= _MD_XMOBILE_DATE.$this->utils->getDateLong($date, 1).'<br />';
		$detail4html .= _MD_XMOBILE_TIME.$this->utils->getTimeLong($date, 1).'<br />';
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
		// ヒット数
//		$detail4html .= _MD_XMOBILE_HITS.$itemObject->getVar($this->item_hits_fld).'<br />';
		// ヒットカウントの増加
		$this->increaseHitCount($this->item_id);
		// コメント数
//		$detail4html .= _MD_XMOBILE_COMMENT.$itemObject->getVar($this->item_comments_fld).'<br />';
		// 詳細
		$description = '';
		$description = $itemObject->getVar($this->item_description_fld);
		$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
		$detail4html .= $description.'<br />';

		$this->controller->render->template->assign('cat_select',$this->getSelect());
		$com_op = htmlspecialchars($this->controller->utils->getGetPost('com_op', ''), ENT_QUOTES);
		if ($com_op == '')
		{
			$this->controller->render->template->assign('item_detail',$detail4html);
			$this->controller->render->template->assign('item_detail_page_navi',$this->itemDetailPageNavi->renderNavi());
			$this->controller->render->template->assign('edit_link',$this->getEditLink($this->item_id));
		}
		$this->controller->render->template->assign('comment_link',$this->getCommentLink($this->item_id));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getItemList()
	{
		$this->setNextViewState('detail');
		$this->setBaseUrl();
		$this->setItemParameter();
		$this->setItemListPageNavi();

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemList criteria', $this->item_criteria->render());

		$itemObjectArray = $this->getObjects($this->item_criteria);
		if (!$itemObjectArray)
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getItemList Error', $this->getErrors());
		}

		if (count($itemObjectArray) == 0) // 表示するデータ無し
		{
//			return _MD_XMOBILE_NO_DATA;
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		$item_list = array();
		$i = 0;
		foreach($itemObjectArray as $itemObject)
		{
			$id = $itemObject->getVar($this->item_id_fld);
			$title = $itemObject->getVar($this->item_title_fld);
			$url_parameter = $this->getBaseUrl();

			if (!is_null($this->category_pid_fld) && !is_null($this->category_pid))
			{
				$url_parameter .= '&amp;'.$this->category_pid_fld.'='.$this->category_pid;
			}
			if (!is_null($this->category_id_fld) && ($this->item_cid_fld != $this->category_id_fld))
			{
				$url_parameter .= '&amp;'.$this->category_id_fld.'='.$this->category_id;
			}
			if (!is_null($this->item_id_fld))
			{
				$url_parameter .= '&amp;'.$this->item_id_fld.'='.$id;
			}
			$date = '';
			if (!is_null($this->item_date_fld))
			{
				// 変更点
				$date = $itemObject->getVar($this->item_date_fld);
				$date = $this->utils->getDateShort($date, 1);
			}

			$number = $i + 1; // アクセスキー用の番号、1から開始
			$item_list[$i]['key'] = $number;
			$item_list[$i]['title'] = $this->adjustTitle($itemObject->getVar($this->item_title_fld));
			$item_list[$i]['url'] = $url_parameter;
			$item_list[$i]['date'] = $date;
			$i++;
		}
		return $item_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setBaseUrl()
	{
		$this->baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->nextViewState,$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'setBaseUrl', $this->baseUrl);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getItemExtraArg()
	{
		// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
		$extra = 'year='.$this->year.'&month='.$this->month;
		if (!is_null($this->category_id_fld) && !is_null($this->category_id))
		{
			$extra .= '&'.$this->category_id_fld.'='.$this->category_id;
		}
		$item_extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'item_extra_arg', $item_extra_arg);
		return $item_extra_arg;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setDate()
	{
		global $xoopsConfig;

		// 夏時間を含めた補正
		$tz_offset = (date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600;

		$item_id = $this->utils->getGetPost($this->item_id_fld, null);
		if (is_null($item_id))
		{
			$this->year = intval($this->utils->getGetPost('year', strftime('%Y',time()+$tz_offset)));
			$this->month = intval($this->utils->getGetPost('month', strftime('%m',time()+$tz_offset)));
		}
		else
		{
			$itemObject =& $this->get($item_id);
			if (!is_object($itemObject))
			{
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'setDete Error', $this->getErrors());
			}
			else
			{
				$start = $itemObject->getVar('start');
				$this->year = intval($this->utils->getGetPost('year', strftime('%Y',$start+$tz_offset)));
				$this->month = intval($this->utils->getGetPost('month', strftime('%m',$start+$tz_offset)));
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getSelect()
	{
		global $xoopsConfig;

		// 夏時間を含めた補正
		$tz_offset = (date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600;
		$myts =& MyTextSanitizer::getInstance();

		$this->setNextViewState('list');
		$this->setBaseUrl();
		$this->setCategoryParameter();

		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('gperm_groupid', '00000'));
		// グループIDの取得
		$user =& $this->sessionHandler->getUser();
		$groupid_array = $this->utils->getGroupIdArray($user);
//		foreach($groupid_array as $groupid)
//		{
//			$groupid = sprintf('%05d',$groupid);
//			$criteria->add(new Criteria('gperm_groupid', $groupid),'OR');
//		}
		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'getCatSelect criteria', $criteria->render());

		$groupperm_handler =& xoops_gethandler('groupperm');
		$cid_array = $groupperm_handler->getItemIds('pical_cat', $groupid_array, $this->mid);

		$cids = '';
		if (count($cid_array) > 1)
		{
			$cids = implode(',',$cid_array);
		}
		elseif (count($cid_array) == 1)
		{
			$cids = $cid_array[0];
		}

		$select = '';
		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'default',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
//		$baseUrl = preg_replace('/&amp;/i','&',$baseUrl);
		$select .= '<form action="'.$baseUrl.'" method="post">';
		$select .= '<div class="form">';

//		$sql = "SELECT cid,pid,cat_title FROM ".$this->db->prefix('pical_cat')." WHERE enabled = 1 AND cid IN(".$cids.") ORDER BY weight";
//		$sql = "SELECT cid,pid,cat_title FROM ".$this->db->prefix('pical_cat')." WHERE enabled=1 AND cat_depth=1 AND cid IN(".$cids.") ORDER BY weight";
		$sql = "SELECT cid,pid,cat_title FROM ".$this->categoryTableName." WHERE enabled=1 AND cat_depth=1 AND cid IN(".$cids.") ORDER BY weight";
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getCatSelect sql', $sql);

		$ret = $this->db->query($sql);
		if ($ret)
		{
			$select .= _MD_XMOBILE_CATEGORY.'<br />';
			$select .= '<select name="cid">';
			$select .= '<option value="0">----</option>';
			while($data = $this->db->fetchArray($ret))
			{
				$sel = '';
				if ($data['cid'] == $this->category_id)
				{
					$sel = ' selected="selected"';
				}
				$select .= '<option value="'.$data['cid'].'"'.$sel.'>'.$data['cat_title'].'</option>';
				$sel = '';
				$arr = $this->categoryTree->getChildTreeArray($data['cid']);
				if (is_array($arr))
				{
					foreach($arr as $option)
					{
						if (in_array($option['cid'], $cid_array))
						{
							$option['prefix'] = str_replace('.','-',$option['prefix']);
							$catpath = $option['prefix'].'&nbsp;'.$myts->makeTboxData4Show($option['cat_title']);
							if ($option['cid'] == $this->category_id)
							{
								$sel = ' selected="selected"';
							}
							$select .= '<option value="'.$option['cid'].'"'.$sel.'>'.$catpath.'</option>';
							$sel = '';
						}
					}
					unset($arr);unset($option);
				}
			}
			$select .= '</select><br />';
		}

	// year and month select
		$select .= '<select name="year">';
		for($i=-1;$i<=1;$i++)
		{
			$temp_year = strftime('%Y',time()+$tz_offset) + $i;
			if ($temp_year == $this->year)
			{
				$sel = 'selected="selected"';
			}
			else
			{
				$sel = '';
			}
			$select .= '<option value="'.$temp_year.'" '.$sel.'>'.$temp_year.'</option>';
		}
		$select .= '</select>'._MD_XMOBILE_YEAR;

		$select .= '<select name="month">';
		for($i=1;$i<=12;$i++)
		{
			if ($i == $this->month)
			{
				$sel = 'selected="selected"';
			}
			else
			{
				$sel = '';
			}
			$select .= '<option value="'.$i.'" '.$sel.'>'.$i.'</option>';
		}
		$select .= '</select>'._MD_XMOBILE_MONTH;
		$select .= '<input type="submit" name="submit" value="'._MD_XMOBILE_SHOW.'" />';
		$select .= '</div>';
		$select .= '</form><hr />';

		return $select;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderCalendar($year=0,$month=0)
	{
		global $xoopsConfig;

		// 夏時間を含めた補正
		$tz_offset = (date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600;
		$year = intval($year);
		$month = intval($month);

		if ($year == 0) $year = date('Y',time()+$tz_offset);
		if ($month == 0) $month = date('m',time()+$tz_offset);

		$select_month = mktime(0,0,0,$month,1,$year);

		$start_week = date('w',$select_month);
		$days_count = date('t',$select_month);

		$QUERY_STRING = preg_replace('/&month='.$month.'&year='.$year.'/','',$_SERVER['QUERY_STRING']);
		$QUERY_STRING = htmlspecialchars($QUERY_STRING);

		$previous_month_par = '';
		//前月へのリンク
		if ($month == 1) //1月は前年の12月
		{
			$previous_year = $year-1;
			$previous_month_par = '&amp;month=12&amp;year='.$previous_year;
		}
		else //1月以外は同年の前月
		{
			$previous_month = $month-1;
			$previous_month_par = '&amp;month='.$previous_month.'&amp;year='.$year;
		}

		$next_month_par = '';
		//翌月へのリンク
		if ($month == 12) //12月は翌年の1月
		{
			$next_year = $year+1;
			$next_month_par = '&amp;month=1&amp;year='.$next_year;
		}
		else //12月以外は同年の翌月
		{
			$next_month = $month+1;
			$next_month_par = '&amp;month='.$next_month.'&amp;year='.$year;
		}

		$data = htmlspecialchars($_SERVER['PHP_SELF']);

		$previous_month_link = $data.'?'.$QUERY_STRING.$previous_month_par;
		$next_month_link = $data.'?'.$QUERY_STRING.$next_month_par;
		$this_month_link = $data.'?'.$QUERY_STRING.'&amp;month='.$month.'&amp;year='.$year.'&amp;';

		$ret = '';
		$ret = _MD_XMOBILE_SUNDAY.'&nbsp;'._MD_XMOBILE_MONDAY.'&nbsp;'._MD_XMOBILE_TUESDAY.'&nbsp;'._MD_XMOBILE_WEDNESDAY.'&nbsp;'._MD_XMOBILE_THURSDAY.'&nbsp;'._MD_XMOBILE_FRIDAY.'&nbsp;'._MD_XMOBILE_SATURDAY.'<br />';

		$number = 0;

		for ($i = 1; $i < 38; $i++)
		{
			$number = $i-$start_week;
			if ($i <= $start_week)
			{
				$ret .= '&nbsp;&nbsp;&nbsp;';
			}
			elseif ($number > $days_count)
			{
				$ret .= '&nbsp;&nbsp;&nbsp;';
			}
			else
			{
//				if ($number == date('j') && $month == date('n') && $year == date('Y'))
//				{
//					if ($number < 10)
//					{
//						$number4show = '<b>&nbsp;'.$number.'</b>';
//					}
//					else
//					{
//						$number4show = '<b>'.$number.'</b>';
//					}
//				}
//				else
				{
					if ($number < 10)
					{
						$number4show = '&nbsp;'.$number;
					}
					else
					{
						$number4show = $number;
					}
				}

				$ret .= '<a href="'.$this_month_link.$number.'">'.$number4show.'</a>'.'&nbsp;';
//				$ret .= $number4show;

				if ($i % 7 == 0)
				{
					$ret .= '<br />';
				}
			
			}

			if (($days_count <= $number) && ($i % 7 == 0))
			{
				break;
			}
		}

		return $ret;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobilePicalPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobilePicalPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
?>
