<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class RegisterAction extends XmobileAction
{
	var $template = 'xmobile_register.html';
	var $showLogin = 0;
	var $showBacktoMain = 1;
	var $ticket;
	var $xoopsConfigUser;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function RegisterAction()
	{
		global $xoopsConfig;
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/gtickets.php';
		$this->ticket = new XoopsGTicket;

		$config_handler =& xoops_gethandler('config');

		include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/user.php';

		if (preg_match("/^XOOPS Cube/",XOOPS_VERSION)) // XOOPS Cube 2.1x
		{
			include_once XOOPS_ROOT_PATH.'/modules/user/language/'.$xoopsConfig['language'].'/main.php';
			$this->xoopsConfigUser =& $config_handler->getConfigsByDirname('user');
		}
		else // XOOPS 2.0x JP
		{
			$this->xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);
		}

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_US_USERREG);
		$this->controller->render->template->assign('page_title',_US_USERREG);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		global $xoopsDB,$xoopsConfig,$xoopsModuleConfig;

		if ($xoopsModuleConfig['login_terminal'] != 2 && $this->sessionHandler->getCarrierForLogin() == 0)
		{
			$base_url = $this->controller->utils->getLinkUrl('default',null,null,$this->sessionHandler->getSessionId());
			$this->controller->render->redirectHeader(_MD_XMOBILE_INVALID_TERMINAL,5,$base_url);
			exit();
		}

		$myts =& MyTextSanitizer::getInstance();
		$op = $myts->makeTboxData4Show($this->utils->getGetPost('op', 'agreement'));
		$session_id = $this->sessionHandler->getSessionId();

		$email = $myts->makeTboxData4Save($this->utils->getPost('email', ''));
		$user_viewemail = intval($this->utils->getPost('user_viewemail', 0));
		$user_mailok = intval($this->utils->getPost('user_mailok', 0));
		$uname = $myts->makeTboxData4Save($this->utils->getPost('uname', ''));
		$pass = $myts->makeTboxData4Save($this->utils->getPost('pass', ''));
