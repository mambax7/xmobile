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
class Xmobile'.$Pluginname.'Plugin extends XmobileWordpressPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobileWordpressPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileWordpressPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('ID', XOBJ_DTYPE_INT, null, true);
		$this->initVar('post_author', XOBJ_DTYPE_INT, null, true);
		$this->initVar('post_date', XOBJ_DTYPE_TXTBOX, '0000-00-00 00:00:00', true, 20);
		$this->initVar('post_content', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('post_title', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('post_category', XOBJ_DTYPE_INT, null, true);
		$this->initVar('post_excerpt', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('post_lat', XOBJ_DTYPE_FLOAT, null, false);
		$this->initVar('post_lon', XOBJ_DTYPE_FLOAT, null, false);
		$this->initVar('post_status', XOBJ_DTYPE_TXTBOX, 'publish', false, 128);
		$this->initVar('comment_status', XOBJ_DTYPE_TXTBOX, 'open', false, 128);
		$this->initVar('ping_status', XOBJ_DTYPE_TXTBOX, 'open', false, 128);
		$this->initVar('post_password', XOBJ_DTYPE_TXTBOX, '', false, 20);
		$this->initVar('post_name', XOBJ_DTYPE_TXTBOX, '', false, 200);
		$this->initVar('to_ping', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('pinged', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('post_modified', XOBJ_DTYPE_TXTBOX, '0000-00-00 00:00:00', false, 20);
		$this->initVar('post_content_filtered', XOBJ_DTYPE_TXTAREA, '', false);

		// define primary key
		$this->setKeyFields(array('ID'));
		$this->setAutoIncrementField('ID');
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		global $xoopsDB;

// HTMLタグを無効にしたい場合は、$dohtml = 0; を $dohtml = 1; にして下さい
		$dohtml = 1;
		$dosmiley = 0;
		$doxcode = 0;

		$sql = 'SELECT option_id,option_name,option_value FROM '.$xoopsDB->prefix('wp_options');
		if ($ret = $xoopsDB->query($sql))
		{
			while($row = $xoopsDB->fetchArray($ret))
			{
				switch ($row['option_name'])
				{
// これはHTMLタグの設定項目じゃない？
//					case 'use_balanceTags':
//						$dohtml = $row['option_value'];
//						break;
					case 'use_smilies':
						$dosmiley = $row['option_value'];
						break;
					case 'use_bbcode':
						$doxcode = $row['option_value'];
						break;
				}
			}
		}

		$this->initVar('dohtml',XOBJ_DTYPE_INT,$dohtml);
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,$dosmiley);
		$this->initVar('doxcode',XOBJ_DTYPE_INT,$doxcode);
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileWordpressPluginHandlerAbstract extends XmobilePluginHandler
{
	var $template = 'xmobile_wordpress.html';
	var $moduleDir = 'wordpress';
	var $categoryTableName = 'wp_categories';
	var $itemTableName = 'wp_posts';
	var $post2catTableName = 'wp_post2cat';

	var $category_id_fld = 'cat_ID';
	var $category_pid_fld = 'category_parent';
	var $category_title_fld = 'cat_name';
	var $category_order_fld = 'cat_ID';

	var $item_id_fld = 'ID';
	var $item_cid_fld = 'post_category';
	var $item_title_fld = 'post_title';
	var $item_description_fld = 'post_content';
	var $item_order_fld = 'post_date';
	var $item_date_fld = 'post_date';
	var $item_uid_fld = 'post_author';
	var $item_hits_fld = null;
	var $item_comments_fld = null;
//	var $item_order_sort = null;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;

		if ( preg_match("/^\D+(\d*)$/", $mydirname,$matches) )
		{
			$number = $matches[1];
			$this->itemTableName = 'wp'.$number.'_posts';
			$this->categoryTableName = 'wp'.$number.'_categories';
			$this->post2catTableName = 'wp'.$number.'_post2cat';
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
		$this->item_criteria->add(new Criteria('post_status', 'publish'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function addItemCriteria()
	{
		if (!is_object($this->item_criteria))
		{
			return;
		}
		$post_id_array = array();
		if ($this->category_id)
		{
//			$sql = 'SELECT post_id,category_id FROM '.$this->db->prefix('wp_post2cat').' WHERE category_id='.$this->category_id;
			$sql = 'SELECT post_id,category_id FROM '.$this->db->prefix($this->post2catTableName).' WHERE category_id='.$this->category_id;
			if (!$ret = $this->db->query($sql))
			{
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'addItemCriteria sql', $sql);
				$this->utils->setDebugMessage(__CLASS__, 'addItemCriteria db error', $this->db->error());
			}
			else
			{
				while($data = $this->db->fetchArray($ret))
				{
					array_push($post_id_array,$data['post_id']);
				}
			}
			if (count($post_id_array) > 0)
			{
				$post_ids = join(',',$post_id_array);
				$this->item_criteria->add(new Criteria($this->item_id_fld, '('.$post_ids.')', 'IN'));
			}
			else
			{
				$this->item_criteria->add(new Criteria($this->item_id_fld, -1));
			}
		}

		if (!is_null($this->item_order_fld))
		{
			$this->item_criteria->setSort($this->item_order_fld);
		}
		global $xoopsModuleConfig;
		if (is_null($this->item_order_sort))
		{
			$this->item_order_sort = $xoopsModuleConfig['title_order_sort'];
		}
		$this->item_criteria->setOrder($this->item_order_sort);
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
			$date = $itemObject->getVar($this->item_date_fld);
			$detail4html .= _MD_XMOBILE_DATE.$date.'<br />';
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
		// ヒット数
		if (!is_null($this->item_hits_fld))
		{
			$hits = $itemObject->getVar($this->item_hits_fld);
			// ヒットカウントの増加
			$this->increaseHitCount($this->item_id);
		}
		// コメント
		if (!is_null($this->item_comments_fld))
		{
			$comments = $itemObject->getVar($this->item_comments_fld);
		}
		// 詳細
		$description = '';
		if (!is_null($this->item_description_fld))
		{
			$description = $itemObject->getVar($this->item_description_fld);
			$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
			$detail4html .= $description.'<br />';
		}
		return $detail4html;
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

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getList criteria', $this->item_criteria->render());

		$itemObjectArray = $this->getObjects($this->item_criteria);
		if (!$itemObjectArray)
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getList Error', $this->getErrors());
		}

		if (count($itemObjectArray) == 0) // 表示するデータ無し
		{
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
			if (!is_null($this->item_cid_fld))
			{
				$cid = $itemObject->getVar($this->item_cid_fld);
				$url_parameter .= '&amp;'.$this->item_cid_fld.'='.$cid;
			}
			if (!is_null($this->item_id_fld))
			{
				$url_parameter .= '&amp;'.$this->item_id_fld.'='.$id;
			}
			$date = $itemObject->getVar($this->item_date_fld);

			$number = $i + 1; // アクセスキー用の番号、1から開始
			$item_list[$i]['key'] = $number;
			$item_list[$i]['title'] = $this->adjustTitle($title);
			$item_list[$i]['url'] = $url_parameter;
			$item_list[$i]['date'] = $date;
			$i++;
		}
		return $item_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 最新記事一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getRecentList()
	{
		global $xoopsModuleConfig;

		if ($xoopsModuleConfig['show_recent_title'] == 0)
		{
			return false;
		}

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
			return false;
		}

		$recent_list = array();
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
			if (!is_null($this->item_cid_fld))
			{
				$cid = $itemObject->getVar($this->item_cid_fld);
				$url_parameter .= '&amp;'.$this->item_cid_fld.'='.$cid;
			}
			if (!is_null($this->item_id_fld))
			{
				$url_parameter .= '&amp;'.$this->item_id_fld.'='.$id;
			}
			$date = $itemObject->getVar($this->item_date_fld);

			$recent_list[$i]['title'] = $this->adjustTitle($title);
			$recent_list[$i]['url'] = $url_parameter;
			$recent_list[$i]['date'] = $date;
			$i++;
		}
		return $recent_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getCidFromId($id)
	{
		$sql = 'SELECT category_id FROM '.$this->db->prefix($this->post2catTableName).' WHERE post_id ='.$id;

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getCidFromId sql', $sql);

		$ret = $this->db->query($sql,0,1);
		if (!$ret)
		{
			return false;
		}

		list($cid) = $this->db->fetchRow($ret);
		return intval($cid);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getChildItemCountById($id)
	{
		$ids = intval($id);
		$idArray = $this->categoryTree->getAllChildId($ids);
		if (count($idArray) > 0)
		{
			$ids .= ',';
			$ids .= join(',',$idArray);
		}
		$sql = 'SELECT post_id FROM '.$this->db->prefix($this->post2catTableName).' WHERE category_id IN('.$ids.')';
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
