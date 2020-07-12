<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class UserinfoAction extends XmobileAction
{
	var $template = 'xmobile_userinfo.html';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function UserinfoAction()
	{
		global $xoopsConfig;
// XOOPS Cube 2.1 の場合、system/constants.php を読み込まない
		if (!preg_match("/^XOOPS Cube/",XOOPS_VERSION))
		{
			include_once XOOPS_ROOT_PATH . '/modules/system/constants.php';
		}
		include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/user.php';
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_MD_XMOBILE_ACCOUNT);
		$this->controller->render->template->assign('page_title',_MD_XMOBILE_ACCOUNT);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		global $xoopsModuleConfig;

		$myts =& MyTextSanitizer::getInstance();
		$request_uid = intval($this->utils->getGetPost('uid',0));
		$uid = $this->sessionHandler->getUid();

		if ($uid == 0)
		{
			$this->controller->render->redirectHeader(_MD_XMOBILE_NO_PERM_MESSAGE,3);
			exit();
		}

		if ($request_uid == 0)
		{
			$request_uid = $uid;
		}

		$member_handler =& xoops_gethandler('member');
		$requestUser =& $member_handler->getUser($request_uid);
		$this->controller->render->template->assign('requestUser',$requestUser);

		if (is_object($requestUser))
		{
			if (!$requestUser->isActive())
			{
				$this->controller->render->redirectHeader(_US_SELECTNG,3);
				exit();
			}
		}
		else
		{
			$this->controller->render->redirectHeader(_US_SELECTNG,3);
			exit();
		}

		$gperm_handler =& xoops_gethandler('groupperm');
		if (is_object($requestUser))
		{
			$groups = $requestUser->getGroups();
		}
		else
		{
			$groups = XOOPS_GROUP_ANONYMOUS;
		}

		if ($request_uid == $uid)
		{
			$is_user = true;
		}
		else
		{
			$is_user = false;
		}

		// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
		$ext = 'send2=1&to_userid='.$requestUser->getVar('uid');
		$send_pm_url = $this->utils->getLinkUrl('pmessage','confirm',null,$this->sessionHandler->getSessionID(),$ext);
		$this->controller->render->template->assign('send_pm_url',$send_pm_url);
		$this->controller->render->template->assign('send_to',sprintf(_MD_XMOBILE_SENDPMTO,$requestUser->getVar('uname')));

//		$userrank =& $requestUser->rank();
		$userrank = $requestUser->rank();
		if ($userrank['image'])
		{
			$user_rank_image = '<img src="'.XOOPS_UPLOAD_URL.'/'.$userrank['image'].'" alt="">';
			$this->controller->render->template->assign('user_rank_image',$user_rank_image);
		}

		$this->controller->render->template->assign('user_name',sprintf(_US_ALLABOUT,$requestUser->getVar('uname')));
		$this->controller->render->template->assign('user_realname',$requestUser->getVar('name'));
//		$this->controller->render->template->assign('user_website',$requestUser->getVar('url', 'E'));
		$this->controller->render->template->assign('user_website',$requestUser->getVar('url'));
		$this->controller->render->template->assign('user_viewemail',$requestUser->getVar('user_viewemail'));
		$this->controller->render->template->assign('is_user',$is_user);
//		$this->controller->render->template->assign('user_email',$requestUser->getVar('email', 'E'));
		$this->controller->render->template->assign('user_email',$requestUser->getVar('email'));
		$this->controller->render->template->assign('user_icq',$requestUser->getVar('user_icq'));
		$this->controller->render->template->assign('user_aim',$requestUser->getVar('user_aim'));
		$this->controller->render->template->assign('user_yim',$requestUser->getVar('user_yim'));
		$this->controller->render->template->assign('user_msnm',$requestUser->getVar('user_msnm'));
		$this->controller->render->template->assign('user_from',$requestUser->getVar('user_from'));
		$this->controller->render->template->assign('user_occ',$requestUser->getVar('user_occ'));
		$this->controller->render->template->assign('user_intrest',$requestUser->getVar('user_intrest'));
		$this->controller->render->template->assign('user_extrainfo',$myts->makeTareaData4Show($requestUser->getVar('bio', 'N'),0,1,1));
		$this->controller->render->template->assign('user_regdate',formatTimestamp($requestUser->getVar('user_regdate'),'s'));
		$this->controller->render->template->assign('user_rank',$userrank['title']);
		$this->controller->render->template->assign('user_posts',$requestUser->getVar('posts'));
		$this->controller->render->template->assign('last_login',formatTimestamp($requestUser->getVar('last_login'),'m'));
		$this->controller->render->template->assign('user_sig',$myts->makeTareaData4Show($requestUser->getVar('user_sig', 'N'),0,1,1));


		$module_handler =& xoops_gethandler('module');
		$criteria = new CriteriaCompo(new Criteria('hassearch', 1));
		$criteria->add(new Criteria('isactive', 1));
		// xmobileで利用可能なモジュール
		$plugin_modules = $this->utils->getMidsCanUse($this->sessionHandler->getUser());
		$criteria->add(new Criteria('mid', '('.implode(',', $plugin_modules).')', 'IN'));
		$criteria->setSort('weight');

		//$mids =& array_keys($module_handler->getList($criteria));
		$mids = array_keys($module_handler->getList($criteria));
//		$modules =& $module_handler->getObjects($criteria, true);

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'searched modules criteria', $criteria->render());

		$mods = array();
		foreach($mids as $mid)
		{
			if ($gperm_handler->checkRight('module_read', $mid, $groups))
			{
				$mid = intval($mid);
				$module =& $module_handler->get($mid);
				if (is_object($module))
				{
					$dirname = $module->getVar('dirname');
					// search の3番目の引数が表示する記事の件数、初期値は2
					$results =& $module->search('', '', 2, 0, $request_uid);
					$count = count($results);
					if (is_array($results) && $count > 0)
					{
						$mods[$mid]['name'] = $module->getVar('name');
						for($i = 0; $i < $count; $i++)
						{
							if (isset($results[$i]['image']) && $results[$i]['image'] != '')
							{
								$results[$i]['image'] = 'modules/'.$dirname.'/'.$results[$i]['image'];
							}
							else
							{
								$results[$i]['image'] = 'images/icons/posticon2.gif';
							}
							$link = $results[$i]['link'];
							$ret = preg_split('/\.php\?/',$link);
							$ext = $ret[1];
							// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
							// xoopsfaqとpiCalはxmobile用にクエリーを置換する必要がある為
							if ($dirname == 'xoopsfaq')
							{
								$ext = preg_replace("/^(cat_id=\d*)#(\d*)$/","$1&contents_id=$2",$ext);
							}
							elseif (preg_match('/^piCal\d*$/',$dirname))
							{
								$ext = preg_replace("/^action=View&amp;event_id=(\d*)$/","id=$1",$ext);
							}
							$title = $myts->makeTboxData4Show($results[$i]['title']);
							$title = mb_strimwidth($title, 0, $xoopsModuleConfig['max_title_length'], '..', SCRIPT_CODE);
							$baseUrl = $this->utils->getLinkUrl('plugin','detail',$dirname,$this->sessionHandler->getSessionID(),$ext);
							$mods[$mid]['user_posts'][$i]['link'] = '<a href="'.$baseUrl.'">'.$title.'</a>';
							if ($results[$i]['time'])
							{
								$mods[$mid]['user_posts'][$i]['time'] = formatTimestamp($results[$i]['time']);
							}
						}
					}
					if ($count == 2)
					{
						// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
						$ext = 'action=showallbyuser&mid='.$mid.'&search_uid='.$request_uid;
						$show_all_url = $this->utils->getLinkUrl('search',null,null,$this->sessionHandler->getSessionID(),$ext);
						$mods[$mid]['show_all'] = '<a href="'.$show_all_url.'">'._US_SHOWALL.'</a>';
					}
				}
			}
		}
		// debug
		$this->controller->render->template->assign('mods',$mods);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>