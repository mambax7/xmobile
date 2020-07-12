<?php
// ログアウト画面
//
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class LogoutAction extends XmobileAction
{
	var $showLogin = 0;
	var $showBacktoMain = 1;
	var $isLogout = 1;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function LogoutAction()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_MD_XMOBILE_LOG_OUT);
		$this->controller->render->template->assign('page_title',_MD_XMOBILE_LOG_OUT);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ログアウト処理後、可能ならDefaultViewにリダイレクト
	function getDefaultView()
	{
		global $xoopsConfig,$xoopsUser;
		$_SESSION = array();
		session_destroy();

		if ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '')
		{
			setcookie($xoopsConfig['session_name'], '', time()- 3600, '/',  '', 0);
		}

		// clear entry from online users table
/*
		if (is_object($xoopsUser))
		{
			$online_handler =& xoops_gethandler('online');
			$online_handler->destroy($xoopsUser->getVar('uid'));
		}
*/
		$this->sessionHandler->logout();
		$this->controller->render->redirectHeader(_MD_XMOBILE_LOGOUT_MESSAGE,3,XMOBILE_URL);
		exit();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>