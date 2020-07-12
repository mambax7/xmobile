<?php
// パスワード再発行画面
//
if (!defined('XOOPS_ROOT_PATH')) exit();
global $xoopsConfig;
include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/user.php';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class LostpassAction extends XmobileAction
{
	var $template = 'xmobile_lostpass.html';
	var $showLogin = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function LostpassAction()
	{
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/gtickets.php';
		$this->ticket = new XoopsGTicket;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_MD_XMOBILE_LOST_PASS);
		$this->controller->render->template->assign('page_title',_MD_XMOBILE_LOST_PASS);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// パスワード申請フォーム
	function getDefaultView()
	{
		global $xoopsModuleConfig;
		if ($xoopsModuleConfig['login_terminal'] != 2 && $this->sessionHandler->getCarrierForLogin() == 0)
		{
			$base_url = $this->controller->utils->getLinkUrl('default',null,null,$this->sessionHandler->getSessionId());
			$this->controller->render->redirectHeader(_MD_XMOBILE_INVALID_TERMINAL,5,$base_url);
			exit();
		}

		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',null,$this->sessionHandler->getSessionID());
		$ticket_html = $this->ticket->getTicketHtml();

		$this->controller->render->template->assign('base_url',$baseUrl);
		$this->controller->render->template->assign('ticket_html',$ticket_html);
		$this->controller->render->template->assign('session_name',session_name());
		$this->controller->render->template->assign('session_id',session_id());
		$this->controller->render->template->assign('referer_url',$this->getBaseUrl());
		$this->controller->render->template->assign('show_form',true);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// パスワード発行
	function getConfirmView()
	{
		global $xoopsConfig,$xoopsDB;

		$myts =& MyTextSanitizer::getInstance();
		$op = $myts->makeTboxData4Show($this->utils->getGetPost('op', ''));
		$email = $this->utils->getGetPost('email', '');
		$code = trim($this->utils->getGet('code', ''));

		if ($email == '')
		{
			$this->controller->render->redirectHeader(_US_SORRYNOTFOUND,5);
			exit();
		}

		$member_handler =& xoops_gethandler('member');
		$user =& $member_handler->getUsers(new Criteria('email', $myts->addSlashes($email)));

		if (empty($user))
		{
			$this->controller->render->redirectHeader(_US_SORRYNOTFOUND,5);
			exit();
		}
		else // 新規パスワードの通知メールの送信
		{
			$areyou = substr($user[0]->getVar('pass'), 0, 5);
			if ($code != '' && $areyou == $code)
			{
				$newpass = xoops_makepass();
				$xoopsMailer =& getMailer();
				$xoopsMailer->useMail();
				$xoopsMailer->setToUsers($user[0]);
				$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
				$xoopsMailer->setFromName($xoopsConfig['sitename']);
				$xoopsMailer->setSubject(sprintf(_US_NEWPWDREQ,XOOPS_URL));

				$login_url = XMOBILE_URL.'/?act=login';
				$site_url = XOOPS_URL.'/';
				$mail_body = '';
				$mail_body .= sprintf(_MD_XMOBILE_GREETING,$user[0]->getVar('uname'));
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY1,$_SERVER['REMOTE_ADDR'],$xoopsConfig['sitename']);
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY2,$user[0]->getVar('uname'),$newpass);
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY3,$login_url);
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY_SITENAME,$xoopsConfig['sitename'],$site_url);
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY_ADMINMAIL,$xoopsConfig['adminmail']);
				$xoopsMailer->setBody($mail_body);

				if (!$xoopsMailer->send())
				{
					$this->controller->render->redirectHeader($xoopsMailer->getErrors(),5);
					exit();
				}

				// Next step: add the new password to the database
				$sql = sprintf("UPDATE %s SET pass = '%s' WHERE uid = %u", $xoopsDB->prefix('users'), md5($newpass), $user[0]->getVar('uid'));
				if (!$xoopsDB->queryF($sql))
				{
					$this->controller->render->redirectHeader('sql error',5);
					exit();
				}
				$this->controller->render->redirectHeader(sprintf(_US_PWDMAILED,$user[0]->getVar('uname')),5);
				exit();
			}
			else // パスワード取得用メールの送信
			{
				//チケットの確認
				if (!$ticket_check = $this->ticket->check(true,'',false))
				{
					$this->controller->render->redirectHeader(_MD_XMOBILE_TICKET_ERROR,5);
					exit();
				}

				$xoopsMailer =& getMailer();
				$xoopsMailer->useMail();
				$xoopsMailer->setToUsers($user[0]);
				$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
				$xoopsMailer->setFromName($xoopsConfig['sitename']);
				$xoopsMailer->setSubject(sprintf(_US_NEWPWDREQ,$xoopsConfig['sitename']));

				$mail_body = '';
				$mail_body .= sprintf(_MD_XMOBILE_GREETING,$user[0]->getVar('uname'));
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY4,$_SERVER['REMOTE_ADDR'],$xoopsConfig['sitename']);
				$mail_body .= "\n".XMOBILE_URL."/?act=lostpass&view=confirm&email=".$myts->makeTboxData4Show($email)."&code=".$areyou."&op=mailpasswd\n";
				$mail_body .= _MD_XMOBILE_MAILBODY5;
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY_SITENAME,$xoopsConfig['sitename'],XOOPS_URL);
				$mail_body .= sprintf(_MD_XMOBILE_MAILBODY_ADMINMAIL,$xoopsConfig['adminmail']);
				$xoopsMailer->setBody($mail_body);

				if (!$xoopsMailer->send())
				{
					$message = $xoopsMailer->getErrors();
				}
				else
				{
					$message = sprintf(_US_CONFMAIL,$user[0]->getVar('uname'));
				}
				$this->controller->render->template->assign('confirm_message',$message);
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>