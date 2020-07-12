<?php
// ログイン画面
//
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class LoginAction extends XmobileAction
{
	var $template = 'xmobile_login.html';
	var $showLogin = 0;
	var $loginError = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function LoginAction()
	{
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/gtickets.php';
		$this->ticket = new XoopsGTicket;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_MD_XMOBILE_LOG_IN);
		$this->controller->render->template->assign('page_title',_MD_XMOBILE_LOG_IN);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		global $xoopsModuleConfig;
		if ($xoopsModuleConfig['login_terminal'] != 2 && $this->sessionHandler->getCarrierForLogin() == 0)
		{
			$base_url = $this->controller->utils->getLinkUrl('default',null,null,$this->sessionHandler->getSessionId());
			$this->controller->render->redirectHeader(_MD_XMOBILE_INVALID_TERMINAL,5,$base_url);
			exit();
		}
		return $this->getLoginForm();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ログイン フォーム
	function getLoginForm()
	{
		global $xoopsModuleConfig;
		$show_desc = intval($this->utils->getGet('show_desc',false));
		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',null);
		$ticket_html = $this->ticket->getTicketHtml();
		$carrier = $this->sessionHandler->getCarrierForLogin();

		// use_easy_login
		$use_easy_login = false;
		if ($xoopsModuleConfig['use_easy_login'])
		{
			if ($this->easyLogin() == 0)// 簡単ログイン実行
			{
				//個体識別番号が未登録の場合は登録フォームを表示
				// docomo以外のキャリアの場合は個体識別番号が取得出来る場合のみ、かんたんログインオプションを表示する
				if ($this->sessionHandler->getSubscriberId() != '')
				{
					$use_easy_login = true;
					if (!$show_desc)
					{
						$show_desc_url = $this->utils->getLinkUrl('login',null,null,null,'show_desc=1');
						$this->controller->render->template->assign('show_desc_url',$show_desc_url);
					}
					else
					{
						$limit = $xoopsModuleConfig['easy_login_limit'] / 86400;
						$easy_login_desc = sprintf(_MD_XMOBILE_EASY_LOGIN_DESC,$limit);
						$this->controller->render->template->assign('easy_login_desc',$easy_login_desc);
					}
				}
				elseif ($carrier == 1)// docomoの場合まず、formにutn属性を付加する必要がある
				{
					$use_easy_login = true;
					if (!$show_desc)
					{
						$show_desc_url = $this->utils->getLinkUrl('login',null,null,null,'show_desc=1');
						$this->controller->render->template->assign('show_desc_url',$show_desc_url);
					}
					else
					{
						$limit = $xoopsModuleConfig['easy_login_limit'] / 86400;
						$easy_login_desc = sprintf(_MD_XMOBILE_EASY_LOGIN_DESC,$limit);
						$this->controller->render->template->assign('easy_login_desc',$easy_login_desc);
					}
				}
			}
		}

		$this->controller->render->template->assign('ticket_html',$ticket_html);
		$this->controller->render->template->assign('base_url',$baseUrl);
		$this->controller->render->template->assign('carrier',$carrier);
		$this->controller->render->template->assign('use_easy_login',$use_easy_login);
		$this->controller->render->template->assign('show_desc',$show_desc);
		$this->controller->render->template->assign('session_name',session_name());
		$this->controller->render->template->assign('session_id',session_id());
		$this->controller->render->template->assign('referer_url',$this->getBaseUrl());

		return;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ログイン処理
	function getConfirmView()
	{
		//チケットの確認
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
			$this->loginError = true;
			$this->controller->render->template->assign('login_failed',_MD_XMOBILE_TICKET_ERROR);
			$this->getLoginForm();
			return;
		}

		if ($session_id = $this->sessionHandler->login())
		{
			$user =& $this->sessionHandler->getUser();
			if (0 == $user->getVar('level'))
			{
				$this->controller->render->redirectHeader(_MD_XMOBILE_US_NOACTTPADM,3);
				exit();
			}
			header('Location: '.XMOBILE_URL.'/index.php?sess='.$session_id);
			exit();
		}
		else
		{
			$this->loginError = true;
			$this->controller->render->template->assign('login_failed',_MD_XMOBILE_LOGIN_FAILED);
			$this->getLoginForm();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// 簡単ログイン処理
	function easyLogin()
	{
		if ($session_id = $this->sessionHandler->loginBySubscriberId())
		{
			$user =& $this->sessionHandler->getUser();
			if (0 == $user->getVar('level'))
			{
				$this->controller->render->redirectHeader(_MD_XMOBILE_US_NOACTTPADM,3);
				exit();
			}
			header('Location: '.XMOBILE_URL.'/index.php?sess='.$session_id);
			exit();
		}
		else
		{
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>