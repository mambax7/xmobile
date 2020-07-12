<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class PmessageAction extends XmobileAction
{
	var $template = 'xmobile_pmessage.html';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function PmessageAction()
	{
		global $xoopsConfig;
//		include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/pmsg.php';
//		$this->ticket = new XoopsGTicket;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_MD_XMOBILE_INBOX);
		$this->controller->render->template->assign('page_title',_MD_XMOBILE_INBOX);
		$base_url = $this->utils->getLinkUrl('pmessage',null,null,$this->sessionHandler->getSessionID());
		$title_link = '<a href="'.$base_url.'">'._MD_XMOBILE_INBOX.'</a>';
		$this->controller->render->template->assign('page_title_link',$title_link);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();
		$op = $myts->makeTboxData4Show($this->utils->getGetPost('op', ''));
		$session_id = $this->sessionHandler->getSessionID();
		$uid = $this->sessionHandler->getUid();
		if ($uid == 0)
		{
			$base_url = $this->utils->getLinkUrl('register',null,null,$this->sessionHandler->getSessionID());
			$message = _MD_XMOBILE_PM_SORRY.'<br /><a href="'.$base_url.'">'._MD_XMOBILE_REGISTERNOW.'</a>.';
			$this->controller->render->redirectHeader($message,5,$base_url);
			exit();
		}

		$pm_handler =& xoops_gethandler('privmessage');

		$criteria =& new Criteria('to_userid', $uid);
		$criteria->setSort('msg_time');
		$criteria->setOrder('DESC');
		$extra_arg = $this->baseUrl;
		$pm_count = $pm_handler->getCount($criteria);
		$pageNavi =& new XmobilePageNavigator($pm_count, $xoopsModuleConfig['pm_title_row'], 'pm_start', $extra_arg);
		$start = $pageNavi->getStart();
		$criteria->setLimit($pageNavi->getPerpage());
		$criteria->setStart($pageNavi->getStart());

		$pm_arr =& $pm_handler->getObjects($criteria);
		$total_messages = count($pm_arr);

		if ($total_messages == 0)
		{
			$display = 0;
		}
		else
		{
			$display = 1;
			$pms = array();
			for($i = 0; $i < $total_messages; $i++)
			{
				$pms[$i]['read_msg'] = $pm_arr[$i]->getVar('read_msg');
				$poster_name = XoopsUser::getUnameFromId($pm_arr[$i]->getVar('from_userid'));
				if (!$poster_name)
				{
					$poster_name = $xoopsConfig['anonymous'];
				}
				$pms[$i]['poster_name'] = $poster_name;

				// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
//				$ext = 'start='.$i.'&total_messages='.$total_messages;
				$ext = 'start='.($start + $i).'&total_messages='.$total_messages;
				$base_url = $this->utils->getLinkUrl('pmessage','detail',null,$this->controller->sessionHandler->getSessionID(),$ext);
				$subject = '<a href="'.$base_url.'">'.$pm_arr[$i]->getVar('subject').'</a>';
				$pms[$i]['subject'] = $subject;
				$msg_time = formatTimestamp($pm_arr[$i]->getVar('msg_time'));
				$pms[$i]['msg_time'] = $msg_time;
			}
			$this->controller->render->template->assign('pms',$pms);
		}

		$this->controller->render->template->assign('pm_page_navi',$pageNavi->renderNavi());

		$ext = 'send=1';
		$base_url = $this->utils->getLinkUrl('pmessage','confirm',null,$this->controller->sessionHandler->getSessionID(),$ext);
		$base_url = preg_replace('/&amp;/i','&',$base_url);
		$this->controller->render->template->assign('base_url',$base_url);
		$this->controller->render->template->assign('total_messages',$total_messages);
		$this->controller->render->template->assign('show_list',true);
		$this->controller->render->template->assign('display',$display);

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDetailView()
	{
		if (isset($_POST['cancel']))
		{
			$base_url = XMOBILE_URL.'/?act=pmessage&sess='.$this->sessionHandler->getSessionID();
			header('Location: '.$base_url);
			exit();
		}

		$this->controller->render->template->assign('show_detail',true);

		$myts =& MyTextSanitizer::getInstance();
//		$op = $myts->makeTboxData4Show($this->utils->getGetPost('op', ''));
		$msg_id = intval($this->utils->getGetPost('msg_id', 0));
		$session_id = $this->sessionHandler->getSessionID();
		$uid = $this->sessionHandler->getUid();
		$message = '';

/*
		if (isset($_POST['reply']))
		{
			$base_url = XMOBILE_URL.'/?act=pmessage&view=confirm&reply=1&msg_id='.$msg_id.'&sess='.$this->sessionHandler->getSessionID();
			header('Location: '.$base_url);
			exit();
		}
*/
		if ($uid == 0)
		{
			$base_url = $this->utils->getLinkUrl('register',null,null,$this->sessionHandler->getSessionID());
			$message = _MD_XMOBILE_PM_SORRY.'<br /><a href="'.$base_url.'">'._MD_XMOBILE_REGISTERNOW.'</a>.';
			$this->controller->render->redirectHeader($message,5,$base_url);
			exit();
		}

		$pm_handler =& xoops_gethandler('privmessage');
/*
		if (!empty($_POST['delete']))
		{
			$pm =& $pm_handler->get(intval($_POST['msg_id']));
			if (!is_object($pm) || $pm->getVar('to_userid') != $uid || !$pm_handler->delete($pm))
			{
				$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),null,null,$this->sessionHandler->getSessionID());
				$this->controller->render->redirectHeader(_MD_XMOBILE_DELETE_FAILED,5,$base_url);
				exit();
			}
			else
			{
				$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),null,null,$this->sessionHandler->getSessionID());
				$this->controller->render->redirectHeader(_MD_XMOBILE_PM_DELETED,3,$base_url);
				exit();
			}
		}
*/
//		$start = !empty($_GET['start']) ? intval($_GET['start']) : 0;
//		$total_messages = !empty($_GET['total_messages']) ? intval($_GET['total_messages']) : 0;

/*
		$criteria = new Criteria('to_userid', $uid);
		$criteria->setLimit($limit);
		$criteria->setStart($start);
		$criteria->setSort('msg_time');
		$criteria->setOrder('DESC');
		$pm_arr =& $pm_handler->getObjects($criteria);
*/
		$criteria =& new Criteria('to_userid', $uid);
		$criteria->setSort('msg_time');
		$criteria->setOrder('DESC');
		$extra_arg = $this->baseUrl;
		$total_messages = $pm_handler->getCount($criteria);

		$limit = 1;
		$pageNavi =& new XmobilePageNavigator($total_messages, $limit, 'start', $extra_arg);
		$criteria->setLimit($limit);
		$criteria->setStart($pageNavi->getStart());
		$pm_arr =& $pm_handler->getObjects($criteria);

		if (empty($pm_arr))
		{
			$has_message = false;
		}
		else
		{
			$has_message = true;

			$pm_handler->setRead($pm_arr[0]); // check read_msg
			$poster = new XoopsUser($pm_arr[0]->getVar('from_userid'));
			if ($poster->isActive())
			{
				$poster_name = $poster->getVar('uname');
				// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
//				$ext = 'uid='.$pm_arr[0]->getVar('from_userid');
				$ext = 'uid='.$poster->getVar('uid');
				$base_url = $this->utils->getLinkUrl('userinfo',null,null,$this->controller->sessionHandler->getSessionID(),$ext);
				$poster_name = '<a href="'.$base_url.'">'.$poster->getVar('uname').'</a>';
				$reply = true;
			}
			else
			{
				$poster_name = $xoopsConfig['anonymous'];
				$reply = false;
			}

			$ticket = new XoopsGTicket;

//			$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),null,$this->sessionHandler->getSessionID());
			$ext = 'send=1';
			$base_url = $this->utils->getLinkUrl('pmessage','confirm',null,$this->controller->sessionHandler->getSessionID(),$ext);
			$base_url = preg_replace('/&amp;/i','&',$base_url);

			$this->controller->render->template->assign('base_url',$base_url);
			$this->controller->render->template->assign('ticket_html',$ticket->getTicketHtml());
			$this->controller->render->template->assign('session_name',session_name());
			$this->controller->render->template->assign('session_id',session_id());
			$this->controller->render->template->assign('referer_url',$this->getBaseUrl());
			$this->controller->render->template->assign('subject',$pm_arr[0]->getVar('subject'));
			$this->controller->render->template->assign('poster_name',$poster_name);
			$this->controller->render->template->assign('msg_time',formatTimestamp($pm_arr[0]->getVar('msg_time')));
			$this->controller->render->template->assign('msg_text',$pm_arr[0]->getVar('msg_text'));
			$this->controller->render->template->assign('msg_id',$pm_arr[0]->getVar('msg_id'));
			$this->controller->render->template->assign('reply',$reply);

// Hack for inukshukGTD start
			global $xoopsModuleConfig,$xoopsUser;
			if( in_array("inukshukGTD",$xoopsModuleConfig['modules_can_use']) ){
				$this->controller->render->template->assign('inukshukGTD',1);
				include_once XOOPS_ROOT_PATH.'/modules/inukshukGTD/include/functions.php';
				$this->controller->render->template->assign("ProjectsList",get_projectslist($xoopsUser->uid()));
				$this->controller->render->template->assign("StartYear", StartYear('StartYear',date("Y")));
				$this->controller->render->template->assign("StartMonth", StartMonth('StartMonth',date("n")));
				$this->controller->render->template->assign("StartDay", StartDay('StartDay',date("j")));
				$this->controller->render->template->assign("StartHour", StartHour('StartHour',date("H")));
				$this->controller->render->template->assign("StartMin", StartMin('StartMin',intval(date("i")/10)*10));
			}
// Hack for inukshukGTD end

			// page navigation
/*
			$previous = $start - 1;
			$next = $start + 1;
			$pm_page_navi = '';
			if ($previous >= 0)
			{
				// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$ext = 'start='.$previous.'&total_messages='.$total_messages;
				$base_url = $this->utils->getLinkUrl('pmessage','detail',null,$this->sessionHandler->getSessionID(),$ext);
				$pm_page_navi .= '<a href="'.$base_url.'">'._MD_XMOBILE_PM_PREVIOUS.'</a>&nbsp;&nbsp;';
			}
			if ($next < $total_messages)
			{
				// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
				$ext = 'start='.$next.'&total_messages='.$total_messages;
				$base_url = $this->utils->getLinkUrl('pmessage','detail',null,$this->sessionHandler->getSessionID(),$ext);
				$pm_page_navi .= '<a href="'.$base_url.'">'._MD_XMOBILE_PM_NEXT.'</a>';
			}
			$this->controller->render->template->assign('pm_page_navi',$pm_page_navi);
*/
			$this->controller->render->template->assign('pm_page_navi',$pageNavi->renderNavi());
		}
		$this->controller->render->template->assign('message',$message);
		$this->controller->render->template->assign('has_message',$has_message);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getConfirmView()
	{
		global $xoopsModuleConfig,$xoopsDB;
		$myts =& MyTextSanitizer::getInstance();

		if (isset($_POST['cancel']))
		{
			$base_url = XMOBILE_URL.'/?act=pmessage&sess='.$this->sessionHandler->getSessionID();
			header('Location: '.$base_url);
			exit();
		}

// XOOPS Cube 2.1 の場合送信先入力方法を反映する
		$send_type = 0;
		if (preg_match("/^XOOPS Cube/",XOOPS_VERSION))
		{
			$module_handler =& xoops_gethandler('module');
			$pm_module =& $module_handler->getByDirName('pm');
			if (is_object($pm_module))
			{
				$pm_mid = $pm_module->getVar('mid');
			}
			$config_handler =& xoops_gethandler('config');
			$pm_moduleConfig =& $config_handler->getConfigsByCat(0, $pm_mid);
			$send_type = $pm_moduleConfig['send_type'];
		}
		$this->controller->render->template->assign('send_type',$send_type);
// XOOPS Cube 2.1 の場合送信先入力方法を反映する

		$this->controller->render->template->assign('show_edit',true);

		$op = $myts->makeTboxData4Show($this->utils->getGetPost('op', ''));
		$reply = intval($this->utils->getGetPost('reply', 0));
		$delete = intval($this->utils->getGetPost('delete', 0));
		$forward = intval($this->utils->getGetPost('forward', 0));
		$send = intval($this->utils->getGetPost('send', 0));
		$send2 = intval($this->utils->getGetPost('send2', 0));
		$to_userid = intval($this->utils->getGetPost('to_userid', ''));
		$msg_id = intval($this->utils->getGetPost('msg_id', 0));
		$subject = $myts->makeTboxData4Save($this->utils->getPost('subject', ''));
		$msg_text = $myts->makeTareaData4Save($this->utils->getPost('msg_text', ''),0,1,1);
		$session_id = $this->sessionHandler->getSessionID();
		$uid = $this->sessionHandler->getUid();

		if (isset($_POST['reply'])) $reply = 1;
		if (isset($_POST['delete'])) $delete = 1;
		if (isset($_POST['forward'])) $forward = 1;

// 不要？
/*
		if (empty($_GET['refresh'] ) && isset($_POST['op']) && $_POST['op'] != 'submit')
		{
			// $jumpの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
			$jump = '';
			if ($send == 1)
			{
				$jump .= 'send='.$send.'';
			}
			elseif ($send2 == 1)
			{
				$jump .= 'send2='.$send2.'&to_userid='.$to_userid.'';
			}
			elseif ($reply == 1)
			{
				$jump .= 'reply='.$reply.'&msg_id='.$msg_id.'';
			}
			$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),null,null,$this->sessionHandler->getSessionID(),$jump);
			$this->controller->render->redirectHeader($message,5,$base_url);
			exit();
		}
*/
		$ticket = new XoopsGTicket;
		$ticketCheck = $ticket->check(true,'',false);
		if ($uid)
		{
			if ( $op == 'submit' && $ticketCheck )
			{
				$res = $xoopsDB->query('SELECT COUNT(*) FROM '.$xoopsDB->prefix('users').' WHERE uid='.$to_userid);
				list($count) = $xoopsDB->fetchRow($res);
				if ($count != 1)
				{
					$base_url = $this->utils->getLinkUrl('pmessage',null,null,$this->sessionHandler->getSessionID());
					$this->controller->render->redirectHeader(_MD_XMOBILE_USERNOEXIST.'<br />'._MD_XMOBILE_PLZTRYAGAIN,5,$base_url);
					exit();
				}
				else
				{
					$pm_handler =& xoops_gethandler('privmessage');
					$pm =& $pm_handler->create();
					$pm->setVar('subject', $subject);
					$pm->setVar('msg_text', $msg_text);
					$pm->setVar('to_userid', $to_userid);
					$pm->setVar('from_userid', $uid);
					if (!$pm_handler->insert($pm))
					{
						$base_url = $this->utils->getLinkUrl('pmessage',null,null,$this->sessionHandler->getSessionID());
						$this->controller->render->redirectHeader($pm->getHtmlErrors(),5,$base_url);
						exit();
					}
					else
					{
						$base_url = $this->utils->getLinkUrl('pmessage',null,null,$this->sessionHandler->getSessionID());
						$this->controller->render->redirectHeader(_MD_XMOBILE_PM_MESSAGEPOSTED,5,$base_url);
						exit();
					}
				}

			}
			elseif ($delete == 1 && $ticketCheck )
			{
				$pm_handler =& xoops_gethandler('privmessage');
				$pm =& $pm_handler->get($msg_id);
				if (!is_object($pm) || $pm->getVar('to_userid') != $uid || !$pm_handler->delete($pm))
				{
					$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),null,null,$this->sessionHandler->getSessionID());
					$this->controller->render->redirectHeader(_MD_XMOBILE_DELETE_FAILED,5,$base_url);
					exit();
				}
				else
				{
					$base_url = $this->utils->getLinkUrl($this->controller->getActionState(),null,null,$this->sessionHandler->getSessionID());
					$this->controller->render->redirectHeader(_MD_XMOBILE_PM_DELETED,3,$base_url);
					exit();
				}
			}
			elseif ($reply == 1 || $send == 1 || $send2 == 1 || $forward==1)
			{
				$pm_uid = '';
				$pm_uname = '';
				$msg_text = '';

				include_once XOOPS_ROOT_PATH.'/include/xoopscodes.php';
				if ($reply == 1 || $forward==1)
				{
					$pm_handler =& xoops_gethandler('privmessage');
					$pm =& $pm_handler->get($msg_id);
					if ($pm->getVar('to_userid') == $uid)
					{
						$pm_uname = XoopsUser::getUnameFromId($pm->getVar('from_userid'));
						$msg_text = '>'.$pm->getVar('msg_text', 'E');
					}
					else
					{
						unset($pm);
						$reply = $send2 = 0;
					}
				}

				if ($reply == 1)
				{
					$pm_uid = $pm->getVar('from_userid');
				}
				elseif ($send2 == 1)
				{
					$pm_uid = $to_userid;
					$pm_uname = XoopsUser::getUnameFromId($to_userid);
				}
				else
				{
					$pm_uids = array();
					$i = 0;
					$result = $xoopsDB->query('SELECT uid, uname FROM '.$xoopsDB->prefix('users').' WHERE level > 0 ORDER BY uname');
//					while(list($ftouid, $ftouname) = $xoopsDB->fetchRow($result))
					while($row = $xoopsDB->fetchArray($result))
					{
//						$pm_uids[$i]['uid'] = $ftouid;
//						$pm_uids[$i]['uname'] = $myts->makeTboxData4Show($ftouname);
						$pm_uids[$i]['uid'] = intval($row['uid']);
						$pm_uids[$i]['uname'] = $myts->makeTboxData4Show($row['uname']);
						$i++;
					}
					$this->controller->render->template->assign('pm_uids',$pm_uids);
				}

				$subject = '';
				if ($reply == 1 || $forward==1)
				{
					$subject = $pm->getVar('subject', 'E');
					if ($reply == 1 && !preg_match('/^Re:/i',$subject))
					{
						$subject = 'Re: '.$subject;
					}
					if ($forward == 1 && !preg_match("/^Fwd:/i",$subject)) {
						$subject = 'Fwd: '.$subject;
					}
				}

				$base_url = $this->utils->getLinkUrl('pmessage','confirm',null,$this->controller->sessionHandler->getSessionID());
				$base_url = preg_replace('/&amp;/i','&',$base_url);
				$this->controller->render->template->assign('base_url',$base_url);
				$this->controller->render->template->assign('ticket_html',$ticket->getTicketHtml());
				$this->controller->render->template->assign('session_name',session_name());
				$this->controller->render->template->assign('session_id',session_id());
				$this->controller->render->template->assign('referer_url',$this->getBaseUrl());
				$this->controller->render->template->assign('msg_text',$msg_text);
				$this->controller->render->template->assign('reply',$reply);
				$this->controller->render->template->assign('send2',$send2);
				$this->controller->render->template->assign('pm_uid',$pm_uid);
				$this->controller->render->template->assign('pm_uname',$pm_uname);
				$this->controller->render->template->assign('subject',$subject);
				$this->controller->render->template->assign('tarea_cols',$xoopsModuleConfig['tarea_cols']);
				$this->controller->render->template->assign('tarea_rows',$xoopsModuleConfig['tarea_rows']);
				$this->controller->render->template->assign('from_userid', $uid);
			}
		}
		else
		{
			$base_url = $this->utils->getLinkUrl('register',null,null,$this->sessionHandler->getSessionID());
			$message = _MD_XMOBILE_PM_SORRY.'<br /><a href="'.$base_url.'">'._MD_XMOBILE_REGISTERNOW.'</a>.';
			$this->controller->render->redirectHeader($message,5,$base_url);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>