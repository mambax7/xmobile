<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
global $xoopsConfig;
include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/search.php';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class SearchAction extends XmobileAction
{
	var $template = 'xmobile_search.html';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function SearchAction()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_SR_SEARCHRESULTS);
		$this->controller->render->template->assign('page_title',_SR_SEARCHRESULTS);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		global $xoopsConfig,$xoopsModuleConfig;

		$myts =& MyTextSanitizer::getInstance();

		$query = $this->utils->getGetPost('query', '');
		$action = $myts->makeTboxData4Show($this->utils->getGetPost('action', 'results'));
		$andor = $myts->makeTboxData4Show($this->utils->getGetPost('andor', 'AND'));
		$mid = intval($this->utils->getGetPost('mid', 0));
		$search_uid = intval($this->utils->getGetPost('search_uid', 0));
		$start = intval($this->utils->getGetPost('start', 0));

		$config_handler =& xoops_gethandler('config');
		$xoopsConfigSearch =& $config_handler->getConfigsByCat(XOOPS_CONF_SEARCH);

		if ($xoopsConfigSearch['enable_search'] != 1)
		{
			$baseUrl = $this->utils->getLinkUrl('default',null,null,$this->sessionHandler->getSessionID());
			header('Location: '.$baseUrl);
			exit();
		}

		if ($action == 'results')
		{
			if ($query == '')
			{
				$this->controller->render->redirectHeader(_SR_PLZENTER,5);
				exit();
			}
		}
		elseif ($action == 'showall')
		{
			if ($query == '' || empty($mid))
			{
				$this->controller->render->redirectHeader(_SR_PLZENTER,5);
				exit();
			}
		}
		elseif ($action == 'showallbyuser')
		{
			if (empty($mid) || empty($search_uid))
			{
				$this->controller->render->redirectHeader(_SR_PLZENTER,5);
				exit();
			}
		}

		$member_handler =& xoops_gethandler('member');
		$user =& $member_handler->getUser($search_uid);
		$groups = is_object($user) ? $user->getGroups() : XOOPS_GROUP_ANONYMOUS;
		$gperm_handler = & xoops_gethandler( 'groupperm');
		$queries = array();
		$ignored_queries = array();

		if ($andor != 'OR' && $andor != 'exact' && $andor != 'AND')
		{
			$andor = 'AND';
		}

		if ($action != 'showallbyuser')
		{
			if ($andor != 'exact')
			{
				$temp_queries = preg_split('/[\s]+/', $query);
				foreach($temp_queries as $q)
				{
					$q = trim($q);
					if (strlen($q) >= $xoopsConfigSearch['keyword_min'])
					{
						$queries[] = $myts->makeTboxData4Show($q);
					}
					else
					{
						$ignored_queries[] = $myts->makeTboxData4Show($q);
					}
				}
				if (count($queries) == 0)
				{
					$lang_keytoshort = sprintf(_SR_KEYTOOSHORT, $xoopsConfigSearch['keyword_min']);
					$this->controller->render->template->assign('lang_keytoshort',$lang_keytoshort);
					return;
				}
			}
			else
			{
				$query = trim($query);
				if (strlen($query) < $xoopsConfigSearch['keyword_min'])
				{
					$lang_keytoshort = sprintf(_SR_KEYTOOSHORT, $xoopsConfigSearch['keyword_min']);
					$this->controller->render->template->assign('lang_keytoshort',$lang_keytoshort);
					return;
				}
				$queries = array($myts->makeTboxData4Show($query));
			}
		}

		$this->controller->render->template->assign('queries',$queries);
		$this->controller->render->template->assign('ignored_queries',$ignored_queries);

		switch ($action)
		{
			case 'results':

				$this->controller->render->template->assign('show_results',true);

				$module_handler =& xoops_gethandler('module');
				$criteria = new CriteriaCompo(new Criteria('hassearch', 1));
				$plugin_modules = $this->utils->getMidsCanUse($this->sessionHandler->getUser());
				$criteria->add(new Criteria('mid', '('.implode(',', $plugin_modules).')', 'IN'));
				$criteria->setSort('weight');

				$extra = 'action=results&query='.urlencode(stripslashes(implode(' ', $queries))).'&andor='.$andor;
				$extra_arg = $this->utils->getLinkUrl($this->controller->getActionState(),$this->controller->getViewState(),null,$this->sessionHandler->getSessionID(),$extra);
				$module_count = $module_handler->getCount($criteria);
				$page_navi =& new XmobilePageNavigator($module_count, $xoopsModuleConfig['search_title_row'], 'start', $extra_arg);

				$criteria->setLimit($page_navi->getPerpage());
				$criteria->setStart($page_navi->getStart());

				// debug
				$this->utils->setDebugMessage(__CLASS__, 'search criteria', $criteria->render());
				$this->utils->setDebugMessage(__CLASS__, 'search module_count', $module_count);

				$modules =& $module_handler->getObjects($criteria, true);

				if (!empty($ignored_queries))
				{
					$ignored_word = sprintf(_SR_IGNOREDWORDS, $xoopsConfigSearch['keyword_min']);
					$this->controller->render->template->assign('ignored_word',$ignored_word);
				}

				$mods = array();
				// 一覧表示でのモジュール毎の検索結果の最大表示件数を変更する場合は下の$limitの数字を任意の数字に変更してください。
				$limit = 3;
				foreach($modules as $module)
				{
					$mid = $module->getVar('mid');
					$dirname = $module->getVar('dirname');
					$results =& $module->search($queries, $andor, $limit, 0);
					$mods[$mid]['name'] = $myts->makeTboxData4Show($module->getVar('name'));
					$count = count($results);
					if (!is_array($results) || $count == 0)
					{
						$mods[$mid]['no_match'] = true;
					}
					else
					{
						$mods[$mid]['no_match'] = false;
						$link_list = '';
						for($i = 0; $i < $count; $i++)
						{
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
							$itemUrl = $this->utils->getLinkUrl('plugin','detail',$module->getVar('dirname'),$this->sessionHandler->getSessionID(),$ext);
							$link_list .= '<a href="'.$itemUrl.'">'.$title.'</a><br />';
							$mods[$mid]['link'][$i] = '<a href="'.$itemUrl.'">'.$title.'</a>';
						}

						if ($count == $limit)
						{
							$ext = 'query='.urlencode(stripslashes(implode(' ', $queries)));
							// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
							$ext .= '&mid='.$mid.'&action=showall&andor='.$andor;
							$searchUrl = $this->utils->getLinkUrl('search',null,null,$this->sessionHandler->getSessionID(),$ext);
							$mods[$mid]['show_all'] = '<a href="'.$searchUrl.'">'._SR_SHOWALLR.'</a>';
						}
						else
						{
							$mods[$mid]['show_all'] = false;
						}
					}
				}
				$this->controller->render->template->assign('mods',$mods);
				$this->controller->render->template->assign('page_navi',$page_navi->renderNavi());
				break;

			case 'showall':

			case 'showallbyuser':

				$module_handler =& xoops_gethandler('module');
				$module =& $module_handler->get($mid);
				// 詳細表示でのモジュール毎の検索結果の最大表示件数を変更する場合は下の$limitの数字を任意の数字に変更してください。
				$limit = 10;
				$results =& $module->search($queries, $andor, $limit, $start, $search_uid);
				$message = '';

				$count = count($results);
				if (is_array($results) && $count > 0)
				{
					$next_results =& $module->search($queries, $andor, 1, $start + $limit, $search_uid);
					$next_count = count($next_results);
					$has_next = false;
					if (is_array($next_results) && $next_count == 1)
					{
						$has_next = true;
					}
					if ($action == 'showall')
					{
						$this->controller->render->template->assign('show_all',true);
					}
					else
					{
						$this->controller->render->template->assign('show_allbyuser',true);
					}

					$mod_name = $myts->makeTboxData4Show($module->getVar('name'));
					$dirname = $module->getVar('dirname');
					$showing = sprintf(_SR_SHOWING, $start+1, $start + $count);
					$links = array();
					for($i = 0; $i < $count; $i++)
					{
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

						$links[$i]['url'] = $this->utils->getLinkUrl('plugin','detail',$module->getVar('dirname'),$this->sessionHandler->getSessionID(),$ext);
						$title = $myts->makeTboxData4Show($results[$i]['title']);
						$links[$i]['title'] = mb_strimwidth($title, 0, $xoopsModuleConfig['max_title_length'], '..', SCRIPT_CODE);

						$results[$i]['uid'] = intval($results[$i]['uid']);
						if (!empty($results[$i]['uid']))
						{
							$uname = XoopsUser::getUnameFromId($results[$i]['uid']);
							$links[$i]['uname'] = $uname;
						}
						$links[$i]['time'] = $results[$i]['time'] ? '&nbsp;('.formatTimestamp(intval($results[$i]['time'])).')' : '';
					}

					// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
					$ext = 'query='.urlencode(stripslashes(implode(' ', $queries)));
					$ext .= '&mid='.$mid.'&action='.$action.'&andor='.$andor;
					if ($action=='showallbyuser')
					{
						$ext .= '&search_uid='.$this->sessionHandler->getUid();
					}

					$page_navi = '';
					if ($start > 0)
					{
						$prev = $start - $limit;
						$ext .= '&start='.$prev;
						$baseUrl = $this->utils->getLinkUrl('search',null,null,$this->sessionHandler->getSessionID(),$ext);
						$page_navi .= '<a href="'.$baseUrl.'">'._SR_PREVIOUS.'</a>&nbsp;';
					}
					if (false != $has_next)
					{
						$next = $start + $limit;
						// $extの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
						$ext .= '&start='.$next;
						$baseUrl = $this->utils->getLinkUrl('search',null,null,$this->sessionHandler->getSessionID(),$ext);
						$page_navi .= '<a href="'.$baseUrl.'">'._SR_NEXT.'</a>';
					}

					$this->controller->render->template->assign('queries',$queries);
					$this->controller->render->template->assign('ignored_queries',$ignored_queries);
					$this->controller->render->template->assign('mod_name',$mod_name);
					$this->controller->render->template->assign('showing',$showing);
					$this->controller->render->template->assign('links',$links);
					$this->controller->render->template->assign('page_navi',$page_navi);
				}
				else
				{
					$this->controller->render->template->assign('no_match',_SR_NOMATCH);
				}

				break;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>