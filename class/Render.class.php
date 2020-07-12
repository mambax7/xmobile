<?php
// HTML出力用クラス
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileRender
{
	var $controller;
	var $pageSessionHandler;

	var $headerTemplateName = 'xmobile_header.html';
	var $contentsTemplateName = '';
	var $footerTemplateName = 'xmobile_footer.html';
	var $template = null;
	var $header = '';
	var $title = '';
	var $body = '';
	var $footer = '';
	var $debugMessage = '';
	var $outPut = '';

	var $headerStrLen = 0;
	var $contentsStrLen = 0;
	var $footerStrLen = 0;

	var $maxDataSize;
	var $dataSize;
	var $hasPage = 0;
	var $session_id = '';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileRender()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function &getInstance()
	{
		static $instance;
		if (!isset($instance)) 
		{
			$instance = new XmobileRender();
		}
		return $instance;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function prepare(&$controller)
	{
		$this->controller = $controller;
		require_once XOOPS_ROOT_PATH.'/class/template.php';
		require_once SMARTY_DIR.'Smarty.class.php';
		$this->template =& new XoopsTpl();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// コンテンツ出力用テンプレートの指定
// アクションに応じて、各アクションクラスで指定
// 指定しない場合はxmobile_contents.htmlを使用
	function setTemplate($template_name)
	{
		$this->contentsTemplateName = $template_name;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 以下、未実装です
// PC（携帯端末以外）用画面出力処理
//  XOOPS_ROOT_PATH.'/header.php'、/templates/xmobile_index.html（初期設定では空）、XOOPS_ROOT_PATH.'/footer.php'を読み込む
// 必要に応じてカスタマイズして下さい。
	function displayforpc()
	{
		global $xoopsOption, $xoopsConfig, $xoopsUserIsAdmin, $xoopsUser, $xoopsLogger;
		$xoopsOption['template_main'] = 'xmobile_index.html';
		include_once XOOPS_ROOT_PATH.'/header.php';
		include_once XOOPS_ROOT_PATH.'/footer.php';
//		exit();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 画面出力処理
// 必要に応じて文字エンコードを変換後表示
	function display()
	{
		$this->outPut = $this->outPut.$this->debugMessage.'</body></html>';

		if (SCRIPT_CODE != HTML_CODE)
		{
			$this->outPut = mb_convert_encoding($this->outPut,HTML_CODE,SCRIPT_CODE);
		}

//携帯用絵文字変換処理
		require_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/Emoji.class.php';
		$mh =& new XmobileEmoji();
		$carrier = $this->controller->sessionHandler->getCarrierByAgent();
		$this->outPut = $mh->convertStr($this->outPut, $carrier);

		echo $this->outPut;

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 出力用データ設定
	function setOutPut()
	{
		if (!$this->checkDataSize())
		{
			$this->body = $this->splitPage();
		}
		$this->outPut = $this->header.$this->body.$this->footer;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// タグの置換
	function removeIntactTag($text_data)
	{

		return $text_data;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// getthumbpath.phpを使用して画像のサムネイルを生成して置換
// 外部リンク画像はそのまま出力
	function replaceImage($text_data)
	{
		global $xoopsModuleConfig;
		if ($xoopsModuleConfig['use_thumbnail'])
		{
			require_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/getthumbpath.php';

			$xoops_url = preg_replace('/\//','\\\/',XOOPS_URL);
			$imgstr = '/<img src="'.$xoops_url.'([^"">]*)[""][^>]*>/';

// debug
//			if (preg_match($imgstr, $text_data))
//			{
//				$this->controller->utils->setDebugMessage(__CLASS__, 'replaceImage', 'True');
//			}

			$text_data = preg_replace_callback($imgstr, 'getthumbpath', $text_data);
		}
		return $text_data;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// サイト内へのリンクでxmobileで表示可能な場合はuriを置換する。
	function replaceUri($text_data)
	{
		global $xoopsModuleConfig;
		if ($xoopsModuleConfig['replace_link'])
		{
			require_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/replaceuri.php';

			$xoops_url = preg_replace('/\//','\\\/',XOOPS_URL);
			$uristr = '/<a href="('.$xoops_url.'[^">]*)"/';

// debug
//			if (preg_match($uristr, $text_data))
//			{
//				$this->controller->utils->setDebugMessage(__CLASS__, 'replaceUri', 'True');
//			}

			$text_data = preg_replace_callback($uristr, 'replaceURI', $text_data);
		}
		return $text_data;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 出力データの文字数のチェック
	function checkDataSize()
	{
		global $xoopsModuleConfig;

		if ($xoopsModuleConfig['max_data_size'] == 0)
		{
			$this->controller->utils->setDebugMessage(__CLASS__, 'checkDataSize', 'True');
			return true;
		}
		$this->maxDataSize = $xoopsModuleConfig['max_data_size'] * 1000;
		$this->dataSize = $this->headerStrLen + $this->bodyStrLen + $this->footerStrLen;

		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'maxDataSize', $this->maxDataSize);
		$this->controller->utils->setDebugMessage(__CLASS__, 'dataSize', $this->dataSize);

		if ($this->maxDataSize > $this->dataSize)
		{
			$this->controller->utils->setDebugMessage(__CLASS__, 'checkDataSize', 'True');
			return true;
		}
		else
		{
			$this->controller->utils->setDebugMessage(__CLASS__, 'checkDataSize', 'False');
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 出力データの文字数制限が設定されている場合は
// 制限値で分割
	function splitPage()
	{
		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'splitPage', 'True');
		$splitedContent4html = '';

		$exclusion_data_size = $this->headerStrLen + $this->footerStrLen;
		$contents_limit_size = $this->maxDataSize - $exclusion_data_size;

		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'contents_limit_size', $contents_limit_size);

		$html_split_array = preg_split('/<br \/>/i',$this->body,-1,PREG_SPLIT_NO_EMPTY);
		$split_page = 1;
		$save_html_array = array();
		$save_html_array[$split_page] = '';
		$split_str_len = 0;
		$save_str_len = 0;
		$check_str_len = 0;

		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'count html_split_array', count($html_split_array));

		foreach($html_split_array as $split_str)
		{
			$split_str_len = strlen($split_str);
			$save_str_len = strlen($save_html_array[$split_page]);
			$check_str_len = $save_str_len + $split_str_len;

			if ($check_str_len > $contents_limit_size)
			{
					++$split_page;
					$save_html_array[$split_page] = $split_str.'<br />';
			}
			else
			{
				$save_html_array[$split_page] .= $split_str.'<br />';
			}
		// debug
//			$this->controller->utils->setDebugMessage(__CLASS__, 'split_str_len', $split_str_len);
//			$this->controller->utils->setDebugMessage(__CLASS__, 'check_str_len', $check_str_len);
//			$this->controller->utils->setDebugMessage(__CLASS__, 'page', $split_page);
		}


		$request_page = intval($this->controller->utils->getGet('pag',1));

		$html_output = $save_html_array[$request_page];
		$max_page = $split_page;

		if ($max_page > 1)
		{
			$html_output .= $this->getPageNavi($request_page, $max_page, $this->session_id);
		}

		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'max_page', $max_page);

		return $html_output;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// リダイレクト用画面表示
	function redirectHeader($message='',$interval=3,$baseUrl='')
	{
		global $xoopsModuleConfig,$xoopsConfig;
//		$message = htmlspecialchars($message, ENT_QUOTES);
		$interval = intval($interval);
		if ($baseUrl == '')
		{
			$baseUrl = $this->controller->utils->getLinkUrl('default',null,null,$this->controller->sessionHandler->getSessionID());
		}
		elseif (preg_match("/[\\0-\\31]/", $baseUrl) || preg_match("/^(javascript|vbscript|about):/i", $baseUrl))
		{
			$baseUrl = XMOBILE_URL;
		}
		else
		{
			$baseUrl = preg_replace('/&amp;/i', '&',htmlspecialchars($baseUrl, ENT_QUOTES));
		}

		header('Content-Type:text/html; charset='.CHARA_SET.'');
		$this->outPut = '';
		$this->outPut .= '<?xml version="1.0"?>'.PHP_EOL;
		$this->outPut .= '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
		$this->outPut .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._LANGCODE.'">';
		$this->outPut .= '<head><meta http-equiv="content-type" content="text/html; charset='.CHARA_SET.'" />';
		$this->outPut .= '<meta http-equiv="refresh" content="'.$interval.'; url='.$baseUrl.'" />';
		$this->outPut .= '<title>'.$xoopsConfig['sitename'].'</title>';
		
		$this->outPut .= '<link rel="stylesheet" type="text/css" media="all" href="'.XMOBILE_URL.'/style.css" />';
		$this->outPut .= '</head><body>';

		$this->outPut .= '<div class="header">';
		if ($xoopsModuleConfig['logo'] != '')
		{
			$this->outPut .= '<div class="logo"><img src="'.$xoopsModuleConfig['logo'].'" alt="'.strip_tags($xoopsModuleConfig['sitename']).'" /></div>';
		}
		$this->outPut .= '<div class="sitename">'.$xoopsModuleConfig['sitename'].'<hr /></div>';
		$this->outPut .= '</div>';
		$this->outPut .= '<div class="contents">'.$message.'</div>';
		$this->outPut .= $this->footer;

		$this->display();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Headerのセット
	function setHeader()
	{
		global $xoopsModuleConfig,$xoopsConfig;

		header('Content-Type:text/html; charset='.CHARA_SET.'');
// xhtml 1.0 transitional
//		$this->header .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
//		$this->header .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._LANGCODE.'" lang="'._LANGCODE.'">';

// xhtml mobile profile
		$this->header = '';
		$this->header .= '<?xml version="1.0"?>'.PHP_EOL;
		$this->header .= '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
		$this->header .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._LANGCODE.'">';
		$this->header .= '<head><meta http-equiv="content-type" content="text/html; charset='.CHARA_SET.'" />';
		$this->header .= '<title>'.$xoopsConfig['sitename'].' - '.$this->title.'</title>';
		$this->header .= '<link rel="stylesheet" type="text/css" media="handheld,tty,screen,projection" href="'.XMOBILE_URL.'/style.css" />';
		$this->header .= '</head><body>';


		$this->header .= $this->template->fetch('db:'.$this->headerTemplateName, null, null, false);
		$this->headerStrLen = strlen($this->header);
		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'headerStrLen', $this->headerStrLen);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Titleのセット
	function setTitle($title)
	{
		$this->title = $title;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Bodyのセット
	function setBody()
	{
//		$this->body = $this->template->fetch('db:'.$this->contentsTemplateName, null, null, false);
		$this->body = $this->template->fetch('file:'.XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/templates/'.$this->contentsTemplateName, null, null, false);
		$this->body = $this->replaceImage($this->body);
		$this->body = $this->replaceUri($this->body);
		$this->bodyStrLen = strlen($this->body);
		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'TemplateName', $this->contentsTemplateName);
		$this->controller->utils->setDebugMessage(__CLASS__, 'bodyStrLen', $this->bodyStrLen);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Footerのセット
	function setFooter()
	{
		$this->footer = $this->template->fetch('db:'.$this->footerTemplateName, null, null, false);
//		$this->footer .= '</body></html>';
		$this->footerStrLen = strlen($this->footer);
		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'footerStrLen', $this->footerStrLen);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// DebugMessageのセット
	function setDebugMessage($debugMessage)
	{
		$this->debugMessage = '<hr />'.$debugMessage;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// データ分割表示用ページナビゲーション
	function getPageNavi($request_page, $max_page, $session_id)
	{
		$extra = htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES);
//		$extra = preg_replace('/&amp;/i', '&', $extra);
		$base_url = XMOBILE_URL.'?'.$extra;
		$base_url = preg_replace('/&pag=\d*/', '', $base_url);

		$pageNavi = '<hr />';

		if ($request_page > 1)
		{
			$previous_page = $request_page - 1;
			$pageNavi .= _MD_XMOBILE_PAGESIZELIMIT_OVER.'<br />';
			$ext = '&pag='.$previous_page;
			$pageNavi .= '[<a href="'.$base_url.$ext.'">'._MD_XMOBILE_PREV_PAGE.'</a>]';
			$pageNavi .= '&nbsp;&nbsp;';
		}
		else
		{
			$pageNavi .= _MD_XMOBILE_PAGESIZELIMIT_OVER.'<br />';
		}

		if ($request_page < $max_page)
		{
			$next_page = $request_page + 1;
			$base_url = preg_replace('/&pag=(\d)*/', '&pag='.$next_page, $base_url);

			$ext = '&pag='.$next_page;
			$pageNavi .= '[<a href="'.$base_url.$ext.'">'._MD_XMOBILE_NEXT_PAGE.'</a>]';
		}

		return $pageNavi;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>