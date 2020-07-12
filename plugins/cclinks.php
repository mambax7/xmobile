<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileCclinksPlugin extends XmobilePlugin
{
	function XmobileCclinksPlugin()
	{
		// call parent constructor
//		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('LinkID', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('MyLinkID', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('EntryDT', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('LinkTitle', XOBJ_DTYPE_TXTBOX, '', true, 150);
		$this->initVar('LinkDesc', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('URL', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('Impression', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('CatID', XOBJ_DTYPE_INT, '0', true);
//		$this->initVar('CatTitle', XOBJ_DTYPE_TXTBOX, '', true, 255);

		// define primary key
		$this->setKeyFields(array('LinkID'));
		$this->setAutoIncrementField('LinkID');
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileCclinksPluginHandler extends XmobilePluginHandler
{
	var $template = 'xmobile_cclinks.html';
	var $moduleDir = 'cclinks';
	var $categoryTableName = 'cclinks_cats';
	var $itemTableName = 'cclinks_links';

	var $category_id_fld = 'CatID';
	var $category_pid_fld = 'ParentID';
	var $category_title_fld = 'CatTitle';
	var $category_order_fld = 'CatSort';

	var $item_id_fld = 'LinkID';
//	var $item_cid_fld = 'CatID';
	var $item_title_fld = 'LinkTitle';
	var $item_description_fld = 'LinkDesc';
	var $item_order_fld = ' EntryDT';
	var $item_date_fld = 'EntryDT';
	var $item_hits_fld = 'Impression';
//	var $item_order_sort = 'DESC';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileCclinksPluginHandler($db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();

		if ($this->category_id != 0)
		{
			$lids = $this->getLidsFromCid($this->category_id);
			$this->item_criteria->add(new criteria($this->item_id_fld,$lids,'IN'));
		}

		if (!is_null($this->item_order_fld))
		{
			$this->item_criteria->setSort($this->item_order_fld);
		}
		if (!is_null($this->item_order_sort))
		{
			$this->item_criteria->setOrder($this->item_order_sort);
		}
	}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 最新記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getRecentList()
	{
		global $xoopsModuleConfig;

		$this->setNextViewState('detail');
		$this->setBaseUrl();
		$this->setItemParameter();
		if (!is_null($this->item_date_fld))
		{
			$this->item_criteria->setSort($this->item_date_fld);
			$this->item_criteria->setOrder('DESC');
			$this->item_criteria->setLimit($xoopsModuleConfig['recent_title_row']);
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getRecentList criteria', $this->item_criteria->render());
		if (!$itemObjectArray = $this->getObjects($this->item_criteria))
		{
			$this->utils->setDebugMessage(__CLASS__, 'getRecentlist Error', $this->getErrors());
		}

		if (count($itemObjectArray) == 0) // 表示するデータ無し
		{
			$this->utils->setDebugMessage(__CLASS__, '表示するデータは？', 'なし');
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}
		$this->utils->setDebugMessage(__CLASS__, '表示するデータは？', 'あり');

		$recent_list = array();
// Notice [PHP]: Undefined variable: itemObject
//		$this->utils->setDebugMessage(__CLASS__, 'itemObject:',  $itemObject);

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
			$url_parameter .= '&amp;'.$this->category_id_fld.'='.$this->getCidFromId($id);
//			if (!is_null($this->item_cid_fld))
//			{
//				$cid = $itemObject->getVar($this->item_cid_fld);
//				$url_parameter .= '&amp;'.$this->item_cid_fld.'='.$cid;
//			}
			if (!is_null($this->item_id_fld))
			{
				$url_parameter .= '&amp;'.$this->item_id_fld.'='.$id;
			}
			$date = '';
			if (!is_null($this->item_date_fld))
			{
				// 変更点
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
		global $xoopsConfig;


		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemDetail criteria', $this->item_criteria->render());
		// 一意のidではなくcriteriaで検索する為、オブジェクトの配列が返される
		if (!$itemObjectArray = $this->getObjects($this->item_criteria))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getItemDetail Error', $this->getErrors());
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

		$title = $itemObject->getVar($this->item_title_fld);
		if (!is_null($this->item_date_fld))
		{
			$date = $itemObject->getVar($this->item_date_fld);
		}
		if (!is_null($this->item_hits_fld))
		{
			$hits = $itemObject->getVar($this->item_hits_fld);
			// ヒットカウントの増加
			$this->increaseHitCount($this->item_id);
		}

		$description = $itemObject->getVar($this->item_description_fld);
		$url = $itemObject->getVar('URL');

		$detail4html = '';
		$detail4html .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
		// タイトル
		$detail4html .= _MD_XMOBILE_TITLE.$title.'<br />';
		// WebサイトのURL
		if ($url !== '')
		{
			$detail4html .= 'URL:&nbsp;<a href="'.$url.'">'.$url.'</a><br />';
		}
		// 説明
		if ($description !== '')
		{
			$detail4html .= _MD_XMOBILE_DESCRIPTION.'<br />';
			$detail4html .= $description.'<br />';
		}
		return $detail4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getLidsFromCid($cid)
	{
		global $xoopsModuleConfig;

		$cid = intval($cid);
		$sql = 'SELECT MyLinkID FROM '.$this->db->prefix('cclinks_data').' WHERE MyCatID = '.$cid;

		$this->utils->setDebugMessage(__CLASS__, 'getLidFromCid sql', $sql);

		$ret = $this->db->query($sql);
		$count = $this->db->getRowsNum($ret);
		$result = $this->db->query($sql);
		if ($count == 0)
		{
			return false;
		}

		$lids = '(';
		while(list($lid) = $this->db->fetchRow($result))
		{
			$lid = intval($lid);
			$lids .= ','.$lid;
		}
		$lids .= ')';
		$lids = preg_replace('/^\(,/','(',$lids);
		return $lids;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getCidFromId($lid)
	{
		global $xoopsModuleConfig;

		$lid = intval($lid);

		$sql = 'SELECT MyCatID FROM '.$this->db->prefix('cclinks_data').' WHERE MyLinkID = '.$lid;

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getCidFromId sql', $sql);

//		$ret = $this->db->query($sql);
		$ret = $this->db->query($sql,0,1);
		if (!$ret)
		{
			return false;
		}
		while(list($cid) = $this->db->fetchRow($ret))
		{
			return intval($cid);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setCategoryParentId()
	{
		if (is_null($this->category_pid_fld)) return;

		$this->category_pid = intval($this->utils->getGetPost($this->category_pid_fld, 0));

		$this->utils->setDebugMessage(__CLASS__, 'category_pid', $this->category_pid);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getChildItemCountById($id)
	{
		global $xoopsModuleConfig;

		$ids = intval($id);
		$idArray = $this->categoryTree->getAllChildId($ids);
		if (count($idArray) > 0)
		{
			$ids .= ',';
			$ids .= join(',',$idArray);
		}
		$sql = 'SELECT MyCatID FROM '.$this->db->prefix('cclinks_data').' WHERE MyCatID IN('.$ids.')';
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemCountById sql', $sql);

		$ret = $this->db->query($sql);
		if ($ret)
		{
			$itemCount = $this->db->getRowsNum($ret);
			$this->utils->setDebugMessage(__CLASS__, 'getItemCountById count', $itemCount);
			return $itemCount;
		}
		else
		{
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
