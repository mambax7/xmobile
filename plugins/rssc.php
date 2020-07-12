<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileRsscPlugin extends XmobilePlugin
{
	function XmobileRsscPlugin()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();
		// define object elements
		$this->initVar('fid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('lid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('uid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('mid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('p1', XOBJ_DTYPE_INT, '0', false);
		$this->initVar('p2', XOBJ_DTYPE_INT, '0', false);
		$this->initVar('p3', XOBJ_DTYPE_INT, '0', false);
		$this->initVar('site_title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('site_link', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('link', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('entry_id', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('guid', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('updated_unix', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('published_unix', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('category', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('author_name', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('author_uri', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('author_email', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('type_cont', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('raws', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('content', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('search', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('enclosure_url', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('enclosure_type', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('enclosure_length', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('aux_int_1', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('aux_int_2', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('aux_text_1', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('aux_text_2', XOBJ_DTYPE_TXTBOX, '', true, 255);

		// define primary key
		$this->setKeyFields(array('fid'));
		$this->setAutoIncrementField('fid');
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
class XmobileRsscPluginHandler extends XmobilePluginHandler
{
	var $moduleDir = 'rssc';
	var $categoryTableName = 'rssc_link';
	var $itemTableName = 'rssc_feed';
// category parameters
	var $category_id_fld = 'lid';
	var $category_title_fld = 'title';
//	var $category_order_fld = 'lid';
// item parameters
	var $item_id_fld = 'fid';
	var $item_cid_fld = 'lid';
	var $item_title_fld = 'title';
	var $item_description_fld = 'content';
	var $item_order_fld = 'published_unix';
	var $item_date_fld = 'published_unix';
	var $item_order_sort = 'DESC';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileRsscPluginHandler($db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
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
// アイテムデータ取得用criteriaの追加設定
// カテゴリID、ソートフィールド、ソート順の設定
/*
	function addItemCriteria()
	{
		parent::addItemCriteria();
		$lid = intval($this->utils->getGetPost('lid', 0));
		if ($lid != 0)
		{
			$this->item_criteria->add(new Criteria('lid', $lid));
		}
	}
*/
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
		$this->baseUrl = $this->utils->getLinkUrl('plugin',$this->nextViewState,'rssc',$this->sessionHandler->getSessionID());
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'setBaseUrl', $this->baseUrl);
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
//			return _MD_XMOBILE_NO_DATA;
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		$item_list = array();
		$i = 0;
		foreach($itemObjectArray as $itemObject)
		{
// rssフィード内でタグを有効にする
			$itemObject->assignSanitizerElement();

			$id = $itemObject->getVar($this->item_id_fld);
			$title = $itemObject->getVar($this->item_title_fld);
			// 詳細リンク用パラメータ生成
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

			$date = '';
			if (!is_null($this->item_date_fld))
			{
				//変更点
				$date = $itemObject->getVar($this->item_date_fld);
				$date = $this->utils->getDateShort($date);
			}

			$number = $i + 1; // アクセスキー用の番号、1から開始
			$item_list[$i]['key'] = $number;
			$item_list[$i]['title'] = $this->adjustTitle($title);
			$item_list[$i]['url'] = $url_parameter;
			$item_list[$i]['date'] = $date;
// 内容表示
			$item_list[$i]['content'] = mb_strimwidth($itemObject->getVar('content'), 0, 100, '..', SCRIPT_CODE);
// 参照元URI
			$item_list[$i]['link'] = $itemObject->getVar('link');
			$i++;
		}

		return $item_list;
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
		if (!is_null($this->item_title_fld))
		{
			$detail4html .= _MD_XMOBILE_TITLE;
			$detail4html .= $itemObject->getVar($this->item_title_fld).'<br />';
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
		// コメント
		if (!is_null($this->item_comments_fld))
		{
//			$detail4html .= _MD_XMOBILE_COMMENT.$itemObject->getVar($this->item_comments_fld).'<br />';
		}
		// 詳細
		$description = '';
		if (!is_null($this->item_description_fld))
		{
			$description = $itemObject->getVar($this->item_description_fld);
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


		$chstr = "^".XOOPS_URL."/modules/wordpress/index.php\?p";
		$repstr = XMOBILE_URL."/?act=plugin&plg=wordpress&author";
		$blog_link = ereg_replace($chstr, $repstr, $itemObject->getVar('link'));
		$detail4html .= '<hr /><a href="'.$blog_link.'">元の記事へのリンク</a><br />';

		// blogサイトのURL
//		if ($url !== '')
//		{
//			$detail4html .= 'url:&nbsp;'.$url.'<br />';
//		}


		return $detail4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
