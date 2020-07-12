<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
//require_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/xoopstableobject.php';
include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/TableObject.class.php';

if (!defined('XMOBILE_NOMOBILE')) define('XMOBILE_NOMOBILE', 0);
if (!defined('XMOBILE_DOCOMO')) define('XMOBILE_DOCOMO', 1);
if (!defined('XMOBILE_AU')) define('XMOBILE_AU', 2);
if (!defined('XMOBILE_VODA')) define('XMOBILE_VODA', 3);

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileSession extends XmobileTableObject
{
	function XmobileSession()
	{
		XmobileTableObject::XmobileTableObject();

		// define object elements
		$this->initVar('session_id', XOBJ_DTYPE_TXTBOX, '', true, 32);
		$this->initVar('uid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('subscriber_id', XOBJ_DTYPE_TXTBOX, '', false, 40);
		$this->initVar('ip_address', XOBJ_DTYPE_TXTBOX, '', false, 15);
		$this->initVar('php_session_id', XOBJ_DTYPE_TXTBOX, '', true, 32);
		$this->initVar('last_access', XOBJ_DTYPE_INT, time(), true);
		$this->initVar('user_agent', XOBJ_DTYPE_TXTBOX, '', false, 255);

		// define primary key
		$this->setKeyFields(array('session_id'));
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileSessionHandler extends XmobileTableObjectHandler
{
	var $controller;
	var $subscriberHandler;

	var $tableName = 'xmobile_session';
	var $mClass = 'XmobileSession';
	var $subscriberClass = 'XmobileSubscriber';
	var $user = null;

	var $session_id = '';
	var $uid = 0;
	var $ip_address = '';
	var $subscriber_id = '';
	var $php_session_id = '';

	var $search_ip = null;
	var $carrierForLogin = 0;
	var $carrierByAgent = 0;
	var $carrierByHost = 0;
	var $user_agent = '';
	var $subscriberObject = null;
	var $subscriberCheck = 0;
	var $timeoutFlag = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function XmobileSessionHandler($db)
	function XmobileSessionHandler($db,&$controller)
	{
		$this->controller = $controller;
		XmobileTableObjectHandler::XmobileTableObjectHandler($db);
		$this->tableName = $this->db->prefix($this->tableName);
		$this->setSubscriberHandler();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setSubscriberHandler()
	{
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/XmobileSubscriber.php';
		$this->subscriberHandler =& new XmobileSubscriberHandler($GLOBALS['xoopsDB']);
//		$this->subscriberHandler =& xoops_getmodulehandler('subscriber',basename(dirname(dirname(__FILE__))));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function checkSession(&$controller)
	function checkSession()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();

//		$this->controller = $controller;

		$this->distinctAccess();
		$this->distinctLogin();

//		if ($xoopsModuleConfig['use_easy_login'])
//		{
//			if ($this->checkSubscriberId())
//			{
//				return $this->loginBySubscriberId();
//			}
//		}

		$this->session_id = $this->getRequestSessionID();

		if ($this->session_id == '')
		{
			return false;
		}

		$check_result = 0;
		$limit_access = time() - $xoopsModuleConfig['session_limit'];

		$this->setIpAddress();

		$criteria =& new CriteriaCompo();
		$criteria = $criteria->add(new Criteria('session_id', $myts->addSlashes($this->session_id)));
		$criteria = $criteria->add(new Criteria('user_agent', $myts->addSlashes($this->user_agent)));
//		if ($xoopsModuleConfig['check_ip_address'])
//		{
//			$criteria = $criteria->add(new Criteria('ip_address', $myts->addSlashes($this->search_ip).'%', 'LIKE'));
//		}

		$sessionCount = $this->getCount($criteria);

		if ($sessionCount == 1)
		{
			$this->mClass =& $this->get($this->session_id);
			$this->uid = $this->mClass->getVar('uid');

			if ((!$this->uid == 0) && ($this->mClass->getVar('last_access') < $limit_access))
			{
				$this->setTimeOutFlag();
			}
			else
			{
//				$this->setUser();
				$check_result = $this->updateSession();
			}
		}

		$this->setUser();

		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'checkSession criteria', $criteria->render());
		$this->controller->utils->setDebugMessage(__CLASS__, 'checkSession sessionCount', $sessionCount);
//		$this->controller->utils->setDebugMessage(__CLASS__, 'checkSession timeoutFlag', $this->timeoutFlag);
//		$this->controller->utils->setDebugMessage(__CLASS__, 'checkSession check_result', $check_result);
//		$this->controller->utils->setDebugMessage(__CLASS__, 'checkSession uid', $this->uid);

		if ($xoopsModuleConfig['session_limit'] > 0)
		{
			$this->deleteTimeOutSession();
		}

		return $check_result;

	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// 携帯電話以外のアクセスを振り分ける
	function distinctAccess()
	{
		global $xoopsModuleConfig;

		$this->setCarrierByHost();
		$this->setCarrierByUserAgent();
		$not_mobile = false;

		switch ($xoopsModuleConfig['access_terminal'])
		{
			case 0:// 携帯端末のみ(ホスト名から判別)
				if ($this->carrierByHost == XMOBILE_NOMOBILE) $not_mobile = true;
				break;

			case 1:// 携帯端末のみ(エージェントから判別)
				if ($this->carrierByAgent == XMOBILE_NOMOBILE) $not_mobile = true;
				break;

			case 2:// 全て許可
				break;
		}

		if ($not_mobile)
		{
			$this->controller->setRender();
			$this->controller->render->displayforpc();
//			header('Location: '.XOOPS_URL);
			exit();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// 携帯電話以外のログイン・新規ユーザ登録・個体識別番号の取得を振り分ける
	function distinctLogin()
	{
		global $xoopsModuleConfig;

		$lang_type = '';
		switch ($xoopsModuleConfig['login_terminal'])
		{
			case 0:// 携帯端末のみ(ホスト名から判別)
				$this->carrierForLogin = $this->carrierByHost;
				$lang_type = 'Carrier By Host';
				break;

			case 1:// 携帯端末のみ(エージェントから判別)
				$this->carrierForLogin = $this->carrierByAgent;
				$lang_type = 'Carrier By Agent';
				break;

			case 2:// 全て許可
//				$this->carrierForLogin = 4;
				$this->carrierForLogin = $this->carrierByAgent;
				$lang_type = 'Carrier By Agent';
				break;
		}
		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'distinctLogin login terminal type', $lang_type);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// REMOTE_ADDRから携帯電話キャリアを判別する
	function setCarrierByHost()
	{
		$host_name = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$lang_carrier = '';

		if (preg_match('/\.docomo\.ne\.jp$/',$host_name))
		{
			$this->carrierByHost = XMOBILE_DOCOMO;
			$lang_carrier = 'docomo';
		}
		elseif (preg_match('/\.ezweb\.ne\.jp$/',$host_name))
		{
			$this->carrierByHost = XMOBILE_AU;
			$lang_carrier = 'au';
		}
		elseif (preg_match('/\.jp-[cdknqrst]{1,1}\.ne\.jp$/',$host_name))
		{
			$this->carrierByHost = XMOBILE_VODA;
			$lang_carrier = 'softbank';
		}
		else
		{
			$this->carrierByHost = XMOBILE_NOMOBILE;
			$lang_carrier = 'other';
		}

		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'getCarrierByHost host_name', $host_name);
		$this->controller->utils->setDebugMessage(__CLASS__, 'getCarrierByHost carrier', '('.$this->carrierByHost.')'.$lang_carrier);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// HTTP_USER_AGENTから携帯電話キャリアを判別する（偽装可能な為、信用できない)
	function setCarrierByUserAgent()
	{
		$this->user_agent = $_SERVER['HTTP_USER_AGENT'];
		$lang_carrier = '';

		if (preg_match("/DoCoMo\//",$this->user_agent))
		{
			if (preg_match('/([\S|\s]+)[\/|;]ser[\S]+\)$/', $_SERVER['HTTP_USER_AGENT'], $matches))
			{
				$this->user_agent = $matches[1].')';
			}
			elseif (preg_match('/([\S|\s]+)[\/|;]ser[\S]+/', $_SERVER['HTTP_USER_AGENT'], $matches))
			{
				$this->user_agent = $matches[1];
			}

			$this->carrierByAgent = XMOBILE_DOCOMO;
			$lang_carrier = 'docomo';
		}
		elseif (preg_match("/L-mode\/\//",$this->user_agent))
		{
			$this->carrierByAgent = XMOBILE_DOCOMO;
			$lang_carrier = 'docomo';
		}
		elseif (preg_match("/KDDI-([0-9A-Z]{4})/",$this->user_agent))
		{
			$this->carrierByAgent = XMOBILE_AU;
			$lang_carrier = 'au';
		}
		elseif (preg_match("/^UP\.Browser\//",$this->user_agent))
		{
			$this->carrierByAgent = XMOBILE_AU;
			$lang_carrier = 'au';
		}
		elseif (preg_match("/Vodafone/",$this->user_agent))
		{
			$this->carrierByAgent = XMOBILE_VODA;
			$lang_carrier = 'softbank';
		}
		elseif (preg_match("/J-PHONE\//",$this->user_agent))
		{
			$this->carrierByAgent = XMOBILE_VODA;
			$lang_carrier = 'softbank';
		}
		elseif (preg_match("/SoftBank\//",$this->user_agent))
		{
			$this->carrierByAgent = XMOBILE_VODA;
			$lang_carrier = 'softbank';
		}
		else
		{
			$this->carrierByAgent = XMOBILE_NOMOBILE;
			$lang_carrier = 'other';
		}

		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'setCarrierFromUserAgent user_agent', $this->user_agent);
		$this->controller->utils->setDebugMessage(__CLASS__, 'setCarrierFromUserAgent carrier', '('.$this->carrierByAgent.')'.$lang_carrier);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getCarrierByHost()
	{
		return $this->carrierByHost;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getCarrierByAgent()
	{
		return $this->carrierByAgent;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getCarrierForLogin()
	{
		return $this->carrierForLogin;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setSubscriberId()
	{
		$myts =& MyTextSanitizer::getInstance();

		switch ($this->carrierForLogin)
		{
			case XMOBILE_DOCOMO:

				if (preg_match('/ser([a-zA-Z0-9]+)/', $_SERVER['HTTP_USER_AGENT'], $matches))
				{
					if (strlen($matches[1]) === 11)
					{
						$this->subscriber_id = $myts->addSlashes($matches[1]);
					}
					elseif (strlen($matches[1]) === 15)
					{
						$this->subscriber_id = $myts->addSlashes($matches[1]);
					}
				}

				break;

			case XMOBILE_AU:
				if (isset($_SERVER['HTTP_X_UP_SUBNO']))
				{
					$this->subscriber_id = $myts->addSlashes($_SERVER['HTTP_X_UP_SUBNO']);
				}

				break;

			case XMOBILE_VODA:

				if (preg_match('/\/SN([a-zA-Z0-9]+)\s/', $_SERVER['HTTP_USER_AGENT'], $matches))
				{
					$this->subscriber_id = $myts->addSlashes($matches[1]);
				}

				break;
		}

		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'subscriber_id', $this->subscriber_id);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getSubscriberId()
	{
		$this->setSubscriberId();
		return $this->subscriber_id;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkSubscriberId()
	{
		global $xoopsModuleConfig;

		$this->setSubscriberId();

		if ($this->subscriber_id == '')
		{
			return false;
		}

		$this->subscriberObject =& $this->subscriberHandler->get($this->subscriber_id);
		if (is_object($this->subscriberObject))
		{
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'checkSubscriberId', 'true');
			$this->subscriberCheck = true;
		}
		else
		{
			//debug
			if ($this->subscriberHandler->getErrors() != '')
			{
				$this->controller->utils->setDebugMessage(__CLASS__, 'checkSubscriberId Error', $this->subscriberHandler->getErrors());
			}
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'checkSubscriberId', 'false');
			$this->subscriberCheck = false;
		}
		return $this->subscriberCheck;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function createSubscriber()
	{
		$myts =& MyTextSanitizer::getInstance();

		$this->setSubscriberId();

		$subscriberObject =& $this->subscriberHandler->create();
		$subscriberObject->setVar('subscriber_id', $myts->addSlashes($this->subscriber_id));
		$subscriberObject->setVar('uid', intval($this->uid));
		$subscriberObject->setVar('created', time());
		if ($ret = $this->subscriberHandler->insert($subscriberObject))
		{
			return $subscriberObject;
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'createSubscriber Success', $this->subscriberHandler->getErrors());
		}
		else
		{
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'createSubscriber Error', $this->subscriberHandler->getErrors());
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function deleteSubscriber()
	{
		$this->mClass =& $this->get($this->session_id);
		if (is_object($this->mClass))
		{
			$this->subscriberObject =& $this->subscriberHandler->get($this->mClass->getVar('subscriber_id'));
			if (!$ret = $this->subscriberHandler->delete($this->subscriberObject,true))
			{
				//debug
				$this->controller->utils->setDebugMessage(__CLASS__, 'deleteSubscriber Error', $this->getErrors());
			}
			unset($this->subscriber_id);
			unset($this->subscriberObject);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTimeOutFlag()
	{
		$this->uid = 0;
		$this->timeoutFlag = true;
		$this->deleteSession();
		unset($this->session_id);
//		$this->createSessionId();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function createSessionId()
	{
		unset($this->session_id);
		if (is_object($this->user))
		{
			$temp_str = $this->user->getVar('uname').time().$this->user->getVar('pass').rand(1000,9999);
			$this->session_id = md5($temp_str);
		}
		else
		{
			$temp_str = rand(1000,9999).time().rand(1000,9999).time();
			$this->session_id = md5($temp_str);
		}

		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'xmobile session_id', $this->session_id);
		return $this->session_id;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getRequestSessionID()
	{
		$session_id = trim($this->controller->utils->getGetPost('sess', ''));

		if ($session_id == '') return $session_id;

//		if (!preg_match("/^\w+$/", $session_id))
		if (!preg_match("/^\w{32}$/", $session_id))
		{
			trigger_error('Invalid SessionID');
			exit();
		}

		if (strlen($session_id) != 32)
		{
			trigger_error('Invalid SessionID');
			exit();
		}

		return $session_id;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// not use
/*
	function getRequestPhpSessionId()
	{
		global $xoopsConfig;
		$myts =& MyTextSanitizer::getInstance();

//		$php_session_id = $this->controller->utils->getGetPost($xoopsConfig['session_name'], false);
		$php_session_id = $this->controller->utils->getGetPost(session_name(), false);
		if (strlen($php_session_id) != 32)
		{
			trigger_error('Invalid PHP SessionID');
			die();
		}
		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'strlen php_session_id', strlen($php_session_id));
		$this->controller->utils->setDebugMessage(__CLASS__, 'GetPost php_session_id', $php_session_id);
		$this->controller->utils->setDebugMessage(__CLASS__, 'php_session_id', session_id());
		$this->controller->utils->setDebugMessage(__CLASS__, 'php_session_name', session_name());
		$this->controller->utils->setDebugMessage(__CLASS__, 'xoops session_name', $xoopsConfig['session_name']);

		return $php_session_id;
	}
*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setIpAddress()
	{
		$myts =& MyTextSanitizer::getInstance();

		if (isset($_SERVER['REMOTE_ADDR']))
		{
			$this->ip_address = $_SERVER['REMOTE_ADDR'];

			$ret = preg_match('/(\d{1,3}\.){3}/', $this->ip_address,$matches);
			$this->search_ip = $matches[0];
		}
		if (strlen($this->ip_address) > 15)
		{
			trigger_error('Invalid IpAddress');
			die();
		}
		//debug
//		$this->controller->utils->setDebugMessage(__CLASS__, 'strlen ip_address', strlen($this->ip_address));
//		$this->controller->utils->setDebugMessage(__CLASS__, 'search_ip', $this->search_ip);
		$this->controller->utils->setDebugMessage(__CLASS__, 'ip_address', $this->ip_address);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getIpAddress()
	{
		return $this->ip_address;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function createSession()
	{
		$myts =& MyTextSanitizer::getInstance();

		$this->mClass =& $this->create();
		$this->mClass->setVar('session_id', $myts->addSlashes($this->session_id));
		$this->mClass->setVar('uid', intval($this->uid));
		$this->mClass->setVar('subscriber_id', $myts->addSlashes($this->subscriber_id));
		$this->mClass->setVar('ip_address', $myts->addSlashes($this->ip_address));
		$this->mClass->setVar('php_session_id', $myts->addSlashes(session_id()));
		$this->mClass->setVar('last_access', time());
//		$this->mClass->setVar('user_agent', $this->user_agent);
		$this->mClass->setVar('user_agent', $myts->addSlashes($this->user_agent));
		if ($ret = $this->insert($this->mClass))
		{
			return $this->mClass;
		}
		else
		{
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'createSession Error', $this->getErrors());
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function updateSession()
	{
		if (is_null($this->session_id))
		{
			return false;
		}

//		$this->mClass =& $this->get($this->session_id);
		$myts =& MyTextSanitizer::getInstance();
		$this->mClass =& $this->get($myts->addSlashes($this->session_id));
		if (!is_object($this->mClass))
		{
			return false;
		}
		$this->mClass->setVar('last_access',time());
		if ($ret = $this->insert($this->mClass,true))
		{
			return true;
		}
		else
		{
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'updateSession Error', $this->getErrors());
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function deleteSession()
	{
		$this->mClass =& $this->get($this->session_id);
		if (!$ret = $this->delete($this->mClass,true))
		{
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'deleteSession Error', $this->getErrors());
		}
		unset($this->session_id);
		$this->uid = 0;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function deleteTimeOutSession()
	{
		global $xoopsModuleConfig;
		$limit_access = time() - $xoopsModuleConfig['session_limit'];
		$criteria = new Criteria('last_access', $limit_access, '<');
		if (!$ret = $this->deleteAll($criteria,true))
		{
			//debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'deleteTimeOutSession Error', $this->getErrors());
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function deleteTimeOutSubscriber()
	{
		global $xoopsModuleConfig;
		if ($xoopsModuleConfig['easy_login_limit'] != 0)
		{
			$limit_access = time() - $xoopsModuleConfig['easy_login_limit'];
			$criteria = new Criteria('created', $limit_access, '<');
			if (!$ret = $this->subscriberHandler->deleteAll($criteria,true))
			{
				//debug
				$this->controller->utils->setDebugMessage(__CLASS__, 'deleteTimeOutSubscriber Error', $this->getErrors());
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function login()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();

		if (isset($_POST['uname']))
		{
			$uname = trim($_POST['uname']);
		}

		if (isset($_POST['pass']))
		{
			$pass = trim($_POST['pass']);
		}

		if (isset($_POST['easy_login']))
		{
			$easy_login = intval($_POST['easy_login']);
		}
		else
		{
			$easy_login = 0;
		}

		$this->setIpAddress();

		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'login uname', $uname);
		$this->controller->utils->setDebugMessage(__CLASS__, 'login pass', $pass);

		$this->uid = 0;

		if (isset($uname) && isset($pass))
		{
			$member_handler =& xoops_gethandler('member');
//			$this->user =& $member_handler->loginUser($myts->addSlashes($uname), $myts->addSlashes($pass));
			$this->user =& $member_handler->loginUser(addslashes($myts->stripSlashesGPC($uname)), $myts->stripSlashesGPC($pass));


			if (is_object($this->user))
			{
				$this->uid = $this->user->getVar('uid');

				$this->session_id = $this->createSessionId();


				if ($xoopsModuleConfig['use_easy_login'] && $easy_login == 1)
				{
					// debug
					$this->controller->utils->setDebugMessage(__CLASS__, 'easy_login', $easy_login);
					$this->createSubscriber();
				}

				$this->changeLoginTime();

				$this->mClass =& $this->create();
				$this->mClass->setVar('session_id', $myts->addSlashes($this->session_id));
				$this->mClass->setVar('uid', intval($this->uid));
				$this->mClass->setVar('subscriber_id', $myts->addSlashes($this->subscriber_id));
				$this->mClass->setVar('ip_address', $myts->addSlashes($this->ip_address));
				$this->mClass->setVar('php_session_id', $myts->addSlashes(session_id()));
				$this->mClass->setVar('last_access', time());
//				$this->mClass->setVar('user_agent', substr($this->user_agent, 0, 255));
				$this->mClass->setVar('user_agent', $myts->addSlashes($this->user_agent));

				if (!$ret = $this->insert($this->mClass))
				{
					// debug
					$this->controller->utils->setDebugMessage(__CLASS__, 'login Error', $this->getErrors());
				}
				else
				{
					// debug
					$this->controller->utils->setDebugMessage(__CLASS__, 'login', 'Success');
				}

				$this->controller->utils->setDebugMessage(__CLASS__, 'login uid', $this->uid);

				return $this->session_id;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function loginBySubscriberId()
	{
		global $xoopsModuleConfig;
		$myts =& MyTextSanitizer::getInstance();

		if (!$this->checkSubscriberId())
		{
			return false;
		}

		$this->deleteTimeOutSubscriber();

		$this->setIpAddress();

		$this->uid = $this->subscriberObject->getVar('uid');
		$member_handler =& xoops_gethandler('member');
		$this->user =& $member_handler->getUser($this->uid);

		if (is_object($this->user))
		{
			$this->session_id = $this->createSessionId();
			$this->changeLoginTime();
			$this->mClass =& $this->create();
			$this->mClass->setVar('session_id', $myts->addSlashes($this->session_id));
			$this->mClass->setVar('uid', intval($this->uid));
			$this->mClass->setVar('subscriber_id', $myts->addSlashes($this->subscriber_id));
			$this->mClass->setVar('ip_address', $myts->addSlashes($this->ip_address));
			$this->mClass->setVar('php_session_id', $myts->addSlashes(session_id()));
			$this->mClass->setVar('last_access', time());
//			$this->mClass->setVar('user_agent', substr($this->user_agent, 0, 255));
			$this->mClass->setVar('user_agent', $myts->addSlashes($this->user_agent));
			if (!$ret = $this->insert($this->mClass,true))
			{
				// debug
				$this->controller->utils->setDebugMessage(__CLASS__, 'login Error', $this->getErrors());
			}

			$this->controller->utils->setDebugMessage(__CLASS__, 'login uid', $this->uid);
			$this->controller->utils->setDebugMessage(__CLASS__, 'loginBySubscriberId', 'Sussess');

			return $this->session_id;
		}
		else
		{
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function logout()
	{
		$this->deleteSubscriber();
		$this->deleteSession();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getSession()
	{
		return $this->mClass;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getSessionID()
	{
		if (isset($this->session_id))
		{
			return $this->session_id;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getUid()
	{
		return intval($this->uid);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setUser()
	{
		$member_handler =& xoops_gethandler('member');
		$this->user =& $member_handler->getUser($this->uid);
		if (!is_object($this->user))
		{
			$actionState = trim($this->controller->utils->getGetPost('act', 'default'));
			if ($actionState != 'showpage')
			{
				$this->session_id = '';
			}
		}
		//debug
		$this->controller->utils->setDebugMessage(__CLASS__, 'setUser uid', $this->uid);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function &getUser()
	{
		return $this->user;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function changeLoginTime()
	{
		$this->user->setVar('last_login', time());
		$member_handler =& xoops_gethandler('member');

		if ($member_handler->insertUser($this->user))
		{
			$this->controller->utils->setDebugMessage(__CLASS__, 'changeLoginTime', 'Success');
			return true;
		}
		else
		{
			// debug
			$this->controller->utils->setDebugMessage(__CLASS__, 'changeLoginTime Error', $this->getErrors());
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getTimeoutFlag()
	{
		return $this->timeoutFlag;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}//end of class
?>
