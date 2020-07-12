<?php
if (!defined('XOOPS_ROOT_PATH')) exit();

$mydirname = basename(__FILE__,'.php');

$Pluginname = ucfirst($mydirname);
if (!preg_match("/^\w+$/", $Pluginname))
{
	trigger_error('Invalid pluginName');
	exit();
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileD3pipesPluginAbstract extends XmobilePlugin
{
	function __construct($mydirname)
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();
		// define object elements
		$this->initVar('clipping_id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('pipe_id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('fingerprint', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('pubtime', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('link', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('headline', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('can_search', XOBJ_DTYPE_INT, '1', true);
		$this->initVar('highlight', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('weight', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('comments_count', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('fetched_time', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('data', XOBJ_DTYPE_TXTAREA, '', true);

		// define primary key
		$this->setKeyFields(array('clipping_id'));
		$this->setAutoIncrementField('clipping_id');
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		$dohtml = 1;
		$dosmiley = 1;
		$doxcode = 1;

		$this->initVar('dohtml',XOBJ_DTYPE_INT,$dohtml);
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,$dosmiley);
		$this->initVar('doxcode',XOBJ_DTYPE_INT,$doxcode);
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileD3pipesPluginHandlerAbstract extends XmobilePluginHandler
{
	var $moduleDir = '';
	var $categoryTableName = '';
	var $itemTableName = '';
	var $template = 'xmobile_d3pipes.html';
// category parameters
	var $category_id_fld = 'pipe_id';
	var $category_title_fld = 'name';
//	var $category_order_fld = 'weight';
// item parameters
	var $item_id_fld = 'clipping_id';
	var $item_cid_fld = 'pipe_id';
	var $item_title_fld = 'headline';
	var $item_description_fld = 'data';
	var $item_order_fld = 'pubtime';
	var $item_date_fld = 'pubtime';
	var $item_order_sort = 'DESC';

	var $item_list;
	var $total;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;
		$this->categoryTableName = $mydirname.'_pipes';
		$this->itemTableName = $mydirname.'_clippings';
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function prepare(&$controller)
	{
		parent::prepare($controller);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		if ($this->item_criteria == null)
		{
			$this->item_criteria =& new CriteriaCompo();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*
	function getDefaultView()
	{
		parent::getListView();
	}
*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setBaseUrl()
	{
		$this->baseUrl = $this->utils->getLinkUrl('plugin',$this->nextViewState,$this->moduleDir,$this->sessionHandler->getSessionID());
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'setBaseUrl', $this->baseUrl);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// データ一覧のページナビゲーションの初期化後
// アイテムデータ取得用criteriaにリミット、スタートを設定
	function setItemListPageNavi()
	{
		global $xoopsModuleConfig;
		$this->total = $this->getD3pipeEntriesCount($this->category_id);
		$this->itemListPageNavi =& new XmobilePageNavigator($this->total, $xoopsModuleConfig['max_title_row'], 'start', $this->getItemExtraArg());
		$this->item_criteria->setLimit($this->itemListPageNavi->getPerpage());
		$this->item_criteria->setStart($this->itemListPageNavi->getStart());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// データ詳細のページナビゲーションの初期化後
// アイテムデータ取得用criteriaにリミット、スタートを設定
	function setItemDetailPageNavi()
	{
		$this->total = $this->getD3pipeEntriesCount($this->category_id);

		$this->itemDetailPageNavi =& new XmobilePageNavigator($this->total, 1, 'start', $this->getItemExtraArg());
		$this->item_criteria->setLimit($this->itemDetailPageNavi->getPerpage());
		$this->item_criteria->setStart($this->itemDetailPageNavi->getStart());

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi Limit', $this->itemDetailPageNavi->getPerpage());
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi Start', $this->itemDetailPageNavi->getStart());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getItemList()
	{
		$this->setNextViewState('detail');
		$this->setBaseUrl();
		$this->setItemParameter();
		$this->setItemListPageNavi();

		$entry = $this->getD3pipeEntries($this->category_id);

		if (count($entry) == 0) // 表示するデータ無し
		{
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		for($i=$this->itemListPageNavi->getStart(); $i<$this->total; $i++)
		{
			if ($i == $this->itemListPageNavi->getPerpage())
			{
				break;
			}

			$title = htmlspecialchars($entry[$i]['headline'], ENT_QUOTES);
			// 詳細リンク用パラメータ生成
			$url_parameter = $this->getBaseUrl().'&amp;'.$this->category_id_fld.'='.$this->category_id.'&amp;start='.$i;
			$date = intval($entry[$i]['pubtime']);
			$date = $this->utils->getDateLong($date);

			$number = $i + 1; // アクセスキー用の番号、1から開始
			$this->item_list[$i]['key'] = $number;
			$this->item_list[$i]['title'] = $this->adjustTitle($title);
			$this->item_list[$i]['url'] = $url_parameter;
			$this->item_list[$i]['date'] = $date;
			// 内容表示
			$description = '';
//			if ($entry[$i]['content_encoded'])
//			{
//				$description = $entry[$i]['content_encoded'];
//			}
			if (isset($entry[$i]['allow_html']))
			{
				if ($entry[$i]['allow_html'])
				{
					$description = $entry[$i]['description'];
				}
			}
			else
			{
				$description = htmlspecialchars(trim(nl2br($entry[$i]['description'])), ENT_QUOTES);
			}
			if ($description != '')
			{
				$this->item_list[$i]['content'] = $description;
//				$this->item_list[$i]['content'] = mb_strimwidth($description, 0, 100, '..', SCRIPT_CODE);
			}
			// 参照元URI
//			$this->item_list[$i]['link'] = htmlspecialchars($entry[$i]['link'], ENT_QUOTES);
//			$i++;
		}

		return $this->item_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// added for makinavi.jp
// 最新記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getRecentList()
	{
		global $xoopsModuleConfig;

		if($xoopsModuleConfig['show_recent_title'] == 0)
		{
			return false;
		}

		$this->setNextViewState('detail');
		$this->setBaseUrl();
		$this->setItemParameter();
		if(!is_null($this->item_date_fld))
		{
			$this->item_criteria->setSort($this->item_date_fld);
			$this->item_criteria->setOrder('DESC');
			$this->item_criteria->setLimit($xoopsModuleConfig['recent_title_row']);
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getRecentList criteria', $this->item_criteria->render());

		if(!$itemObjectArray = $this->getObjects($this->item_criteria))
		{
			$this->utils->setDebugMessage(__CLASS__, 'getRecentlist Error', $this->getErrors());
		}

		if(count($itemObjectArray) == 0) // 表示するデータ無し
		{
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		$recent_list = array();
		$i = 0;
		foreach($itemObjectArray as $itemObject)
		{
			$id = $itemObject->getVar($this->item_id_fld);
			$title = $itemObject->getVar($this->item_title_fld);
			$url_parameter = $this->getBaseUrl();

			if(!is_null($this->category_pid_fld) && !is_null($this->category_pid))
			{
				$url_parameter .= '&amp;'.$this->category_pid_fld.'='.$this->category_pid;
			}
			if(!is_null($this->category_id_fld) && ($this->item_cid_fld != $this->category_id_fld) && !is_null($this->category_id))
			{
				$url_parameter .= '&amp;'.$this->category_id_fld.'='.$this->category_id;
			}
			if(!is_null($this->item_cid_fld))
			{
				$cid = $itemObject->getVar($this->item_cid_fld);
				$url_parameter .= '&amp;'.$this->item_cid_fld.'='.$cid;
			}
			if(!is_null($this->item_id_fld))
			{
				$url_parameter .= '&amp;'.$this->item_id_fld.'='.$id;
			}
			$date = '';
			if(!is_null($this->item_date_fld))
			{
				$date = $itemObject->getVar($this->item_date_fld);
				$date = $this->utils->getDateShort($date).' '.$this->utils->getTimeLong($date);
			}

			$recent_list[$i]['title'] = $this->adjustTitle($title);
			$recent_list[$i]['url'] = $url_parameter;
			$recent_list[$i]['date'] = $date;
			$i++;
		}
		return $recent_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事詳細・コメント・編集用リンクの取得
// ただし、戻り値はオブジェクトではなくHTML
	function getItemDetail()
	{
		
		if ($this->item_id)
		{
			$itemDetail = $this->getD3pipeClipping($this->item_id);
			$this->setItemDetailPageNavi();
//			$page = $this->getItemPageFromID($this->item_id);
//			$_GET['start'] = $page;
		}
		else
		{
			$entry = $this->getD3pipeEntries($this->category_id);
			$start = intval($this->utils->getGetPost('start'));
			$itemDetail = $entry[$start];
		}

		if ($itemDetail == '') // 表示するデータ無し
		{
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		$detail4html = '';

		// タイトル
		if ($itemDetail['headline'] != '')
		{
			$detail4html .= _MD_XMOBILE_TITLE;
			$detail4html .= htmlspecialchars($itemDetail['headline'], ENT_QUOTES).'<br />';
		}
		// 日付・時刻
		$date = intval($itemDetail['pubtime']);
		$detail4html .= _MD_XMOBILE_DATE.$this->utils->getDateLong($date).' '.$this->utils->getTimeLong($date).'<br />';
		// 内容表示
//		if ($itemDetail['content_encoded'])
//		{
//			$description = $itemDetail['content_encoded'];
//		}
		$description = '';
		if (isset($itemDetail['allow_html']))
		{
			if ($itemDetail['allow_html'])
			{
				$description = $itemDetail['description'];
			}
		}
		else
		{
			$description = htmlspecialchars(trim(nl2br($itemDetail['description'])), ENT_QUOTES);
		}
		if ($description != '')
		{
			$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
			$detail4html .= $description.'<br />';
		}
		// 参照元URI
//		if ($itemDetail['link'] != '')
//		{
//			$detail4html .= htmlspecialchars($itemDetail['link'], ENT_QUOTES).'<br />';
//		}

		return $detail4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// get d3pipe clippings
	function getD3pipeClippingsFromPipeID($pipe_id)
	{
		if (!$pipe_id) return false;

		$configHandler =& xoops_gethandler('config');
		$moduleConfig =& $configHandler->getConfigsByDirname($this->moduleDir);
		include_once XOOPS_TRUST_PATH.'/modules/d3pipes/include/main_functions.php';
		$pos = 0;
		$clippings= d3pipes_main_get_clippings_moduledb($this->moduleDir, $pipe_id, $moduleConfig['entries_per_eachpipe'], $pos);
		return $clippings;
	}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getD3pipeEntriesCount($pipe_id)
	{
		$entries = $this->getD3pipeEntries($pipe_id);
		$count = count($entries);
//die(var_dump($count));
		return $count;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getD3pipeEntries($pipe_id)
	{
		if (!$pipe_id) return false;

		$configHandler =& xoops_gethandler('config');
		$moduleConfig =& $configHandler->getConfigsByDirname($this->moduleDir);
		include_once XOOPS_TRUST_PATH.'/modules/d3pipes/include/common_functions.php';

		$pipe4assign = d3pipes_common_get_pipe4assign($this->moduleDir, $this->category_id);
		$entries = d3pipes_common_fetch_entries($this->moduleDir ,$pipe4assign ,$moduleConfig['entries_per_eachpipe'] ,$errors ,$moduleConfig);
		return $entries;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// get d3pipe clipping
	function getD3pipeClipping($clipping_id)
	{
		if (!$clipping_id) return false;
		include_once XOOPS_TRUST_PATH.'/modules/d3pipes/include/common_functions.php';
		$clip= d3pipes_common_get_clipping($this->moduleDir, $clipping_id);
		return $clip;
	}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobileD3pipesPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct("'.$mydirname.'");
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobileD3pipesPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