//		$vpass = $myts->makeTboxData4Save($this->utils->getPost('vpass', ''));
		$agree_disc = intval($this->utils->getPost('agree_disc', 0));
		$url = $myts->makeTboxData4Save($this->utils->getPost('url', ''));
		$timezone_offset = $myts->makeTboxData4Save($this->utils->getPost('timezone_offset', $xoopsConfig['default_TZ']));

		$message = '';

		// XOOPSの新規ユーザ登録許可の設定をチェック
		if (empty($this->xoopsConfigUser['allow_register']))
		{
			$this->controller->render->redirectHeader(_US_NOREGISTER,5);
			exit();
		}

		if (isset($_POST['cancel']))
		{
			header('Location: '.XMOBILE_URL);
			exit();
		}

		switch ($op)
		{
			case 'agreement':// check agreement
				$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
				$reg_disclaimer = $myts->makeTareaData4Show($this->xoopsConfigUser['reg_disclaimer'],1,1,1);
				$this->controller->render->template->assign('show_agreement_form',true);
				$this->controller->render->template->assign('base_url',$base_url);
				$this->controller->render->template->assign('reg_disclaimer',$reg_disclaimer);
				break;

			case 'register':// register user
//				$this->assignRegisterForm($email,$user_viewemail,$uname,$pass,$vpass,$user_mailok,$agree_disc,$url,$timezone_offset);
				$this->assignRegisterForm($email,$user_viewemail,$uname,$pass,$user_mailok,$agree_disc,$url,$timezone_offset);
				break;

			case 'newuser':// confirm register user

				$err_check = 0;
				$err_msg = '';
//				$stop = $this->userCheck($uname, $email, $pass, $vpass);
				$stop = $this->userCheck($uname, $email, $pass);
				if ($stop == '')
				{
					if ($uname == '')
					{
						$err_check = -1;
					}
					else
					{
						$uname_len = strlen($uname);
						if ($uname_len >= $this->xoopsConfigUser['minuname'] and $uname_len <= $this->xoopsConfigUser['maxuname'])
						{
							$tb_name = $xoopsDB->prefix('users');
							$sql = "SELECT uid FROM $tb_name WHERE uname = '$uname'";
							$result = $xoopsDB->query($sql);
							$result_n = 0;
							$result_n = $xoopsDB->getRowsNum($result);
							if ($result_n > 0)
							{
								$err_check = -1;
								$err_msg .= _MD_XMOBILE_ACCOUNT_EXIST.'<br />';
							}
						}
						else
						{
							$err_check = -1;
						}
					}
					if ($pass == '')
					{
						$err_check = -1;
					}
					if ($email == '')
					{
						$err_check = -1;
					}
					else
					{
						$tb_name = $xoopsDB->prefix('users');
						$sql = "SELECT uid FROM $tb_name WHERE email = '$email'";
						$result = $xoopsDB->query($sql);
						$result_n = 0;
						$result_n = $xoopsDB->getRowsNum($result);
						if ($result_n > 0)
						{
							$err_check = -1;
							$err_msg .= _MD_XMOBILE_E-MAIL_EXIST.'<br />';
						}
					}
				}
				else
				{
					$err_check = -1;
					$err_msg .= $stop;
				}

				if ($err_check == -1)
				{
					$this->controller->render->template->assign('message',$err_msg);
//					$this->assignRegisterForm($email,$user_viewemail,$uname,$pass,$vpass,$user_mailok,$agree_disc,$url,$timezone_offset);
					$this->assignRegisterForm($email,$user_viewemail,$uname,$pass,$user_mailok,$agree_disc,$url,$timezone_offset);
				}
				else
				{
//					$this->assignConfirmForm($email,$user_viewemail,$uname,$pass,$vpass,$user_mailok,$agree_disc,$url,$timezone_offset);
					$this->assignConfirmForm($email,$user_viewemail,$uname,$pass,$user_mailok,$agree_disc,$url,$timezone_offset);
				}
				break;

			case 'actv':

				$id = intval($_GET['id']);
				$actkey = trim($_GET['actkey']);
				if (empty($id))
				{
					$this->controller->render->redirectHeader('',1);
					exit();
				}
				$member_handler =& xoops_gethandler('member');
				$thisuser =& $member_handler->getUser($id);
				if (!is_object($thisuser))
				{
					exit();
				}
				if ($thisuser->getVar('actkey') != $actkey)
				{
					$this->controller->render->redirectHeader(_US_ACTKEYNOT,5);
					exit();
				}
				else
				{
					if ($thisuser->getVar('level') > 0)
					{
						$this->controller->render->redirectHeader(_US_ACONTACT,5);
						exit();
					}
					else
					{
						if (false != $member_handler->activateUser($thisuser))
						{
							if ($this->xoopsConfigUser['activation_type'] == 2)
							{
								$xoopsMailer =& getMailer();
								$xoopsMailer->useMail();
								$xoopsMailer->setTemplate('activated.tpl');
								$xoopsMailer->assign('SITENAME', $xoopsConfig['sitename']);
								$xoopsMailer->assign('ADMINMAIL', $xoopsConfig['adminmail']);
								$xoopsMailer->assign('SITEURL', XOOPS_URL.'/');
								$xoopsMailer->setToUsers($thisuser);
								$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
								$xoopsMailer->setFromName($xoopsConfig['sitename']);
								$xoopsMailer->setSubject(sprintf(_US_YOURACCOUNT,$xoopsConfig['sitename']));
			//					include 'header.php';
								if (!$xoopsMailer->send())
								{
									$message = sprintf(_US_ACTVMAILNG, $thisuser->getVar('uname'));
								}
								else
								{
									$message = sprintf(_US_ACTVMAILOK, $thisuser->getVar('uname'));
								}
							}
							else
							{
								$this->controller->render->redirectHeader(_US_ACTLOGIN,5);
								exit();
							}
						}
						else
						{
							$this->controller->render->redirectHeader('Activation failed!',5);
							exit();
						}
					}
				}
				$this->controller->render->template->assign('message',$message);
				break;
		}

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getConfirmView()
	{
		global $xoopsConfig;

		$myts =& MyTextSanitizer::getInstance();

		$session_id = $this->sessionHandler->getSessionId();

		$email = $myts->makeTboxData4Save($this->utils->getPost('email', ''));
		$user_viewemail = intval($this->utils->getPost('user_viewemail', 0));
		$user_mailok = intval($this->utils->getPost('user_mailok', 0));
		$uname = $myts->makeTboxData4Save($this->utils->getPost('uname', ''));
		$pass = $myts->makeTboxData4Save($this->utils->getPost('pass', ''));
//		$vpass = $myts->makeTboxData4Save($this->utils->getPost('vpass', ''));
		$agree_disc = intval($this->utils->getPost('agree_disc', 0));
		$url = $myts->makeTboxData4Save($this->utils->getPost('url', ''));
		$timezone_offset = $myts->makeTboxData4Save($this->utils->getPost('timezone_offset', ''));

		if (isset($_POST['cancel']))
		{
			header('Location: '.XMOBILE_URL);
			exit();
		}

		//チケットの確認
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
			$this->controller->render->redirectHeader(_MD_XMOBILE_TICKET_ERROR,5);
			exit();
		}

		$err_check = 0;
