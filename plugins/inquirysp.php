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
class XmobileInquiryspPluginAbstract extends XmobilePlugin
{
	function __construct()
	{
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileInquiryspPluginHandlerAbstract extends XmobilePluginHandler
{
	var $template = 'xmobile_inquirysp.html';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($mydirname,$db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
		$this->moduleDir = $mydirname;
		$this->ticket = new XoopsGTicket;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// diaplay contact form
	function getDefaultView()
	{
		global $xoopsConfig, $xoopsModuleConfig;

		$myts =& MyTextSanitizer::getInstance();
		$tarea_cols = $xoopsModuleConfig['tarea_cols'];
		$tarea_rows = $xoopsModuleConfig['tarea_rows'];
		$this->setNextViewState('confirm');
		$this->setBaseUrl();

		$session_id = $this->sessionHandler->getSessionID();
		$user =& $this->sessionHandler->getUser();

		$baseUrl = preg_replace('/&amp;/i','&',$this->baseUrl);
		$detail4html = '';
		$detail4html .= '<form action="'.$baseUrl.'" method="post">';
		$detail4html .= '<div class="form">';
		$detail4html .= $this->ticket->getTicketHtml();
		$detail4html .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
		$detail4html .= _MD_XMOBILE_NAME.'<br />';

		if (is_object($user))
		{
			$usersName = $user->getVar('uname');
			$usersEmail = $user->getVar('email');

			$detail4html .= $usersName.'<br />';
			$detail4html .= 'e-mail:<br />';
			$detail4html .= $usersEmail.'<br />';
			$detail4html .= '<input type="hidden" name="usersName" value="'.$usersName.'" />';
			$detail4html .= '<input type="hidden" name="usersEmail" value="'.$usersEmail.'" />';
		}
		else
		{
			$usersName = '';
			$usersEmail = '';

			$detail4html .= '<input type="text" name="usersName" value="'.$usersName.'" size="14" /><br />';
			$detail4html .= 'e-mail:<br />';
			$detail4html .= '<input type="text" name="usersEmail" value="'.$usersEmail.'" size="14" /><br />';
		}

		$detail4html .= _MD_XMOBILE_CONTENTS.'<br />';
		$detail4html .= '<textarea name="usersComments" cols="'.$tarea_cols.'" rows="'.$tarea_rows.'"></textarea><br />';
		$detail4html .= '<input type="submit" name="submit" value="'._MD_XMOBILE_SEND.'" />';
		$detail4html .= '</div>';
		$detail4html .= '</form>';

		$this->controller->render->template->assign('item_detail',$detail4html);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getConfirmView()
	{
		global $xoopsConfig;
		$myts =& MyTextSanitizer::getInstance();

		$usersName = $myts->stripSlashesGPC($this->utils->getPost('usersName', ''));
		$usersEmail = $myts->stripSlashesGPC($this->utils->getPost('usersEmail', ''));
		$usersComments = $myts->stripSlashesGPC($this->utils->getPost('usersComments', ''));


		//チケットの確認
//		if (!$ticket_check = $this->ticket->check())
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
			return _MD_XMOBILE_TICKET_ERROR;
		}

		if (!checkEmail($usersEmail))
		{
			return _MD_XMOBILE_INVALIDMAIL;
		}

		$detail4html = '';
		if ($usersName !== '' && $usersEmail !=='' && $usersComments !== '')
		{
			$subject = $xoopsConfig['sitename'].' - '._MD_XMOBILE_FROM_MOBILE._MD_XMOBILE_CONTACTFORM;

			$adminMessage = "";
			$adminMessage .= sprintf(_MD_XMOBILE_SUBMITTED,$usersName)."\n";
			$adminMessage .= _MD_XMOBILE_EMAIL." ".$usersEmail."\n";
			$adminMessage .= "HTTP_USER_AGENT:".$_SERVER['HTTP_USER_AGENT']."\n";
			$adminMessage .= _MD_XMOBILE_COMMENTS."\n";
			$adminMessage .= $usersComments."\n";

			$xoopsMailer =& getMailer();
			$xoopsMailer->useMail();
			$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
			$xoopsMailer->setFromEmail($usersEmail);
			$xoopsMailer->setFromName($xoopsConfig['sitename']);
			$xoopsMailer->setSubject($subject);
			$xoopsMailer->setBody($adminMessage);
			$xoopsMailer->send();

			$detail4html .= _MD_XMOBILE_THANKYOU;
		}
		else
		{
			$detail4html .= _MD_XMOBILE_SENDMAIL_FAILED.'<br />';
		}

		$this->controller->render->template->assign('item_detail',$detail4html);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
eval('
class Xmobile'.$Pluginname.'Plugin extends XmobileInquiryspPluginAbstract
{
	function Xmobile'.$Pluginname.'Plugin()
	{
		$this->__construct();
	}
}

class Xmobile'.$Pluginname.'PluginHandler extends XmobileInquiryspPluginHandlerAbstract
{
	function Xmobile'.$Pluginname.'PluginHandler($db)
	{
		$this->__construct("'.$mydirname.'",$db);
	}
}
');
?>
