<?php
// Special thanks
// hoshiyan
// <hoshiyan@hoshiba-farm.com>
// http://www.hoshiba-farm.com/

if (!defined('XOOPS_ROOT_PATH')) exit();

$mydirname = basename(__FILE__,'.php');
$Pluginname = ucfirst($mydirname);
if (!preg_match("/^\w+$/", $Pluginname))
{
	trigger_error('Invalid pluginName');
	exit();
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileTinydPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();
		// define object elements
		$this->initVar('storyid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('blockid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('text', XOBJ_DTYPE_TXTAREA, '', false);
		$this->initVar('visible', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('homepage', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('nohtml', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('nosmiley', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('nobreaks', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('nocomments', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('link', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('address', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('submenu', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('last_modified', XOBJ_DTYPE_INT, time(), true);
		$this->initVar('created', XOBJ_DTYPE_TXTBOX, '2001-1-1 00:00:00', false, 20);
		$this->initVar('html_header', XOBJ_DTYPE_TXTAREA, '', false);
		// define primary key
		$this->setKeyFields(array('storyid'));
		$this->setAutoIncrementField('storyid');
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		$dohtml = 1;
		$doxcode = 0;
		$dosmiley = 0;
		$dobr = 0;

		$this->initVar('dohtml',XOBJ_DTYPE_INT,$dohtml);
		$this->initVar('doxcode',XOBJ_DTYPE_INT,$doxcode);
		$this->initVar('dosmiley',XOBJ_DTYPE_INT,$dosmiley);
		$this->initVar('dobr',XOBJ_DTYPE_INT,$dobr);
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileTinydPluginHandlerAbstract extends XmobilePluginHandler
{
	var $template = 'xmobile_tinyd.html';
	var $itemTableName = 'tinycontent';

	var $item_id_fld = 'storyid';
	var $item_title_fld = 'title';
//	var $item_description_fld = 'text';
	var $item_order_fld = 'blockid';
//	var $item_date_fld = 'last_modified';
//	var $item_order_sort = 'ASC';

	var $modulePath = '';
	var $moduleUrl = '';
	var $show_list = 0;
	var $nocomments = 1;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);

		$this->moduleDir = $mydirname;

		if (preg_match("/^\D+(\d*)$/", $mydirname,$matches))
		{
			$number = $matches[1];
			$this->itemTableName = $this->itemTableName.$number;
		}

		$this->modulePath = XOOPS_ROOT_PATH.'/modules/'.$this->moduleDir;
		$this->moduleUrl = XOOPS_URL.'/modules/'.$this->moduleDir;

		include $this->modulePath.'/include/constants.inc.php';
		include $this->modulePath.'/class/tinyd.textsanitizer.php';

		if (!defined('MB_RENDER_FUNCTIONS_INCLUDED'))
		{
			define('MB_RENDER_FUNCTIONS_INCLUDED',1);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
		$this->item_criteria->add(new Criteria('visible',0,'<>'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemId()
	{
		if (isset($_GET[$this->item_id_fld]))
		{
			$this->item_id = intval($_GET[$this->item_id_fld]);
		}
		elseif (isset($_POST[$this->item_id_fld]))
		{
			$this->item_id = intval($_POST[$this->item_id_fld]);
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'item_id', $this->item_id);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function addItemCriteria()
	{
		parent::addItemCriteria();

		switch ($this->controller->getViewState())
		{
			case 'default':
				if (!$this->show_list)
				{
					$this->item_criteria->add(new Criteria('submenu',1));
				}
				else
				{
					if($this->moduleConfig['tc_display_pagenav'] == 2) // サブメニュー指定のあるコンテンツのみ表示する場合
					{
						$this->item_criteria->add(new Criteria('submenu',1));
					}
					if(isset($_GET['storyid']))
					{
						$this->item_id = intval($_GET['storyid']);
						$this->item_criteria->add(new Criteria('storyid',$this->item_id));
					}
					$this->item_criteria->add(new Criteria('homepage',1),'OR');
				}
				break;

			case 'detail':
				if ($this->moduleConfig['tc_display_pagenav'] == 2) // サブメニュー指定のあるコンテンツのみ表示する場合
				{
					$this->item_criteria->add(new Criteria('submenu',1));
				}
				if(isset($_GET[$this->item_id_fld]))
				{
					$this->item_id = intval($_GET[$this->item_id_fld]);
					$this->item_criteria->add(new Criteria($this->item_id_fld, $this->item_id));
				}
				$this->item_criteria->add(new Criteria('homepage',1),'OR');
				break;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getItemExtraArg()
	{
		$item_extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		return $item_extra_arg;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemDetailPageNavi()
	{
		$criteria =& new CriteriaCompo();
		$criteria->add(new Criteria('visible',0,'<>'));
		if ($this->moduleConfig['tc_display_pagenav'] == 2)// サブメニュー指定のあるコンテンツのみ表示する場合
		{
			$criteria->add(new Criteria('submenu',1));
		}
		$criteria->add(new Criteria('homepage',1),'OR');

		$total = $this->getCount($criteria);
		if (!is_null($this->item_id))
		{
			$page = $this->getItemPageFromID($this->item_id);
			$_GET['start'] = $page;
		}
		$this->itemDetailPageNavi =& new XmobilePageNavigator($total, 1, 'start', $this->getItemExtraArg());
		$this->item_criteria->setLimit($this->itemDetailPageNavi->getPerpage());
		$this->item_criteria->setStart($this->itemDetailPageNavi->getStart());

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi criteria', $criteria->render());
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi Limit', $this->itemDetailPageNavi->getPerpage());
		$this->utils->setDebugMessage(__CLASS__, 'setItemDetailPageNavi Start', $this->itemDetailPageNavi->getStart());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		$this->show_list = 0;
		$this->controller->render->template->assign('item_list',$this->getItemList());
		// 表示するデータはありませんのメッセージ表示を抑止
		$this->controller->render->template->assign('lang_no_item_list', '');
		$this->show_list = 1;
		$this->controller->render->template->assign('item_detail',$this->getItemDetail());
		// ページナビゲーション
		if (!is_null($this->itemDetailPageNavi) && $this->moduleConfig['tc_display_pagenav'])
		{
			$this->controller->render->template->assign('item_detail_page_navi',$this->itemDetailPageNavi->renderNavi());
		}

		// コメント
		if ($this->nocomments == 0)
		{
			// com_opはコメント一覧・投稿画面で記事本文の表示を制御する為に必要
			$this->controller->render->template->assign('comment_link',$this->getCommentLink($this->item_id));
			$com_op = htmlspecialchars($this->controller->utils->getGetPost('com_op', ''), ENT_QUOTES);
			$this->controller->render->template->assign('com_op',$com_op);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDetailView()
	{
		$this->setBaseUrl();
		$this->setCategoryParameter();

		$this->controller->render->template->assign('item_detail',$this->getItemDetail());
		// ページナビゲーション
//		if (!is_null($this->itemDetailPageNavi) && $this->moduleConfig['tc_display_pagenav'] && $this->controller->getViewState() != 'default')
		if (!is_null($this->itemDetailPageNavi) && $this->moduleConfig['tc_display_pagenav'])
		{
			$this->controller->render->template->assign('item_detail_page_navi',$this->itemDetailPageNavi->renderNavi());
		}

		// コメント
		if ($this->nocomments == 0)
		{
			// com_opはコメント一覧・投稿画面で記事本文の表示を制御する為に必要
			$this->controller->render->template->assign('comment_link',$this->getCommentLink($this->item_id));
			$com_op = htmlspecialchars($this->controller->utils->getGetPost('com_op', ''), ENT_QUOTES);
			$this->controller->render->template->assign('com_op',$com_op);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getItemDetail()
	{
		$this->setItemParameter();
		$this->setItemDetailPageNavi();

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getDetailView criteria', $this->item_criteria->render());
		if (!$itemObjectArray = $this->getObjects($this->item_criteria))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getDetailView Error', $this->getErrors());
		}

		if (count($itemObjectArray) == 0)
		{
			return false;
		}

		$itemObject = $itemObjectArray[0];
		$itemObject->assignSanitizerElement();

		// 記事毎のコメント使用許可
		$this->nocomments = $itemObject->getVar('nocomments');

		$detail4html = '';
		if ($this->controller->getViewState() != 'default')
		{
			$detail4html .= $this->getCatPathFromId($this->category_id);
		}
//		$detail4html .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
		$this->item_id = $itemObject->getVar($this->item_id_fld);
		$url_parameter = $this->getBaseUrl();
		$itemObject->assignSanitizerElement();
		$title = $itemObject->getVar($this->item_title_fld);
		$detail4html .= $title.'<hr />';
		$detail4html .= $this->getContent($itemObject);
		return $detail4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// コメント用リンクの取得
	function getCommentLink($id)
	{
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/Comments.class.php';
		$xmobile_comment =& new XmobileComments($this->controller,$this,$id,0,0);
		$comment_link = $xmobile_comment->makeCommentLink();
		return $comment_link;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getContent($itemObject)
	{
		if (!is_object($itemObject))
		{
			return false;
		}

		$myts = &MyTextSanitizer::getinstance();

		$htmlContent = '';

		if ($itemObject->getVar('link') == 0) // 外部ファイル参照型でない場合
		{
//			$htmlContent .= $itemObject->getVar('text');
			$htmlContent .= $this->contentRender($itemObject->getVar('text'), $itemObject->getVar('nohtml'), $itemObject->getVar('nosmiley'), $itemObject->getVar('nobreaks'), $this->moduleConfig['tc_space2nbsp']);
		}
		else // 外部ファイル参照型の場合
		{
			// コンテンツを定義したファイルを参照し、文字コードを変換して、必要ならコンテンツ内のURLのパスの部分を書換える。
			$wrap_file = $this->modulePath.'/content/'.$itemObject->getVar('address');
			if (!file_exists($wrap_file))
			{
				$htmlContent .= '指定されたファイル'.$wrap_file.'がありません。';
			}
			else
			{
				$contents_html = file_get_contents($wrap_file, true);

				if ($contents_html === false)
				{
					$htmlContent .= '指定されたファイル'.$wrap_file.'が読めません。';
				}
				else
				{
//					if (!defined('FOR_XOOPS_LANG_CHECKER'))
//					{
//						$contents_html = mb_convert_encoding($contents_html,mb_internal_encoding(),'auto');
//					}

					if ($itemObject->getVar('link') == TC_WRAPTYPE_CONTENTBASE)// ラップしたページと同じディレクトリ基点
					{
					}
					elseif ($itemObject->getVar('link') == TC_WRAPTYPE_USEREWRITE)// mod_rewriteによる書き換え
					{
					}
					elseif ($itemObject->getVar('link') == TC_WRAPTYPE_CHANGESRCHREF ) // HTMLタグ書き換え
					{
						$contents_html = $this->changeSrchref($contents_html,$this->moduleUrl.'/content');
					}
				}

				$contents_html = str_replace('{X_SITEURL}', XOOPS_URL, $contents_html);
				$htmlContent .= $contents_html;
			}
		}
		return $htmlContent;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function contentRender($text,$nohtml,$nosmiley,$nobreaks,$nbsp = 0)
	{
		$myts =& TinyDTextSanitizer::getInstance();

		if ($nohtml >= 16)
		{
			// db content (PEAR wiki)
			if (!defined('PATH_SEPARATOR')) define('PATH_SEPARATOR',DIRECTORY_SEPARATOR == '/' ? ':' : ';');
			ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.XOOPS_ROOT_PATH.'/common/PEAR');
			include_once 'Text/Wiki.php';
			// include_once "Text/sunday_Wiki.php";
			$wiki = new Text_Wiki(); // create instance

			// Configuration
			$wiki->deleteRule('Wikilink'); // remove a rule for auto-linking
			$wiki->setFormatConf('Xhtml','translate',false); // remove HTML_ENTITIES

			// $wiki = new sunday_Text_Wiki(); // create instance
			//$text = str_replace ( "\r\n", "\n", $text );
			//$text = str_replace ( "~\n", "[br]", $text );
			//$text = $wiki->transform($text);
			//$content = str_replace ( "[br]", "<br/>", $text );
			// special thx to minahito! you are great!!
			$content = $wiki->transform($text);

			if ($nohtml & 2 )
			{
				$content = $myts->displayTarea($content, 1, !$nosmiley, 1, 1, !$nobreaks, $nbsp );
			}
		}
		else if ($nohtml >= 8)
		{
			// db content (PHP)
			ob_start() ;
			eval( $text ) ;
			$content = ob_get_contents();
			ob_end_clean() ;

			if ($nohtml & 2)
			{
				$content = $myts->displayTarea( $content, 1, !$nosmiley, 1, 1, !$nobreaks, $nbsp );
			}
		}
		else if ($nohtml < 4)
		{
//			echo "go...".$nohtml;
			switch ($nohtml)
			{
				case 0 : // HTML with BB
					$content = $myts->displayTarea( $text, 1, !$nosmiley, 1, 1, !$nobreaks, $nbsp );
					break ;
				case 1 : // Text with BB
					$content = $myts->displayTarea( $text, 0, !$nosmiley, 1, 1, !$nobreaks, $nbsp );
					break ;
				case 2 : // HTML without BB
//					$content = '<pre>'.$text.'</pre>';
					$content = $myts->displayTarea($text, 1, !$nosmiley, 0, 1, !$nobreaks, $nbsp );
					break ;
				case 3 : // Text without BB
					$content = $myts->makeTboxData4Show( $text );
					break ;
			}
		}
		else
		{
			$content = $text;
		}
	//echo "in=".$text;
	//echo "out=".$content;
		return $content;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function changeSrchref($content, $wrap_base_url)
	{
		$patterns = array("/src\=\"(?!http:|https:)([^, \r\n\"\(\)'<>]+)/i", "/src\=\'(?!http:|https:)([^, \r\n\"\(\)'<>]+)/i", "/src\=(?!http:|https:)([^, \r\n\"\(\)'<>]+)/i", "/href\=\"(?!http:|https:)([^, \r\n\"\(\)'<>]+)/i", "/href\=\'(?!http:|https:)([^, \r\n\"\(\)'<>]+)/i", "/href\=(?!http:|https:)([^, \r\n\"\(\)'<>]+)/i");
		$replacements = array("src=\"$wrap_base_url/\\1", "src='$wrap_base_url/\\1", "src=$wrap_base_url/\\1", "href=\"$wrap_base_url/\\1", "href='$wrap_base_url/\\1", "href=$wrap_base_url/\\1");

		return preg_replace($patterns, $replacements, $content);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*
	if ( ! function_exists( 'mb_convert_encoding' ) ) {
		function mb_convert_encoding( $str ) { return $str ; }
	}

	if ( ! function_exists( 'mb_internal_encoding' ) ) {
		function mb_internal_encoding( $str ) { return "UTF-8" ; }
	}


	}

	if ( ! defined( 'FOR_XOOPS_LANG_CHECKER' ) && ! function_exists( 'mobile_tc_convert_wrap_to_ie' ) ) {
		function mobile_tc_convert_wrap_to_ie( $str ) {
			return mb_convert_encoding( $str , mb_internal_encoding() , "auto" ) ;
		}
	}
*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobileTinydPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobileTinydPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
