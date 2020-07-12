<?php
// イベント通知画面
//
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class NotificationsAction extends XmobileAction
{
	var $template = 'xmobile_notifications.html';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function NotificationsAction()
	{
		global $xoopsConfig;
		include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/notification.php';
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/gtickets.php';
		$this->ticket = new XoopsGTicket;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_MD_XMOBILE_NOTIFICATION);
		$this->controller->render->template->assign('page_title',_MD_XMOBILE_NOTIFICATION);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 初期画面
	function getDefaultView()
	{
		if (!is_object($this->sessionHandler->getUser()))
		{
			$this->controller->render->template->assign('noperm_message',_MD_XMOBILE_NO_PERM_MESSAGE);
		}
		else
		{
			$this->drowNortificationForm();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// イベント更新
	function getConfirmView()
	{
		global $xoopsDB;
		$myts =& MyTextSanitizer::getInstance();
		$uid = $this->sessionHandler->getUid();
		$not_item = $this->utils->getGetPost('not_item', '');
		$notify_method = intval($this->utils->getPost('notify_method', 0));
		$notify_mode = intval($this->utils->getPost('notify_mode', 0));
		$message = '';

		//チケットの確認
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
			$message = _MD_XMOBILE_TICKET_ERROR;
		}

		if (!is_object($this->sessionHandler->getUser()))
		{
			$base_url = $this->utils->getLinkUrl('default',null,null,$this->sessionHandler->getSessionID());
			$this->controller->render->redirectHeader(_MD_XMOBILE_UPDATE_FAILED,3,$base_url);
			exit();
		}

		$member_handler =& xoops_gethandler('member');
		$user =& $member_handler->getUser($uid);
		$user->setVar('notify_method',$notify_method);
		$user->setVar('notify_mode',$notify_mode);
		if (!$member_handler->insertUser($user))
		{
			// debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'update notify_method Error',$user->getErrors());
			$message = _MD_XMOBILE_UPDATE_FAILED;
		}

		$modules = $this->getModuleArray();
		foreach($modules as $mid=>$module)
		{
			if (array_key_exists($mid,$not_item))
			{
				$notify = 1;
			}
			else
			{
				$notify = 0;
			}
			$not_event = 'new_'.$module['dirname'];
/*
			$criteria = new Criteria ('not_uid', $uid);
			$criteria->add(new Criteria ('not_modid', $mid));
			$notification_handler =& xoops_gethandler('notification');
			$notifications =& $notification_handler->getObjects($criteria);
*/
			$sql = "SELECT not_id FROM ".$xoopsDB->prefix('xoopsnotifications')." WHERE not_modid=".$mid." AND not_uid=".$uid;
			$ret = $xoopsDB->query($sql);
			$count = $xoopsDB->getRowsNum($ret);

			if ($notify == 1 && $count == 0)
			{
				$sql = "INSERT INTO ".$xoopsDB->prefix('xoopsnotifications');
//				$sql .= " (not_modid,not_itemid,not_category,not_event,not_uid,not_mode)";
//				$sql .= " VALUES($mid,$not_itemid,'global',$not_event,$uid,$not_mode)";
				$sql .= " (not_modid,not_category,not_event,not_uid)";
				$sql .= " VALUES($mid,'global','$not_event',$uid)";

				$ret = $xoopsDB->query($sql);
				if (!$ret)
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'insert sql error', $xoopsDB->error());
					$message = _MD_XMOBILE_UPDATE_FAILED;
				}
				else
				{
					$message = _MD_XMOBILE_UPDATE_SUCCESS;
				}
			}
			elseif ($notify == 0 && $count != 0)
			{
				$sql = "DELETE FROM ".$xoopsDB->prefix('xoopsnotifications')." WHERE not_modid=".$mid." AND not_uid=".$uid;
				$ret = $xoopsDB->query($sql);
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'delete sql', $sql);
				if (!$ret)
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'delete sql error', $xoopsDB->error());
					$message = _MD_XMOBILE_UPDATE_FAILED;
				}
				else
				{
					$message = _MD_XMOBILE_UPDATE_SUCCESS;
				}
			}
		}
	
		$this->controller->render->template->assign('message',$message);
		$this->drowNortificationForm();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// イベント一覧・更新フォーム
	function drowNortificationForm()
	{
		$uid = $this->sessionHandler->getUid();
		$base_url = $this->utils->getLinkUrl('notifications','confirm',null,$this->sessionHandler->getSessionID());

		$member_handler =& xoops_gethandler('member');
		$user =& $member_handler->getUser($uid);
		if (is_object($user))
		{
			$user_method = $user->getVar('notify_method');
			$user_mode = $user->getVar('notify_mode');

//			$notify_methods = array(
//				XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE,
//				XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM,
//				XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL
//			);
//			$notify_modes = array(
//				XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS,
//				XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE,
//				XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN
//			);

			$base_methods = array(0=>_NOT_METHOD_DISABLE,1=>_NOT_METHOD_PM,2=>_NOT_METHOD_EMAIL);
			$base_mode = array(0=>_NOT_MODE_SENDALWAYS,1=>_NOT_MODE_SENDONCE,2=>_NOT_MODE_SENDONCEPERLOGIN);

			$notify_methods = array();
			$notify_modes = array();
			foreach($base_methods as $key=>$value)
			{
				$notify_methods[$key]['name'] = $value;
				if ($key == $user_method)
				{
					$notify_methods[$key]['selected'] = ' selected="selected"';
				}
				else
				{
					$notify_methods[$key]['selected'] = '';
				}
			}
			foreach($base_mode as $key=>$value)
			{
				$notify_modes[$key]['name'] = $value;
				if ($key == $user_mode)
				{
					$notify_modes[$key]['selected'] = ' selected="selected"';
				}
				else
				{
					$notify_modes[$key]['selected'] = '';
				}
			}

			$this->controller->render->template->assign('notify_methods',$notify_methods);
			$this->controller->render->template->assign('notify_modes',$notify_modes);
		}

		$modules = $this->getModuleArray();
		$mods = array();
		foreach($modules as $mid=>$module)
		{
			$criteria = new CriteriaCompo();
			$criteria->add(new Criteria('not_uid', $uid));
			$criteria->add(new Criteria('not_modid',$mid));
			$notification_handler =& xoops_gethandler('notification');
			$count = $notification_handler->getCount($criteria);

			if ($count == 0)
			{
				$checked = 0;
			}
			else
			{
				$checked = 1;
			}

			$mods[$mid]['name'] = $module['name'];
			$mods[$mid]['checked'] = $checked;
		}

		$this->controller->render->template->assign('base_url',$base_url);
		$this->controller->render->template->assign('ticket_html',$this->ticket->getTicketHtml());
		$this->controller->render->template->assign('session_name',session_name());
		$this->controller->render->template->assign('session_id',session_id());
		$this->controller->render->template->assign('referer_url',$this->getBaseUrl());
		$this->controller->render->template->assign('mods',$mods);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// イベント通知有効でかつxmobileで利用可能なモジュールを取得
	function getModuleArray()
	{
		global $xoopsModuleConfig;

		$module_handler =& xoops_gethandler('module');
		$criteria = new CriteriaCompo(new Criteria('isactive', 1));
		$criteria->add(new Criteria('hasnotification',1));
		$criteria->add(new Criteria('weight',0,'<>'));
		$criteria->setSort('weight');
		$modules =& $module_handler->getObjects($criteria);

		$module_array = array();

		foreach($modules as $module)
		{
			$mid = $module->getVar('mid');
			$module_name = $module->getVar('name');
			$module_dirname = $module->getVar('dirname');
			// 利用可能なモジュールを取得
			if (in_array($module_dirname, $xoopsModuleConfig['modules_can_use']))
			{
				$module_array[$mid]['name'] = $module_name;
				$module_array[$mid]['dirname'] = $module_dirname;
			}
		}

		return $module_array;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