//		$stop = $this->userCheck($uname, $email, $pass, $vpass);
		$stop = $this->userCheck($uname, $email, $pass);

		if (empty($stop))
		{
			$member_handler =& xoops_gethandler('member');
			$newuser =& $member_handler->createUser();
			$newuser->setVar('user_viewemail',$user_viewemail, true);
			$newuser->setVar('uname', $uname, true);
			$newuser->setVar('email', $email, true);
			if ($url != '')
			{
				$newuser->setVar('url', formatURL($url), true);
			}
			$newuser->setVar('user_avatar','blank.gif', true);
			$actkey = substr(md5(uniqid(mt_rand(), 1)), 0, 8);
			$newuser->setVar('actkey', $actkey, true);
			$newuser->setVar('pass', md5($pass), true);
			$newuser->setVar('timezone_offset', $timezone_offset, true);
			$newuser->setVar('user_regdate', time(), true);
			$newuser->setVar('uorder',$xoopsConfig['com_order'], true);
			$newuser->setVar('umode',$xoopsConfig['com_mode'], true);
			$newuser->setVar('user_mailok',$user_mailok, true);

			if ($this->xoopsConfigUser['activation_type'] == 1)
			{
				$newuser->setVar('level', 1, true);
			}

			if (!$member_handler->insertUser($newuser))
			{
				$this->controller->render->redirectHeader(_US_REGISTERNG,5);
				exit();
			}

			$newid = $newuser->getVar('uid');

			if (!$member_handler->addUserToGroup(XOOPS_GROUP_USERS, $newid))
			{
				$this->controller->render->redirectHeader(_US_REGISTERNG,5);
				exit();
			}

			switch ($this->xoopsConfigUser['activation_type'])
			{
				case 0: // ユーザー自身による確認が必要
					$xoopsMailer =& getMailer();
					$xoopsMailer->useMail();
					$xoopsMailer->setTemplate('register.tpl');
					$xoopsMailer->assign('SITENAME', $xoopsConfig['sitename']);
					$xoopsMailer->assign('ADMINMAIL', $xoopsConfig['adminmail']);
					$xoopsMailer->assign('SITEURL', XOOPS_URL.'/');
					$xoopsMailer->assign('X_UACTLINK', XMOBILE_URL.'/?act=register&op=actv&id='.$newid.'&actkey='.$actkey);
					$xoopsMailer->setToUsers(new XoopsUser($newid));
					$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
					$xoopsMailer->setFromName($xoopsConfig['sitename']);
					$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
					if (!$xoopsMailer->send())
					{
						$message = _US_YOURREGMAILNG;
					}
					else
					{
						$message = _US_YOURREGISTERED;
					}
					break;

				case 1: // 自動的にユーザーアカウントを有効化
					$message = _US_ACTLOGIN;
					break;

				case 2: // 管理者が確認してアカウントを有効化
					$xoopsMailer =& getMailer();
					$xoopsMailer->useMail();
					$xoopsMailer->setTemplate('adminactivate.tpl');
					$xoopsMailer->assign('USERNAME', $uname);
					$xoopsMailer->assign('USEREMAIL', $email);
					$xoopsMailer->assign('USERACTLINK', XOOPS_URL.'/user.php?op=actv&id='.$newid.'&actkey='.$actkey);
					$xoopsMailer->assign('SITENAME', $xoopsConfig['sitename']);
					$xoopsMailer->assign('ADMINMAIL', $xoopsConfig['adminmail']);
					$xoopsMailer->assign('SITEURL', XMOBILE_URL);
					$member_handler =& xoops_gethandler('member');
					$xoopsMailer->setToGroups($member_handler->getGroup($this->xoopsConfigUser['activation_group']));
					$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
					$xoopsMailer->setFromName($xoopsConfig['sitename']);
					$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
					if (!$xoopsMailer->send())
					{
						$message = _US_YOURREGMAILNG;
					}
					else
					{
						$message = _US_YOURREGISTERED2;
					}
					break;
			}

			if ($this->xoopsConfigUser['new_user_notify'] == 1 && !empty($this->xoopsConfigUser['new_user_notify_group']))
			{
				$xoopsMailer =& getMailer();
				$xoopsMailer->useMail();
				$member_handler =& xoops_gethandler('member');
				$xoopsMailer->setToGroups($member_handler->getGroup($this->xoopsConfigUser['new_user_notify_group']));
				$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
				$xoopsMailer->setFromName($xoopsConfig['sitename']);
				$xoopsMailer->setSubject(sprintf(_US_NEWUSERREGAT,$xoopsConfig['sitename']));
				$xoopsMailer->setBody(sprintf(_US_HASJUSTREG, $uname));
				$xoopsMailer->send();
			}

			$this->controller->render->template->assign('message',$message);

		}
		else
		{
			$this->controller->render->template->assign('message',$stop);
//			$this->assignRegisterForm($email,$user_viewemail,$uname,$pass,$vpass,$user_mailok,$agree_disc,$url,$timezone_offset);
			$this->assignRegisterForm($email,$user_viewemail,$uname,$pass,$user_mailok,$agree_disc,$url,$timezone_offset);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function assignConfirmForm($email,$user_viewemail,$uname,$pass,$vpass,$user_mailok,$agree_disc,$url,$timezone_offset)
	function assignConfirmForm($email,$user_viewemail,$uname,$pass,$user_mailok,$agree_disc,$url,$timezone_offset)
	{
		$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());

		$this->controller->render->template->assign('show_confirm_form',true);
		$this->controller->render->template->assign('base_url',$base_url);
		$this->controller->render->template->assign('email',$email);
		$this->controller->render->template->assign('user_viewemail',$user_viewemail);
		$this->controller->render->template->assign('uname',$uname);
		$this->controller->render->template->assign('pass',$pass);
//		$this->controller->render->template->assign('vpass',$vpass);
		$this->controller->render->template->assign('user_mailok',$user_mailok);
		$this->controller->render->template->assign('agree_disc',$agree_disc);
		$this->controller->render->template->assign('url',$url);
		$this->controller->render->template->assign('timezone_offset',$timezone_offset);
		$this->controller->render->template->assign('ticket_html',$this->ticket->getTicketHtml());
		$this->controller->render->template->assign('session_name',session_name());
		$this->controller->render->template->assign('session_id',session_id());
		$this->controller->render->template->assign('referer_url',$this->getBaseUrl());
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function assignRegisterForm($email,$user_viewemail,$uname,$pass,$vpass,$user_mailok,$agree_disc,$url,$timezone_offset)
	function assignRegisterForm($email,$user_viewemail,$uname,$pass,$user_mailok,$agree_disc,$url,$timezone_offset)
	{
		global $xoopsConfig;

		$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
		$this->controller->render->template->assign('show_register_form',true);
		$this->controller->render->template->assign('base_url',$base_url);
		$this->controller->render->template->assign('email',$email);
		$this->controller->render->template->assign('user_viewemail',$user_viewemail);
		$this->controller->render->template->assign('uname',$uname);
		$this->controller->render->template->assign('pass',$pass);
//		$this->controller->render->template->assign('vpass',$vpass);
		$this->controller->render->template->assign('user_mailok',$user_mailok);
		$this->controller->render->template->assign('agree_disc',$agree_disc);
		$this->controller->render->template->assign('url',$url);
		$this->controller->render->template->assign('timezone_offset',$timezone_offset);
		$this->controller->render->template->assign('ticket_html',$this->ticket->getTicketHtml());
		$this->controller->render->template->assign('session_name',session_name());
		$this->controller->render->template->assign('session_id',session_id());
		$this->controller->render->template->assign('referer_url',$this->getBaseUrl());
// ユーザ名として使用可能な文字の設定を参照
		$this->controller->render->template->assign('uname_test_level',$this->xoopsConfigUser['uname_test_level']);
		$uname_limit = '';
		$pass_limit = '';
		switch ($this->xoopsConfigUser['uname_test_level'])
		{
			case 0:
				// strict
				$uname_limit = _MD_XMOBILE_UNAME_LIMIT_S;
				break;
			case 1:
				// medium
				$uname_limit = _MD_XMOBILE_UNAME_LIMIT_M;
				break;
			case 2:
				// loose
				$minuname = ceil($this->xoopsConfigUser['minuname'] / 2);
				$maxuname = ceil($this->xoopsConfigUser['maxuname'] / 2);
				$uname_limit = sprintf(_MD_XMOBILE_UNAME_LIMIT_L, $minuname, $maxuname);
				
				break;
		}
		$uname_limit .= sprintf(_MD_XMOBILE_UNAME_LIMIT, $this->xoopsConfigUser['minuname'], $this->xoopsConfigUser['maxuname']);
		$pass_limit = sprintf(_MD_XMOBILE_PASS_LIMIT, $this->xoopsConfigUser['minpass']);

		$this->controller->render->template->assign('uname_limit',$uname_limit);
		$this->controller->render->template->assign('pass_limit',$pass_limit);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function userCheck($uname, $email, $pass, $vpass)
	function userCheck($uname, $email, $pass)
	{
		global $xoopsDB;
		$myts =& MyTextSanitizer::getInstance();

		$stop = '';
		if (!checkEmail($email))
		{
			$stop .= _US_INVALIDMAIL.'<br />';
		}
		foreach ($this->xoopsConfigUser['bad_emails'] as $be)
		{
			if (!empty($be) && preg_match('/'.$be.'/i', $email))
			{
				$stop .= _US_INVALIDMAIL.'<br />';
				break;
			}
		}
		if (strrpos($email,' ') > 0)
		{
			$stop .= _US_EMAILNOSPACES.'<br />';
		}
		$uname = xoops_trim($uname);

		switch ($this->xoopsConfigUser['uname_test_level'])
		{
			case 0:
				// strict
				$restriction = '/[^a-zA-Z0-9\_\-]/';
				break;
			case 1:
				// medium
				$restriction = '/[^a-zA-Z0-9\_\-\<\>\,\.\$\%\#\@\!\\\'\"]/';
				break;
			case 2:
				// loose
				$restriction = '/[\000-\040]/';
				break;
		}
		if (empty($uname) || preg_match($restriction, $uname))
		{
			$stop .= _US_INVALIDNICKNAME.'<br />';
		}
		if (strlen($uname) > $this->xoopsConfigUser['maxuname'])
		{
			$stop .= sprintf(_US_NICKNAMETOOLONG, $this->xoopsConfigUser['maxuname']).'<br />';
		}
		if (strlen($uname) < $this->xoopsConfigUser['minuname'])
		{
			$stop .= sprintf(_US_NICKNAMETOOSHORT, $this->xoopsConfigUser['minuname']).'<br />';
		}
		foreach ($this->xoopsConfigUser['bad_unames'] as $bu)
		{
			if (!empty($bu) && preg_match('/'.$bu.'/i', $uname))
			{
				$stop .= _US_NAMERESERVED.'<br />';
				break;
			}
		}
		if (strrpos($uname, ' ') > 0)
		{
			$stop .= _US_NICKNAMENOSPACES.'<br />';
		}
		$sql = sprintf('SELECT COUNT(*) FROM %s WHERE uname = %s', $xoopsDB->prefix('users'), $xoopsDB->quoteString(addslashes($uname)));
		$result = $xoopsDB->query($sql);
		list($count) = $xoopsDB->fetchRow($result);
		if ($count > 0)
		{
			$stop .= _US_NICKNAMETAKEN.'<br />';
		}
		$count = 0;
		if ($email)
		{
			$sql = sprintf('SELECT COUNT(*) FROM %s WHERE email = %s', $xoopsDB->prefix('users'), $xoopsDB->quoteString(addslashes($email)));
			$result = $xoopsDB->query($sql);
			list($count) = $xoopsDB->fetchRow($result);
			if ($count > 0)
			{
				$stop .= _US_EMAILTAKEN.'<br />';
			}
		}
//		if (!isset($pass) || $pass == '' || !isset($vpass) || $vpass == '')
		if (!isset($pass) || $pass == '')
		{
			$stop .= _US_ENTERPWD.'<br />';
		}
//		if ((isset($pass)) && ($pass != $vpass))
//		{
//			$stop .= _US_PASSNOTSAME.'<br />';
//		}
		elseif (($pass != '') && (strlen($pass) < $this->xoopsConfigUser['minpass']))
		{
			$stop .= sprintf(_US_PWDTOOSHORT,$this->xoopsConfigUser['minpass']).'<br />';
		}
		return $stop;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
