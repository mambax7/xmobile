<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileAction
{
	var $controller;
	var $utils = null;
	var $pluginHandler;
	var $sessionHandler;
	var $pageNavi;
	var $baseUrl = '';
	var $template = 'xmobile_contents.html';

	var $showLogin = 1;
	var $showBacktoMain = 1;
	var $isLogout = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileAction()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function prepare(&$controller, &$pluginHandler)
	{
		$this->controller = $controller;
		$this->pluginHandler = $pluginHandler;
		$this->sessionHandler =& $this->controller->getSessionHandler();
		$this->utils =& $this->controller->utils;
		$this->setBaseUrl();
		$this->setTemplate();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTemplate()
	{
		$this->controller->render->setTemplate($this->template);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setBaseUrl()
	{
		$this->baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'baseUrl', $this->baseUrl);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getBaseUrl()
	{
		return $this->baseUrl;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ヘッダー部、タイトル部、フッター部をセットし、
// リクエストに応じたメソッドの実行結果をボディ部にセット
	function execute()
	{
		// default : geDefaultView
		$method_name = 'get'.ucfirst($this->controller->getViewState()).'View';

		$this->setHeader();
		$this->setFooter();

		$this->checkSessionTimeOut();

		if (method_exists($this,$method_name))
		{
			$this->setBody($this->$method_name());
		}
		else
		{
//			trigger_error('View Method Not Exists');
//			die();
			$this->controller->render->redirectHeader(_MD_XMOBILE_NOT_EXIST,3);
			exit();
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'ViewMethodName', $method_name);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function executePluginView()
	{
		// if no perm
		if (!$pluginPerm = $this->pluginHandler->getModulePerm())
		{
			$body = _MD_XMOBILE_NO_PERM_MESSAGE.'<br />';
			return $body;
		}

		// default : getDefaultView
		$method_name = 'get'.ucfirst($this->controller->getViewState()).'View';
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'PluginMethodName', $method_name);

		if (method_exists($this->pluginHandler,$method_name))
		{
			$body = $this->pluginHandler->$method_name();
			return $body;
		}
		else
		{
//			trigger_error('Plugin Method Not Exists');
//			die();
			$this->controller->render->redirectHeader(_MD_XMOBILE_NOT_EXIST,3);
			exit();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getListView()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDetailView()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getEditView()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getConfirmView()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setHeader()
	{
		global $xoopsModuleConfig;
		$this->controller->render->template->assign('xmobile_url',XMOBILE_URL);
		$this->controller->render->template->assign('logo',$xoopsModuleConfig['logo']);
		$this->controller->render->template->assign('sitename',$xoopsModuleConfig['sitename']);
		$this->controller->render->template->assign('sitename_alt',strip_tags($xoopsModuleConfig['sitename']));
		$this->setTitle();
		$this->controller->render->setHeader();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setBody($body)
	{
		if (!$body)
		{
			$body = _MD_XMOBILE_NO_DATA;
		}
		$this->controller->render->template->assign('body',$body);
		$this->controller->render->setBody();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setFooter()
	{
		$this->setUserMenu();
		$this->controller->render->setFooter();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setUserMenu()
	{
		global $xoopsModuleConfig;

		$session_id = $this->sessionHandler->getSessionID();
		$uid = $this->sessionHandler->getUid();
		$show_register = false;

		// ログイン・ユーザメニュー
		if (is_object($this->sessionHandler->getUser()))
		{
			// 登録ユーザ用
			$is_user = true;
			// ログアウト
			$logout_url =  $this->utils->getLinkUrl('logout',null,null,$session_id);
			// アカウント情報
			$userinfo_url =  $this->utils->getLinkUrl('userinfo',null,null,$session_id);
			// プライベートメッセージ
			$privmessage_count = $this->utils->getPrivateMessage($uid);
			$pmessage_url =  $this->utils->getLinkUrl('pmessage',null,null,$session_id);
			if ($privmessage_count == 0)
			{
				$privmessage_state = _MD_XMOBILE_INBOX;
			}
			else
			{
				$privmessage_state = _MD_XMOBILE_INBOX.'('.sprintf(_MD_XMOBILE_NUMBER,$privmessage_count).')';
			}
			// イベント通知機能
			$notifications_url =  $this->utils->getLinkUrl('notifications',null,null,$session_id);

			$this->controller->render->template->assign('logout_url',$logout_url);
			$this->controller->render->template->assign('userinfo_url',$userinfo_url);
			$this->controller->render->template->assign('pmessage_url',$pmessage_url);
			$this->controller->render->template->assign('notifications_url',$notifications_url);
			$this->controller->render->template->assign('privmessage_state',$privmessage_state);
		}
		else
		{
			// ゲスト用
			$is_user = false;

			// パスワード紛失
			$lostpass_url =  $this->utils->getLinkUrl('lostpass',null,null,$session_id);
			// 新規ユーザ登録
			//XOOPSの新規ユーザ登録許可、かつ、xmobileモジュールの新規ユーザ登録許可の場合のみ表示
			$allow_register = false;
			if (preg_match("/^XOOPS Cube/",XOOPS_VERSION)) // XOOPS Cube 2.1x
			{
				$config_handler =& xoops_gethandler('config');
				$moduleConfig =& $config_handler->getConfigsByDirname('user');
				if (!empty($moduleConfig['allow_register']))
				{
					$allow_register = true;
				}
			}
			else // XOOPS 2.0x JP
			{
				$config_handler =& xoops_gethandler('config');
				$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);
				if (!empty($xoopsConfigUser['allow_register']))
				{
					$allow_register = true;
				}
			}
			if ($allow_register && $xoopsModuleConfig['allow_register'] == 1)
			{
				$register_url =  $this->utils->getLinkUrl('register',null,null,$session_id);
				$this->controller->render->template->assign('register_url',$register_url);
				$show_register = true;
			}
			// ログイン
			$login_url =  $this->utils->getLinkUrl('login',null,null,$session_id);
			$this->controller->render->template->assign('login_url',$login_url);
			$this->controller->render->template->assign('lostpass_url',$lostpass_url);
			$this->controller->render->template->assign('use_easy_login',$xoopsModuleConfig['use_easy_login']);
			// 簡単ログイン
			if ($xoopsModuleConfig['use_easy_login'])
			{
				$this->controller->render->template->assign('carrier',$this->sessionHandler->getCarrierForLogin());
//				$this->controller->render->template->assign('session_name',session_name());
//				$this->controller->render->template->assign('session_id',session_id());
//				$this->controller->render->template->assign('referer_url',XMOBILE_URL);
			}
		}

		$show_login = false;
		if ($this->showLogin && !$xoopsModuleConfig['access_level'] == 0)
		{
			$show_login = true;
			$this->controller->render->template->assign('is_timeoout',$this->sessionHandler->getTimeoutFlag());
		}
		$show_search = true;
		if (!is_object($this->sessionHandler->getUser()) && $xoopsModuleConfig['access_level'] == 1)
		{
			$show_search = false;
		}

		if ($this->controller->getActionState() == 'logout')
		{
			$main_url =  XMOBILE_URL;
		}
		else
		{
			$main_url =  $this->utils->getLinkUrl('default',null,null,$session_id);
		}
		$search_url =  $this->utils->getLinkUrl('search',null,null,null);
		$this->controller->render->template->assign('show_back2main',$this->showBacktoMain);
		$this->controller->render->template->assign('use_accesskey',$xoopsModuleConfig['use_accesskey']);
		$this->controller->render->template->assign('main_url',$main_url);
		$this->controller->render->template->assign('is_user',$is_user);
		$this->controller->render->template->assign('show_register',$show_register);
		$this->controller->render->template->assign('show_login',$show_login);
		$this->controller->render->template->assign('show_search',$show_search);
		$this->controller->render->template->assign('search_url',$search_url);
		$this->controller->render->template->assign('session_id',$session_id);

		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'showBacktoMain', $this->showBacktoMain);
//		$this->utils->setDebugMessage(__CLASS__, 'isLogout', $this->isLogout);
//		$this->utils->setDebugMessage(__CLASS__, 'showLogin', $this->showLogin);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkSessionTimeOut()
	{
		global $xoopsModuleConfig;
		// session time out
		if (!$xoopsModuleConfig['access_level'] == 0 && $this->sessionHandler->getTimeoutFlag())
		{
			$this->controller->render->redirectHeader(_MD_XMOBILE_TIME_OUT,5,XMOBILE_URL);
			exit();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setShowLogin($value)
	{
		$this->showLogin = intval($value);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setShowBacktoMain($value)
	{
		$this->showBacktoMain = intval($value);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setIsLogout($value)
	{
		$this->isLogout = intval($value);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
