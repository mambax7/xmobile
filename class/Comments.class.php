<?php
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//include_once XOOPS_ROOT_PATH.'/class/xoopscomments.php';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//class XmobileComments extends XoopsComments
class XmobileComments
{
	var $controller;
	var $pluginHandler;
	var $sessionHandler;

	var $ticket;
	var $baseUrl;
	var $db;
	var $ctable;
	var $session_id;
	var $uid = 0;
	var $user;
	var $myts;
	var $module_dir;
	var $module;
	var $mid;
	var $module_config;
	var $comment_config;
	var $comment_rule;
	var $comment_perm = 0; // 0:cant post,1:can post,2:can post and edit,2:(admin)can post and edit and delete all
	var $comment_approve = 0; // 0:pending,1:active
	var $comment_status = 0;
	var $com_dborder = 'DESC';
	var $statusText;
	var $com_edit_link;
	var $com_delete_link;
	var $com_reply_link;
	var $comment;
	var $com_id;
	var $com_uid;
	var $com_itemid;
	var $com_itemid_field;
	var $com_item_cat_id;
	var $com_item_cat_id_field;
	var $com_title;
	var $com_text;
	var $com_pid;
	var $com_status;
	var $com_rootid;
	var $dohtml;
	var $dosmiley;
	var $dobr;
	var $doxcode;
	var $com_icon;
	var $com_count = 0;
	var $start = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function XmobileComments($module_dir=null,$com_itemid=null)
	function XmobileComments(&$controller, &$pluginHandler, $com_itemid=null,$cat_id=0,$start=0)
	{
		$this->controller = $controller;
		$this->pluginHandler = $pluginHandler;
		$this->sessionHandler =& $this->controller->getSessionHandler();

		global $xoopsConfig;
		include_once XOOPS_ROOT_PATH.'/include/comment_constants.php';
//		include_once XOOPS_ROOT_PATH.'/modules/system/constants.php';
		include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/comment.php';

		$this->db =& Database::getInstance();
		$this->ctable = $this->db->prefix('xoopscomments');
		$this->myts =& MyTextSanitizer::getInstance();
		$this->session_id = $this->sessionHandler->getSessionID();
		$this->uid = $this->sessionHandler->getUid();

		$this->module_dir = $this->pluginHandler->getModuleDir();
		$module_handler =& xoops_gethandler('module');
		$this->module =& $this->pluginHandler->getModuleObject();
		$this->mid = $this->pluginHandler->getMid();
		$this->module_config =& $this->pluginHandler->getModuleConfig();

		$this->com_itemid = intval($com_itemid);
		$this->com_item_cat_id = intval($cat_id);
		$this->com_item_cat_id_field = $this->pluginHandler->getCategoryIdField();
		$this->start = intval($start);

		// com_order
		if ($xoopsConfig['com_order'] == 1)
		{
			$this->com_dborder = 'DESC';
		}
		else
		{
			$this->com_dborder = 'ASC';
		}

		$this->checkCommentRule();
//		$this->setBaseUrl();

		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/gtickets.php';
		$this->ticket =& new XoopsGTicket;

		if (file_exists(XOOPS_ROOT_PATH.'/modules/'.$this->module_dir.'/include/comment_functions.php'))
		{
			include_once XOOPS_ROOT_PATH.'/modules/'.$this->module_dir.'/include/comment_functions.php';
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setBaseUrl()
	{
//		$this->start = intval($this->controller->utils->getGetPost('start', 0));
//		$this->com_itemid = intval($this->controller->utils->getGetPost('com_itemid', 0));

		// $extraの値はgetLinkUrl()でhtmlspecialchars()を掛けられるので&amp;ではなく&と記述しておく
		$extra = 'start='.$this->start.'&'.$this->com_item_cat_id_field.'='.$this->com_item_cat_id.'&'.$this->com_itemid_field.'='.$this->com_itemid;
		$this->baseUrl = $this->controller->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
		// debug
//		$this->controller->utils->setDebugMessage(__CLASS__, 'setBaseUrl', $this->baseUrl);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkCommentRule()
	{
		$this->comment_status = XOOPS_COMMENT_PENDING;

		if (empty($this->module_config['com_rule']))
			$this->module_config['com_rule'] = '';

		if ($this->module_config['com_rule'] != XOOPS_COMMENT_APPROVENONE)
		{
			$gperm_handler = & xoops_gethandler( 'groupperm' );
			$member_handler =& xoops_gethandler('member');
			$this->user =& $member_handler->getUser($this->uid);
			$groups = ($this->user) ? $this->user->getGroups() : XOOPS_GROUP_ANONYMOUS;
// XOOPS Cube の場合、constants.php を読み込まずに、定数 XOOPS_SYSTEM_COMMENT の数字 14 を直接指定した？
//			$xoops_iscommentadmin = $gperm_handler->checkRight( 'system_admin', XOOPS_SYSTEM_COMMENT, $groups);
			$xoops_iscommentadmin = $gperm_handler->checkRight( 'system_admin', 14, $groups);
			$this->comment_config = $this->module->getInfo('comments');
			$this->com_itemid_field = trim($this->comment_config['itemName']);

			if (!empty($this->module_config['com_anonpost']) || is_object($this->user))
			{
				$this->comment_perm = 1;
			}

			$this->comment_rule = 1;

			if ($this->module_config['com_rule'] == XOOPS_COMMENT_APPROVEALL)
			{
				$this->comment_approve = 1;
				$this->comment_status = XOOPS_COMMENT_ACTIVE;
			}
			elseif (($this->module_config['com_rule'] == XOOPS_COMMENT_APPROVEUSER) && ($this->uid != 0))
			{
				$this->comment_approve = 1;
				$this->comment_status = XOOPS_COMMENT_ACTIVE;
			}
			elseif ($this->module_config['com_rule'] == XOOPS_COMMENT_APPROVEADMIN && is_object($this->user) && $this->user->isAdmin($this->mid))
			{
				$this->comment_approve = 1;
				$this->comment_status = XOOPS_COMMENT_ACTIVE;
			}
		}
		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'checkCommentRule comment_approve', $this->comment_approve);
		$this->controller->utils->setDebugMessage(__CLASS__, 'checkCommentRule comment_status', $this->comment_status);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//function checkCommentPermission($com_id,$com_uid)
	function checkCommentPermission($com_uid=0)
	{
		if (!empty($this->module_config['com_anonpost']) || is_object($this->user))
		{
			$this->comment_perm = 1;
		}
		if (is_object($this->user) && $com_uid == $this->uid)
		{
			$this->comment_perm = 2;
		}
		if (is_object($this->user) && $this->user->isAdmin($this->mid))
		{
			$this->comment_perm = 3;
		}
		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'checkCommentPermission comment_perm', $this->comment_perm);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function makeCommentLink($comments)
	function makeCommentLink()
	{
		$myts =& MyTextSanitizer::getInstance();
		$com_op = $myts->makeTboxData4Show($this->controller->utils->getGetPost('com_op', ''));
		$com_id = intval($this->controller->utils->getGetPost('com_id', 0));
		$com_pid = intval($this->controller->utils->getGetPost('com_pid', 0));

		$this->setBaseUrl();

		$comment_link = false;

//		$this->checkCommentRule();

		if ($this->comment_rule == 0)
		{
			return false;
		}

		$this->checkCommentPermission();
		if ($this->comment_perm >= 1)
		{
//			$comment_post_link = '<a href="'.$this->baseUrl.'&amp;com_op=post_new#comment">'._MD_XMOBILE_POST_COMMENT.'</a>';
			$comment_post_link = '<a href="'.$this->baseUrl.'&amp;com_op=post_new">'._MD_XMOBILE_POST_COMMENT.'</a>';
		}
		else
		{
			$comment_post_link = '';
		}

		// 有効なコメント数の取得、未承認のコメントは含まず
		$comment_handler =& xoops_gethandler('comment');
//		$comments_count = $comment_handler->getCountByItemId($this->mid, $this->com_itemid, XOOPS_COMMENT_ACTIVE);
		$comments = $comment_handler->getByItemId($this->mid, $this->com_itemid, $this->com_dborder, XOOPS_COMMENT_ACTIVE);
		$comments_count = count($comments);

		if ($comments_count > 0)
		{
//			$comment_list_link = '<a href="'.$this->baseUrl.'&amp;com_op=list#comment">'.sprintf(_MD_XMOBILE_COMMENT_COUNT,$comments_count).'</a>';
			$comment_list_link = '<a href="'.$this->baseUrl.'&amp;com_op=list">'.sprintf(_MD_XMOBILE_COMMENT_COUNT,$comments_count).'</a>';
		}

		switch ($com_op)
		{
			case 'list': // 一覧表示
				$comment_link .= $comment_post_link;
				$comment_link .= $this->showList();
				break;

			case 'post_new': // 新規コメント投稿
				$comment_link .= $this->renderCommentForm('post_new');
				break;

			case 'reply': // コメント返信
				$this->getComment($com_pid);
				$comment_link .= $this->renderCommentForm('reply');
				break;

			case 'edit': // コメント編集
				$this->getComment($com_id);
				$comment_link .= $this->renderCommentForm('edit');
				break;

			case 'delete': // コメント削除
				$this->getComment($com_id);
				$comment_link .= $this->renderDeleteCommentForm();
				break;

			case 'save': // コメント更新
				$comment_link .= $this->save($com_id);
				$comment_link .= '<br />';
//				$comment_link .= $comment_post_link;
				$comment_link .= $this->showList();
				break;

			default:
//				if ($comments != 0)
				if ($comments_count > 0)
				{
					$comment_link .= $comment_list_link.'<br />';
				}
				$comment_link .= $comment_post_link;
				break;
		}

		// debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'makeCommentLink com_op', $com_op);

		return $comment_link;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// 一覧表示
	//function showList($com_itemid)
	function showList()
	{
		global $xoopsModuleConfig;
		require_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/Utils.class.php';
		$this->utils =& XmobileUtils::getInstance();

		$statusText = array(
			XOOPS_COMMENT_PENDING => _CM_PENDING,
			XOOPS_COMMENT_ACTIVE => _CM_ACTIVE,
			XOOPS_COMMENT_HIDDEN => _CM_HIDDEN
		);

		$comment_handler =& xoops_gethandler('comment');

		$extra_arg = $this->baseUrl.'&com_op=list';
//		$comments_count = $comment_handler->getCountByItemId($this->mid, $this->com_itemid, XOOPS_COMMENT_ACTIVE);
		$comments = $comment_handler->getByItemId($this->mid, $this->com_itemid, $this->com_dborder, XOOPS_COMMENT_ACTIVE);
		$comments_count = count($comments);
		$pageNavi =& new XmobilePageNavigator($comments_count, $xoopsModuleConfig['comment_title_row'], 'com_start', $extra_arg);

//		$comments = $comment_handler->getByItemId($this->mid, $this->com_itemid, $this->com_dborder, null, $xoopsModuleConfig['comment_title_row'], $pageNavi->getStart());
		$comments = $comment_handler->getByItemId($this->mid, $this->com_itemid, $this->com_dborder, XOOPS_COMMENT_ACTIVE, $xoopsModuleConfig['comment_title_row'], $pageNavi->getStart());

		$html_ret = '<hr />'._MD_XMOBILE_COMMENT_LIST.'<br />';
		foreach($comments as $comment)
		{
			$com_id = $comment->getVar('com_id');
			$com_uid = $comment->getVar('com_uid');
			$com_uname = $this->controller->utils->getUnameFromId($com_uid);
			$com_title = $comment->getVar('com_title');
			$com_text = $comment->getVar('com_text');
			$com_pid = $comment->getVar('com_pid');
			$com_status = $comment->getVar('com_status');
			$com_rootid = $comment->getVar('com_rootid');
			$com_created = $comment->getVar('com_created');
			$com_ip = $comment->getVar('com_ip');


			$html_ret .= '-------------------<br />';
			$html_ret .= _MD_XMOBILE_TITLE.$com_title.'<br />';
			$html_ret .= _CM_POSTER.': '.$com_uname.'<br />';
			$html_ret .= _CM_POSTED.': '.$this->utils->getDateLong($com_created).' '.$this->utils->getTimeLong($com_created).'<br />';

			$this->checkCommentPermission($com_uid);

			if ($this->comment_perm == 3)
			{
				$html_ret .= _CM_STATUS.': '.$statusText[$com_status].'<br />';
				$html_ret .= 'IP: '.$com_ip.'<br />';
				$html_ret .= _MD_XMOBILE_CONTENTS.$com_text;
			}
			else // hide comments that are not active
			{
				if (XOOPS_COMMENT_ACTIVE != $com_status)
				{
					continue;
				}
				else
				{
					$html_ret .= _MD_XMOBILE_CONTENTS.': '.$com_text;
				}
			}
			$html_ret .= '<br />';


			if ($this->comment_perm == 3)// admin
			{
//				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=reply&amp;com_pid='.$com_id.'#comment">'._REPLY.'</a>&nbsp;';
//				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=edit&amp;com_id='.$com_id.'#comment">'._EDIT.'</a>&nbsp;';
//				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=delete&amp;com_id='.$com_id.'#comment">'._DELETE.'</a>';
				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=reply&amp;com_pid='.$com_id.'">'._REPLY.'</a>&nbsp;';
				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=edit&amp;com_id='.$com_id.'">'._EDIT.'</a>&nbsp;';
				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=delete&amp;com_id='.$com_id.'">'._DELETE.'</a>';
			}
			elseif ($this->comment_perm == 2)// poster
			{
//				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=reply&amp;com_pid='.$com_id.'#comment">'._REPLY.'</a>&nbsp;';
//				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=edit&amp;com_id='.$com_id.'#comment">'._EDIT.'</a>&nbsp;';
				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=reply&amp;com_pid='.$com_id.'">'._REPLY.'</a>&nbsp;';
				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=edit&amp;com_id='.$com_id.'">'._EDIT.'</a>&nbsp;';
			}
			elseif ($this->comment_perm == 1)
			{
//				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=reply&amp;com_pid='.$com_id.'#comment">'._REPLY.'</a>&nbsp;';
				$html_ret .= '<a href="'.$this->baseUrl.'&amp;com_op=reply&amp;com_pid='.$com_id.'">'._REPLY.'</a>&nbsp;';
			}
//			$html_ret .= '<hr />';
			$html_ret .= '<br />';
		}

		$com_page_navi = $pageNavi->renderNavi();
		if ($com_page_navi != '')
		{
			$html_ret .= '<hr />'.$com_page_navi;
		}
//		$html_ret = preg_replace("/<hr \/>$/","",$html_ret);
		return $html_ret;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// コメント保存
	function save($com_id=0)
	{
		$form_op = isset($_POST['form_op']) ? xoops_trim($_POST['form_op']) : '';
		$this->com_itemid = intval($this->controller->utils->getPost('com_itemid', 0));
		$comment_post_results = 0;
		$comment_handler =& xoops_gethandler('comment');

		if (isset($_POST['cancel']))
		{
//			$extra = 'start='.$this->start.'&'.$this->com_item_cat_id_field.'='.$this->com_item_cat_id.'&'.$this->com_itemid_field.'='.$this->com_itemid;
//			$baseUrl = $this->controller->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
			// header()では&amp;ではなく&と記述する必要がある？
			$baseUrl = XMOBILE_URL.'/?act='.$this->controller->getActionState().'&view=detail&plg='.$this->controller->getPluginState();
			if ($this->sessionHandler->getSessionID() != '')
			{
				$baseUrl .= '&sess='.$this->sessionHandler->getSessionID();
			}
			$baseUrl .= '&start='.$this->start.'&'.$this->com_item_cat_id_field.'='.$this->com_item_cat_id.'&'.$this->com_itemid_field.'='.$this->com_itemid;
			if ($form_op != 'post_new')
			{
				$baseUrl .= '&com_op=list';
			}
			header('Location: '.$baseUrl);
			exit();
		}

		//チケットの確認
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
			$this->controller->render->redirectHeader($this->ticket->getErrors(),5);
//			$this->controller->render->redirectHeader(_MD_XMOBILE_TICKET_ERROR,5);
			exit();
		}

		if ($com_id == 0)
		{
			$com_uid = 0;
		}
		else
		{
			$comment = $comment_handler->get($com_id);
			$com_uid = $comment->getVar('com_uid');
	//		unset ($comment);
		}

		$this->checkCommentPermission($com_uid);

		switch ($form_op)
		{
			case 'post_new':
				if ($this->comment_perm < 1)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}

				$comment = $this->createComment();
				if ($comment_handler->insert($comment))
				{
					$newcid = $comment->getVar('com_id');
					$com_rootid = $comment->getVar('com_rootid');
					// set own id as root id if this is a top comment
					if ($com_rootid == 0)
					{
						$com_rootid = $newcid;
						if (!$comment_handler->updateByField($comment, 'com_rootid', $com_rootid))
						{
							$comment_handler->delete($comment);
						}
					}
					$comment_post_results = _CM_THANKSPOST;
				}
				else
				{
	//				$comment_post_results = $comment->getErrors();
	//				$comment_post_results = xoops_error($comment->getHtmlErrors());
					$comment_post_results = $comment->getHtmlErrors();
				}

				$this->com_count = 1;

				break;

			case 'reply':
				if ($this->comment_perm < 1)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}

				$comment = $this->createComment();
				if ($comment_handler->insert($comment))
				{
					$comment_post_results = _CM_THANKSPOST;
				}
				else
				{
					$comment_post_results = $comment->getHtmlErrors();
				}

				$this->com_count = 1;

				break;

			case 'edit':
				if ($this->comment_perm < 2)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$comment = $comment_handler->get($com_id);

				if ($this->updateComment($com_id))
				{
					$comment_post_results = _MD_XMOBILE_UPDATE_SUCCESS;
				}
				else
				{
					$comment_post_results = $comment->getHtmlErrors();
				}
				break;

			case 'delete':
				if ($this->comment_perm < 3)
				{
					return _MD_XMOBILE_NO_PERM_MESSAGE;
				}
				$comment_post_results = $this->deleteComment($com_id);

				$this->com_count = -1;

				break;

		}

	/*
		if ($form_op !== 'delete')
		{
			$this->updateCustom($com_id,$form_op);
		}
	*/
		return $comment_post_results;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// コメント取得
	function getComment($com_id)
	{
		$comment_handler =& xoops_gethandler('comment');
		$comment = $comment_handler->get($com_id);

		$this->com_id = $comment->getVar('com_id');
		$this->com_uid = $comment->getVar('com_uid');
		$this->com_itemid = $comment->getVar('com_itemid');
		$this->com_title = $comment->getVar('com_title', 'E');
		$this->com_text = $comment->getVar('com_text', 'E');
		$this->com_pid = $comment->getVar('com_pid');
		$this->com_status = $comment->getVar('com_status');
		$this->com_rootid = $comment->getVar('com_rootid');
		$this->dohtml = $comment->getVar('dohtml');
		$this->dosmiley = $comment->getVar('dosmiley');
		$this->dobr = $comment->getVar('dobr');
		$this->doxcode = $comment->getVar('doxcode');
		$this->com_icon = $comment->getVar('com_icon');
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function createComment()
	{
		$com_title = $this->myts->makeTboxData4Save(trim($this->controller->utils->getPost('com_title', _NOTITLE)));
		$com_text = $this->myts->makeTboxData4Save(trim($this->controller->utils->getPost('com_text', '')));
		$com_pid = intval($this->controller->utils->getPost('com_pid', 0));
		$com_rootid = intval($this->controller->utils->getPost('com_rootid', 0));

		$com_uid = $this->uid;
		$com_itemid = $this->com_itemid;
		$dohtml = 0;
		$dosmiley = 0;
		$doxcode = 0;
		$doimage = 0;
		$dobr = 1;
		$com_icon = '';
		$com_modid = $this->mid;
		$com_status = $this->comment_status;

		$comment_handler =& xoops_gethandler('comment');
		$comment = $comment_handler->create();

	//	$comment->setVar('com_id', $com_id);
		$comment->setVar('com_uid', $com_uid);
		$comment->setVar('com_itemid', $com_itemid);
		$comment->setVar('com_title', $com_title);
		$comment->setVar('com_text', $com_text);
		$comment->setVar('com_pid', $com_pid);
		$comment->setVar('com_status', $com_status);
		$comment->setVar('com_rootid', $com_rootid);
		$comment->setVar('dohtml', $dohtml);
		$comment->setVar('dosmiley', $dosmiley);
		$comment->setVar('doxcode', $doxcode);
		$comment->setVar('doimage', $doimage);
		$comment->setVar('dobr', $dobr);
		$comment->setVar('com_icon', $com_icon);
		$comment->setVar('com_modified', time());
		$comment->setVar('com_modid', $com_modid);
		$comment->setVar('com_created', time());
		$comment->setVar('com_ip', xoops_getenv('REMOTE_ADDR'));
		if (!empty($extra_params))
		{
			$comment->setVar('com_exparams', str_replace('&amp;', '&', $extra_params));
		}

		return $comment;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function updateComment($com_id)
	{
		$comment_handler =& xoops_gethandler('comment');
		$comment = $comment_handler->get($com_id);
		$com_text = isset($_POST['com_text']) ? $this->myts->makeTareaData4Save($_POST['com_text']) : '';

		$comment_handler->updateByField($comment, 'com_text', $com_text);
		$comment_handler->updateByField($comment, 'com_modified', time());
		$comment_handler->updateByField($comment, 'com_ip', xoops_getenv('REMOTE_ADDR'));
		return true;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function updateCustom($com_id,$form_op)
	{
		$comment_handler =& xoops_gethandler('comment');
		$comment = $comment_handler->get($com_id);

		// call custom approve function if any
		if (false != $this->comment_approve && isset($this->comment_config['callback']['approve']) && trim($this->comment_config['callback']['approve']) != '')
		{
			$skip = false;
			if (!function_exists($this->comment_config['callback']['approve']))
			{
				if (isset($this->comment_config['callbackFile']))
				{
					$callbackfile = trim($this->comment_config['callbackFile']);
					if ($callbackfile != '' && file_exists(XOOPS_ROOT_PATH.'/modules/'.$this->module_dir.'/'.$callbackfile))
					{
						include_once XOOPS_ROOT_PATH.'/modules/'.$this->module_dir.'/'.$callbackfile;
					}
					if (!function_exists($this->comment_config['callback']['approve']))
					{
						$skip = true;
					}
				}
				else
				{
					$skip = true;
				}
			}
			if (!$skip)
			{
				$this->comment_config['callback']['approve']($comment);
			}
		}

		// call custom update function if any
		if (false != $this->comment_approve && isset($this->comment_config['callback']['update']) && trim($this->comment_config['callback']['update']) != '')
		{
			$skip = false;
			if (!function_exists($this->comment_config['callback']['update']))
			{
				if (isset($this->comment_config['callbackFile']))
				{
					$callbackfile = trim($this->comment_config['callbackFile']);
					if ($callbackfile != '' && file_exists(XOOPS_ROOT_PATH.'/modules/'.$this->module_dir.'/'.$callbackfile))
					{
						include_once XOOPS_ROOT_PATH.'/modules/'.$this->module_dir.'/'.$callbackfile;
					}
					if (!function_exists($this->comment_config['callback']['update']))
					{
						$skip = true;
					}
				}
				else
				{
					$skip = true;
				}
			}
			if (!$skip)
			{
				$criteria = new CriteriaCompo(new Criteria('com_modid', $this->mid));
				$criteria->add(new Criteria('com_itemid', $this->com_itemid));
				$criteria->add(new Criteria('com_status', XOOPS_COMMENT_ACTIVE));
				$comment_handler = xoops_gethandler('comment');
				$comment_count = $comment_handler->getCount($criteria);
	//			$this->comment_config['callback']['update']($this->com_itemid, $comment_count);
	//weblog_com_update($this->com_itemid, $comment_count);
	//  $db =& Database::getInstance();
	//  $sql = 'UPDATE '.$db->prefix('weblog').' SET comments = '.$comment_count.' WHERE blog_id = '.$this->com_itemid;
	//  $db->query($sql);

				$func = $this->comment_config['callback']['update'];
	//			call_user_func_array($func, array($this->com_itemid, $comment_count, $comment->getVar('com_id')));
				call_user_func_array($func, array($this->com_itemid, $comment_count, $com_id));
			}
		}

		// increment user post if needed
	//	$uid = $comment->getVar('com_uid');
	//	if ($uid > 0 && $this->comment_perm > 0)
		if ($this->com_uid > 0 && $this->comment_perm > 0)
		{
			$member_handler =& xoops_gethandler('member');
	//		$poster =& $member_handler->getUser($uid);
			$poster =& $member_handler->getUser($this->uid);
			if (is_object($poster))
			{
				$member_handler->updateUserByField($poster, 'posts', $poster->getVar('posts') + 1);
			}
		}

	/*
		// RMV-NOTIFY
		// trigger notification event if necessary
	//	if ($notify_event)
		if ($this->comment_approve)
		{
			$not_modid = $this->mid;
			include_once XOOPS_ROOT_PATH . '/include/notification_functions.php';
			$not_catinfo =& notificationCommentCategoryInfo($not_modid);
			$not_category = $not_catinfo['name'];
			$not_itemid = $this->com_itemid;
			$not_event = $notify_event;
			// Build an ABSOLUTE URL to view the comment.  Make sure we
			// point to a viewable page (i.e. not the system administration
			// module).
			$comment_tags = array();
			if ('system' == $xoopsModule->getVar('dirname'))
			{
				$module_handler =& xoops_gethandler('module');
				$not_module =& $module_handler->get($not_modid);
			}
			else
			{
				$not_module =& $xoopsModule;
			}
			if (!isset($comment_url))
			{
				$com_config =& $not_module->getInfo('comments');
				$comment_url = $com_config['pageName'] . '?';
				if (isset($com_config['extraParams']) && is_array($com_config['extraParams']))
				{
					$extra_params = '';
					foreach ($com_config['extraParams'] as $extra_param)
					{
						$extra_params .= isset($_POST[$extra_param]) ? $extra_param.'='.$_POST[$extra_param].'&amp;' : $extra_param.'=&amp;';
						//$extra_params .= isset($_GET[$extra_param]) ? $extra_param.'='.$_GET[$extra_param].'&amp;' : $extra_param.'=&amp;';
					}
					$comment_url .= $extra_params;
				}
				$comment_url .= $com_config['itemName'];
			}
			$comment_tags['X_COMMENT_URL'] = XOOPS_URL . '/modules/' . $not_module->getVar('dirname') . '/' .$comment_url . '=' . $this->com_itemid.'&amp;com_id='.$newcid.'&amp;com_rootid='.$com_rootid.'&amp;com_mode='.$com_mode.'&amp;com_order='.$com_order.'#comment'.$newcid;
			$notification_handler =& xoops_gethandler('notification');
			$notification_handler->triggerEvent ($not_category, $not_itemid, $not_event, $comment_tags, false, $not_modid);
		}
	*/
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// コメント削除
	function deleteComment($com_id)
	{
		$delete_op = isset($_POST['delete_op']) ? xoops_trim($_POST['delete_op']) : 'delete_one';

		switch ($delete_op)
		{
			case 'delete_one':
				$comment_handler = xoops_gethandler('comment');
				$comment =& $comment_handler->get($com_id);

				if (!$comment_handler->delete($comment))
				{
					return xoops_error(_CM_COMDELETENG.' (ID: '.$comment->getVar('com_id').')');
					exit();
				}
				$com_itemid = $comment->getVar('com_itemid');

				// update user posts if its not an anonymous post
				if ($comment->getVar('com_uid') != 0)
				{
					$member_handler =& xoops_gethandler('member');
					$com_poster =& $member_handler->getUser($comment->getVar('com_uid'));
					if (is_object($com_poster))
					{
						$member_handler->updateUserByField($com_poster, 'posts', $com_poster->getVar('posts') - 1);
					}
				}

				// get all comments posted later within the same thread
				$thread_comments =& $comment_handler->getThread($comment->getVar('com_rootid'), $com_id);

				include_once XOOPS_ROOT_PATH.'/class/tree.php';
				$xot = new XoopsObjectTree($thread_comments, 'com_id', 'com_pid', 'com_rootid');

				$child_comments =& $xot->getFirstChild($com_id);

				// now set new parent ID for direct child comments
				$new_pid = $comment->getVar('com_pid');
//				$errs = array();
				$msgs = '';
				foreach (array_keys($child_comments) as $i)
				{
					$child_comments[$i]->setVar('com_pid', $new_pid);
					// if the deleted comment is a root comment, need to change root id to own id
					if (false != $comment->isRoot())
					{
						$new_rootid = $child_comments[$i]->getVar('com_id');
						$child_comments[$i]->setVar('com_rootid', $child_comments[$i]->getVar('com_id'));
						if (!$comment_handler->insert($child_comments[$i]))
						{
//							$errs[] = 'Could not change comment parent ID from <b>'.$com_id.'</b> to <b>'.$new_pid.'</b>. (ID: '.$new_rootid.')';
							$msgs .= 'Could not change comment parent ID from <b>'.$com_id.'</b> to <b>'.$new_pid.'</b>. (ID: '.$new_rootid.')<br />';
						} else {
							// need to change root id for all its child comments as well
							$c_child_comments =& $xot->getAllChild($new_rootid);
							$cc_count = count($c_child_comments);
							foreach (array_keys($c_child_comments) as $j)
							{
								$c_child_comments[$j]->setVar('com_rootid', $new_rootid);
								if (!$comment_handler->insert($c_child_comments[$j]))
								{
//									$errs[] = 'Could not change comment root ID from <b>'.$com_id.'</b> to <b>'.$new_rootid.'</b>.';
									$msgs .= 'Could not change comment root ID from <b>'.$com_id.'</b> to <b>'.$new_rootid.'</b>.<br />';
								}
							}
						}
					}
					else
					{
						if (!$comment_handler->insert($child_comments[$i]))
						{
//							$errs[] = 'Could not change comment parent ID from <b>'.$com_id.'</b> to <b>'.$new_pid.'</b>.';
							$msgs .= 'Could not change comment parent ID from <b>'.$com_id.'</b> to <b>'.$new_pid.'</b>.<br />';
						}
					}
				}
//				if (count($errs) > 0)
				if ($msgs != '')
				{
//					return xoops_error($errs);
					return $msgs;
					exit();
				}
	//			redirect_header($redirect_page.'='.$com_itemid.'&amp;com_order='.$com_order.'&amp;com_mode='.$com_mode, 1, _CM_COMDELETED);
				return _CM_COMDELETED;
				break;

			case 'delete_all':
				$comment_handler = xoops_gethandler('comment');
				$comment =& $comment_handler->get($com_id);
				$com_rootid = $comment->getVar('com_rootid');

				// get all comments posted later within the same thread
				$thread_comments =& $comment_handler->getThread($com_rootid, $com_id);

				// construct a comment tree
				include_once XOOPS_ROOT_PATH.'/class/tree.php';
				$xot = new XoopsObjectTree($thread_comments, 'com_id', 'com_pid', 'com_rootid');
				$child_comments =& $xot->getAllChild($com_id);
				// add itself here
				$child_comments[$com_id] =& $comment;
//				$msgs = array();
				$msgs = '';
				$deleted_num = array();
				$member_handler =& xoops_gethandler('member');
				foreach (array_keys($child_comments) as $i)
				{
					if (!$comment_handler->delete($child_comments[$i]))
					{
//						$msgs[] = _CM_COMDELETENG.' (ID: '.$child_comments[$i]->getVar('com_id').')';
						$msgs .= _CM_COMDELETENG.' (ID: '.$child_comments[$i]->getVar('com_id').')<br />';
					}
					else
					{
//						$msgs[] = _CM_COMDELETED.' (ID: '.$child_comments[$i]->getVar('com_id').')';
						$msgs .= _CM_COMDELETED.' (ID: '.$child_comments[$i]->getVar('com_id').')<br />';
						// store poster ID and deleted post number into array for later use
						$poster_id = $child_comments[$i]->getVar('com_uid');
						if ($poster_id > 0)
						{
							$deleted_num[$poster_id] = !isset($deleted_num[$poster_id]) ? 1 : ($deleted_num[$poster_id] + 1);
						}
					}
				}
				foreach ($deleted_num as $user_id => $post_num)
				{
					// update user posts
					$com_poster = $member_handler->getUser($user_id);
					if (is_object($com_poster))
					{
						$member_handler->updateUserByField($com_poster, 'posts', $com_poster->getVar('posts') - $post_num);
					}
				}

				$com_itemid = $comment->getVar('com_itemid');

				// execute updateStat callback function if set
				if (isset($comment_config['callback']['update']) && trim($comment_config['callback']['update']) != '')
				{
					$skip = false;
					if (!function_exists($comment_config['callback']['update']))
					{
						if (isset($comment_config['callbackFile']))
						{
							$callbackfile = trim($comment_config['callbackFile']);
							if ($callbackfile != '' && file_exists(XOOPS_ROOT_PATH.'/modules/'.$moddir.'/'.$callbackfile))
							{
								include_once XOOPS_ROOT_PATH.'/modules/'.$moddir.'/'.$callbackfile;
							}
							if (!function_exists($comment_config['callback']['update']))
							{
								$skip = true;
							}
						}
						else
						{
							$skip = true;
						}
					}
					if (!$skip)
					{
						$criteria = new CriteriaCompo(new Criteria('com_modid', $this->mid));
						$criteria->add(new Criteria('com_itemid', $com_itemid));
						$criteria->add(new Criteria('com_status', XOOPS_COMMENT_ACTIVE));
						$comment_count = $comment_handler->getCount($criteria);
						$comment_config['callback']['update']($com_itemid, $comment_count);
					}
				}
//				return xoops_result($msgs);
				return $msgs;
				break;
		}
	}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderDeleteCommentForm()
	{
		$baseUrl = preg_replace('/&amp;/i','&',$this->baseUrl);
		$comment_form = '';
		$comment_form .= _CM_DELETESELECT;
		$comment_form .= '<form action="'.$baseUrl.'" method="post">';
		$comment_form .= '<div class="form">';
		$comment_form .= '<input type="hidden" name="sess" value="'.$this->session_id.'" />';
		$comment_form .= '<input type="hidden" name="com_id" value="'.$this->com_id.'" />';
		$comment_form .= '<input type="hidden" name="com_itemid" value="'.$this->com_itemid.'" />';
		$comment_form .= '<input type="hidden" name="op" value="detail" />';

		$comment_form .= $this->ticket->getTicketHtml();
		$comment_form .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
		$comment_form .= '<input type="hidden" name="HTTP_REFERER" value="'.$this->baseUrl.'" />';

		$comment_form .= '<input type="hidden" name="'.$this->com_itemid_field.'" value="'.$this->com_itemid.'" />';
		$comment_form .= '<input type="hidden" name="com_op" value="save" />';
		$comment_form .= '<input type="hidden" name="form_op" value="delete" />';
		$comment_form .= '<input type="radio" name="delete_op" value="delete_one" />'._CM_DELETEONE.'<br />';
		$comment_form .= '<input type="radio" name="delete_op" value="delete_all" />'._CM_DELETEALL.'<br />';
		$comment_form .= '<input type="submit" name="delete" value="'._DELETE.'" /> ';
		$comment_form .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
		$comment_form .= '</div>';
		$comment_form .= '</form>';

		return $comment_form;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderCommentForm($com_op)
	{
		global $xoopsModuleConfig;

		$comment_form = '';
		if ($com_op == 'post_new')
		{
			$form_title = _MD_XMOBILE_COMMENT_NEW;
			$form_op = 'post_new';
		}
		elseif ($com_op == 'edit')
		{
			$form_title = _MD_XMOBILE_COMMENT_EDIT;
			$form_op = 'edit';
		}
		elseif ($com_op == 'reply')
		{
			$form_title = _MD_XMOBILE_COMMENT_REPLY;
			$form_op = 'reply';
		}
	/*
		elseif ($com_op == 'delete')
		{
			$form_title = _MD_XMOBILE_COMMENT_DELETE;
			$form_op = 'delete';
		}
	*/

		$baseUrl = preg_replace('/&amp;/i','&',$this->baseUrl);
		$comment_form .= $form_title;
//		$comment_form .= '<a name="comment"></a>';
		$comment_form .= '<a id="comment"></a>';
		$comment_form .= '<form action="'.$baseUrl.'" method="post">';
		$comment_form .= '<div class="form">';
		$comment_form .= '<input type="hidden" name="view" value="detail" />';
		$comment_form .= '<input type="hidden" name="sess" value="'.$this->session_id.'" />';
		$comment_form .= $this->ticket->getTicketHtml();
		$comment_form .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
		$comment_form .= '<input type="hidden" name="HTTP_REFERER" value="'.$this->baseUrl.'" />';
		$comment_form .= '<input type="hidden" name="start" value="'.$this->start.'" />';
		$comment_form .= '<input type="hidden" name="com_pid" value="'.$this->com_pid.'" />';
		$comment_form .= '<input type="hidden" name="com_id" value="'.$this->com_id.'" />';
		$comment_form .= '<input type="hidden" name="com_rootid" value="'.$this->com_rootid.'" />';
		$comment_form .= '<input type="hidden" name="com_itemid" value="'.$this->com_itemid.'" />';
		$comment_form .= '<input type="hidden" name="'.$this->com_itemid_field.'" value="'.$this->com_itemid.'" />';
		$comment_form .= '<input type="hidden" name="com_op" value="save" />';
		$comment_form .= '<input type="hidden" name="form_op" value="'.$form_op.'" />';

		if ($com_op == 'reply')
		{
			if (!preg_match('/^Re:/i', $this->com_title))
			{
				$com_title = 'Re:'.xoops_substr($this->com_title,0,56);
			}
			else
			{
				$com_title = $this->com_title;
			}
			$comment_form .= '<input type="hidden" name="com_title" value="'.$com_title.'" />';
//			$comment_form .= _MD_XMOBILE_TITLE.$com_title.'<br />';
//			$comment_form .= _MD_XMOBILE_TITLE.'<br />';
//			$comment_form .= '<input type="text" name="com_title" value="'.$com_title.'" /><br />';
			$comment_form .= '<textarea name="com_text" cols="'.$xoopsModuleConfig['tarea_cols'].'" rows="'.$xoopsModuleConfig['tarea_rows'].'"></textarea><br />';
		}
		else
		{
//			$comment_form .= _MD_XMOBILE_TITLE.'<br />';
//			$comment_form .= '<input type="text" name="com_title" value="'.$com_title.'" /><br />';
			$comment_form .= '<textarea name="com_text" cols="'.$xoopsModuleConfig['tarea_cols'].'" rows="'.$xoopsModuleConfig['tarea_rows'].'">'.$this->com_text.'</textarea><br />';
		}
		if ($com_op == 'delete')
		{
			$comment_form .= '<input type="submit" name="delete" value="'._DELETE.'" />&nbsp;';
		}
		else
		{
			$comment_form .= '<input type="submit" name="submit" value="'._SUBMIT.'" />&nbsp;';
		}
		$comment_form .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
		$comment_form .= '</div>';
		$comment_form .= '</form>';

		return $comment_form;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>