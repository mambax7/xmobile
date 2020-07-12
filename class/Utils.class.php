<?php
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileUtils
{
	var $debugMessageArray = array();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileUtils()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function &getInstance()
	{
		static $instance;
		if (!isset($instance)) 
		{
			$instance = new XmobileUtils();
		}
		return $instance;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getLinkUrl($action='default',$view=null,$plugin=null,$session_id='',$ext=null)
	{
		$linkUrl = XMOBILE_URL;
		$linkUrl .= '/?act='.$action;
		if (!is_null($view))
		{
			$linkUrl .= '&amp;view='.$view;
//			$linkUrl .= '&view='.$view;
		}
		if (!is_null($plugin))
		{
			$linkUrl .= '&amp;plg='.$plugin;
//			$linkUrl .= '&plg='.$plugin;
		}
		if ($session_id != '')
		{
			$linkUrl .= '&amp;sess='.$session_id;
//			$linkUrl .= '&sess='.$session_id;
		}
		if (!is_null($ext))
		{
			$linkUrl .= '&amp;'.htmlspecialchars(trim($ext), ENT_QUOTES);
//			$linkUrl .= '&'.htmlspecialchars(trim($ext), ENT_QUOTES);
		}
		// debug
		$this->setDebugMessage(__CLASS__, 'getLinkUrl', $linkUrl);

		return $linkUrl;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getGet($key, $default=false)
	{
		if (isset($_GET[$key]))
		{
			// debug
			$this->setDebugMessage(__CLASS__, 'GET', $key.'=>'.$_GET[$key]);
			return $_GET[$key];
		}
		else
		{	
			return $default;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getPost($key, $default=false)
	{
		if (isset($_POST[$key]))
		{
			// debug
			$this->setDebugMessage(__CLASS__, 'POST', $key.'=>'.$_POST[$key]);
			return $_POST[$key];
		}
		else
		{
			return $default;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getGetPost($key, $default=false)
	{
		if (isset($_GET[$key]))
		{
			// debug
			$this->setDebugMessage(__CLASS__, 'GET', $key.'=>'.$_GET[$key]);
			return $_GET[$key];
		}
		elseif (isset($_POST[$key]))
		{
			// debug
			$this->setDebugMessage(__CLASS__, 'POST', $key.'=>'.$_POST[$key]);
			return $_POST[$key];
		}
		else
		{
			return $default;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// プライベートメッセージ
	function getPrivateMessage($uid)
	{
		$privmessage_handler =& xoops_gethandler('Privmessage');
		$criteria = new CriteriaCompo(new Criteria('to_userid', intval($uid)));
		$criteria->add(new Criteria('read_msg',0));
		$message_count = $privmessage_handler->getCount($criteria);
		return $message_count;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ユーザグループID
	function getGroupIdArray($user)
	{
		if (is_object($user))
		{
			$groupid_array = $user->getGroups();
		}
		else
		{
			$groupid_array[] = XOOPS_GROUP_ANONYMOUS;
		}

		// debug
		if (count($groupid_array) > 0)
		{
//			$this->setDebugMessage(__CLASS__, 'getGroupIdArray', join(':',$groupid_array));
		}

		return $groupid_array;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// モジュールのグループアクセス権限チェック
	function getModulePerm($user, $mid, $gperm_name='module_read')
	{
		$modulePerm = false;
		$groups = $this->getGroupIdArray($user);
		$groupperm_handler =& xoops_gethandler('groupperm');
		if ($groupperm_handler->checkRight($gperm_name, $mid, $groups))
		{
			$modulePerm = true;
		}

		// debug
//		$this->setDebugMessage(__CLASS__, 'getModulePerm', $modulePerm);

		return $modulePerm;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// xmobileから利用可能なモジュールのmidの配列を取得
	function getMidsCanUse($user)
	{
		global $xoopsModuleConfig;

		$module_handler =& xoops_gethandler('module');
		$criteria = new CriteriaCompo(new Criteria('isactive', 1));
		$criteria->add(new Criteria('weight',0,'<>'));
		$criteria->setSort('weight');
		$modules =& $module_handler->getObjects($criteria);

		$mid_array = array();
//		foreach($modules as $mid => $module)
		foreach($modules as $module)
		{
			$mid = $module->getVar('mid');
			$dirname = $module->getVar('dirname');
			if (in_array($dirname, $xoopsModuleConfig['modules_can_use']))
			{
				// モジュールのグループアクセス権限チェック
				if ($this->getModulePerm($user, $mid))
				{
					array_push($mid_array,$mid);
				}
			}
		}
		return $mid_array;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getUnameFromId($uid)
	{
		$uid = intval($uid);
		$member_handler =& xoops_gethandler('member');
		$user =& $member_handler->getUser($uid);
		if (is_object($user))
		{
			$myts =& MyTextSanitizer::getInstance();
			return $myts->htmlSpecialChars($user->getVar('uname'));
		}
		else
		{
			return $GLOBALS['xoopsConfig']['anonymous'];
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setDebugMessage($classname, $title, $value='')
	{
		$classname4html = htmlspecialchars($classname, ENT_QUOTES);
		$title4html = htmlspecialchars($title, ENT_QUOTES);
		$value4html = htmlspecialchars($value, ENT_QUOTES);
		$this->debugMessageArray[$classname4html][] = $title4html.':'.$value4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDebugMessage()
	{
		$debugMessage4html = '';
		foreach($this->debugMessageArray as $key=>$messages)
		{
			$i = 0;
			foreach($messages as $message)
			{
				++$i;
				if ($i == 1)
				{
					$debugMessage4html .= '['.$key.']<br />';
				}
				$debugMessage4html .= $message.'<br />';
			}
			$debugMessage4html .= '<br />';
		}
		return $debugMessage4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDateShort($mydate=0, $dst = NULL)
	{
		global $xoopsConfig;
		if ($mydate <= 0)
		{
			return '';
		}
		if ($dst)
			$mydate -= ((date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600);
		else
			$mydate -= (($xoopsConfig['server_TZ'] - $xoopsConfig['default_TZ']) * 3600);
		$get_date_short = date('Y-n-j',$mydate);	//2001-1-3
		return $get_date_short;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDateLong($mydate=0, $dst = NULL)
	{
		global $xoopsConfig;
		$week_array = array(
			_MD_XMOBILE_SUNDAY,
			_MD_XMOBILE_MONDAY,
			_MD_XMOBILE_TUESDAY,
			_MD_XMOBILE_WEDNESDAY,
			_MD_XMOBILE_THURSDAY, 
			_MD_XMOBILE_FRIDAY, 
			_MD_XMOBILE_SATURDAY
		);

		if ($mydate <= 0)
		{
			return '';
		}
		if ($dst)
			$mydate -= ((date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600);
		else
			$mydate -= (($xoopsConfig['server_TZ'] - $xoopsConfig['default_TZ']) * 3600);

		$week_day = $week_array[date('w', $mydate)];

		$get_date_long = date('Y-n-j', $mydate)."(".$week_day.")";	//2001-1-3(水)

		return $get_date_long;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getTimeShort($mydate=0, $dst = NULL)
	{
		global $xoopsConfig;
		if ($mydate <= 0)
		{
			return '';
		}
		if ($dst)
			$mydate -= ((date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600);
		else
			$mydate -= (($xoopsConfig['server_TZ'] - $xoopsConfig['default_TZ']) * 3600);
		$get_time_short = date('G:i', $mydate);	//3:05
		return $get_time_short;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getTimeLong($mydate=0, $dst = NULL)
	{
		global $xoopsConfig;
		if ($mydate <= 0)
		{
			return '';
		}
		if ($dst)
			$mydate -= ((date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600);
		else
			$mydate -= (($xoopsConfig['server_TZ'] - $xoopsConfig['default_TZ']) * 3600);
		$get_time_long = date('H:i', $mydate);	//03:05
		return $get_time_long;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getWeek($week_no, $opt)
	{
		$week_array = array(
			_MD_XMOBILE_SUNDAY, 
			_MD_XMOBILE_MONDAY, 
			_MD_XMOBILE_TUESDAY, 
			_MD_XMOBILE_WEDNESDAY, 
			_MD_XMOBILE_THURSDAY, 
			_MD_XMOBILE_FRIDAY, 
			_MD_XMOBILE_SATURDAY
		);
		$week_day = $week_array[$week_no];

		switch ($opt)
		{
			case 1:
				return $week_day._MD_XMOBILE_LANG_WEEK;
			case 2:
				return '('.$week_day.')';
			case 3:
				return $week_day;
			break;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function convertDate($mydate, $dst = NULL)
	{
		global $xoopsConfig;
		if ($dst)
			$mydate -= ((date('Z',1104537600)/3600 - $xoopsConfig['default_TZ']) * 3600);
		else
			$mydate -= (($xoopsConfig['server_TZ'] - $xoopsConfig['default_TZ']) * 3600);

		if (empty($mydate) || $mydate==0) return '';
		$myyear = substr($mydate,0,4);
		$mymonth = substr($mydate,5,2);
		$myday = substr($mydate,8,2);
		return mktime(0, 0, 0, $mymonth, $myday, $myyear);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}//end of class
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
