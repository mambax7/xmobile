<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
//include_once XOOPS_ROOT_PATH.'/modules/eguide/functions.php';
//include_once XOOPS_ROOT_PATH.'/modules/eguide/reserv_func.php';

if (!defined('_RVSTAT_ORDER')) define('_RVSTAT_ORDER',0);
if (!defined('_RVSTAT_RESERVED')) define('_RVSTAT_RESERVED',1);
if (!defined('_RVSTAT_REFUSED')) define('_RVSTAT_REFUSED',2);

if (!defined('STAT_NORMAL')) define('STAT_NORMAL',0);
if (!defined('STAT_POST')) define('STAT_POST',1);
if (!defined('STAT_DELETED')) define('STAT_DELETED',4);

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileEguidePlugin extends XmobilePlugin
{
	function XmobileEguidePlugin()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('eid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('uid', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('cdate', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('edate', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('ldate', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('mdate', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('expire', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('style', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('status', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('summary', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('body', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('counter', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('topicid', XOBJ_DTYPE_INT, '0', true);

		// define primary key
		$this->setKeyFields(array('eid'));
		$this->setAutoIncrementField('eid');
	}
//////////////////////////////////////////////////////////////////////////
	function assignSanitizerElement()
	{
		switch ($this->getVar('style'))
		{
			case 0:
				$this->initVar('doxcode', XOBJ_DTYPE_INT, 1);
				$this->initVar('dobr', XOBJ_DTYPE_INT, 0);
				$this->initVar('dohtml', XOBJ_DTYPE_INT, 1);
				break;
			case 1:
				$this->initVar('doxcode', XOBJ_DTYPE_INT, 1);
				$this->initVar('dobr', XOBJ_DTYPE_INT, 1);
				$this->initVar('dohtml', XOBJ_DTYPE_INT, 1);
				break;
			case 2:
				$this->initVar('doxcode', XOBJ_DTYPE_INT, 1);
				$this->initVar('dobr', XOBJ_DTYPE_INT, 1);
				$this->initVar('dohtml', XOBJ_DTYPE_INT, 0);
				break;
		}
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileEguidePluginHandler extends XmobilePluginHandler
{
	var $template = 'xmobile_eguide.html';
	var $moduleDir = 'eguide';
	var $categoryTableName = 'eguide_category';
	var $itemTableName = 'eguide';

	var $category_id_fld = 'catid';
	var $category_title_fld = 'catname';
	var $category_order_fld = 'catid';

	var $item_id_fld = 'eid';
	var $item_cid_fld = 'topicid';
	var $item_title_fld = 'title';
	var $item_description_fld = 'summary';
	var $item_order_fld = 'edate';
	var $item_date_fld = 'edate';
	var $item_uid_fld = 'uid';
	var $item_hits_fld = 'counter';
	var $item_order_sort = 'ASC';
	var $item_extra_fld = array('body'=>'');

	var $reserve = null;
	var $reserve_status = 0;
	var $reserved = 0;
	var $reserv_id = null;
	var $reservation_perm = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileEguidePluginHandler($db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
		$this->ticket = new XoopsGTicket;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// カテゴリ一覧の取得
// ただし、戻り値はオブジェクトではなく配列
	function getCatList()
	{
		$this->setNextViewState('list');
		$this->setBaseUrl();
		$this->setCategoryParameter();

		if (is_null($this->category_id))
		{
			$categoryArray = $this->categoryTree->getFirstChild(0);
		}
		else
		{
			$categoryArray = $this->categoryTree->getFirstChild($this->category_id);
		}

		// カテゴリのパンくずを表示
		$this->controller->render->template->assign('cat_path',$this->getCatPathFromId($this->category_id));

		if (!is_array($categoryArray))
		{
			return false;
		}

		$subcategory_count = count($categoryArray);
		if ($subcategory_count == 0) // 表示するデータ無し
		{
			return false;
		}

		if (!is_null($this->category_id))
		{
			$item_count = $this->getItemCountById();
		}
		else
		{
			$item_count = 0;
		}

		if ($item_count > 0)
		{
			$use_accesskey = false;
		}
		else
		{
			$use_accesskey = true;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getCatList subcategory_count', $subcategory_count);
		$this->utils->setDebugMessage(__CLASS__, 'getCatList item_count', $item_count);

		$cat_list = array();
		$i = 0;
		foreach($categoryArray as $category)
		{
			$id = $category[$this->category_id_fld];
			$title = $category[$this->category_title_fld];
			$url_parameter = $this->getBaseUrl();

			if (!is_null($this->category_pid_fld))
			{
				$pid = $category[$this->category_pid_fld];
				$url_parameter .= '&amp;'.$this->category_pid_fld.'='.$pid;
			}
			if (!is_null($this->category_id_fld))
			{
				$url_parameter .= '&amp;'.$this->category_id_fld.'='.$id;
			}
//			$htmlBody .= $this->getListTitleLink($number,$id,$title,$url_parameter,$use_accesskey).'<br />';
			$number = $i + 1; // アクセスキー用の番号、1から開始
			$cat_list[$i]['key'] = $number;
			$cat_list[$i]['title'] = $this->adjustTitle($title);
			$cat_list[$i]['url'] = $url_parameter;
			$cat_list[$i]['item_count'] = sprintf(_MD_XMOBILE_NUMBER, $this->getChildItemCountById($id));
			$i++;
		}
		return $cat_list;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
		$this->item_criteria->add(new Criteria('status', 0, '='));
		$this->item_criteria->add(new Criteria('edate+expire', time(), '>'));

//		$this->item_criteria->add(new Criteria('expire', time(), '>'));
//		$this->item_criteria->add(new Criteria('if (exdate,exdate,edate)+expire)', time(), '>'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事詳細・コメント・編集用リンクの取得
// ただし、戻り値はオブジェクトではなくHTML
	function getItemDetail()
	{
//		$xoopsUser =& $this->sessionHandler->getUser();

		$show_reserve = intval($this->utils->getGet('show_reserve', 0));

		$detail4html = '';

		if ($show_reserve == 0)// if not show reserve list
		{
			$detail4html .= parent::getItemDetail();
		}

		$this->checkReserveStatus($this->item_id);
		$detail4html .= $this->reserve['status_word'];

		switch ($this->reserve_status)
		{
			case 1:

				$detail4html .= $this->getReservationForm($this->item_id);
				break;

			case 2:

				$detail4html .= $this->getReserveList($this->item_id);
				break;

			case 3:

				$detail4html .= _MD_RESERV_CLOSE;
				break;
		}

//		unset($xoopsUser);
		return $detail4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getConfirmView()
	{
		$this->setItemId();
		$op = trim($this->utils->getGetPost('op', ''));
		$entry_type = htmlspecialchars($this->utils->getGetPost('entry_type', 'new'), ENT_QUOTES);

		if ($op == 'reserve')
		{
			$this->controller->render->template->assign('item_detail',$this->saveReservation($entry_type));
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkReserveStatus($eid)// 0:予約受付なし、1:予約受付中、2:予約申込み済、3:予約受付終了
	{
		$myts =& MyTextSanitizer::getInstance();
		$user =& $this->sessionHandler->getUser();

		if ($this->getCount($this->item_criteria) > 0)
		{
			$option_data = $this->getEventOption($eid);
			if ($this->getCount($option_data) > 0)
			{

				if ($this->moduleConfig['member_only'] && !is_object($user))
				{
					$this->reserve['status_word'] = '<hr />'._MD_RESERV_NEEDLOGIN;
				}
				elseif ($option_data['strict'] && ($option_data['persons']<=$option_data['reserved']))
				{
					$this->reserve['status_word'] = '<hr />'._MD_RESERV_FULL;
				}
				else
				{
					$this->reserve_status = 1;
				}
			}
			else
			{
				$this->reserve_status = 3;
			}
		}

		$sql = 'SELECT rvid,eid,exid,uid,rdate,email,info,status,confirm FROM '.$this->db->prefix('eguide_reserv').' WHERE eid='.$eid.' AND uid='.$this->sessionHandler->getUid();
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'checkReserveStatus sql', $sql);
		if (!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'checkReserveStatus db error', $this->db->error());
		}

		$ret_n = $this->db->getRowsNum($ret);
		if ($ret_n > 0 && is_object($user))
		{
			while($data = $this->db->fetchArray($ret))
			{
				$this->reserve = array();
				$this->reserve['rvid'] = intval($data['rvid']);
				$this->reserve['eid'] = intval($data['eid']);
				$this->reserve['exid'] = intval($data['exid']);
				$date = intval($data['rdate']);
				$this->reserve['rdate'] = $this->utils->getDateLong($date).' '.$this->utils->getTimeLong($date);
				$this->reserve['email'] = $myts->makeTboxData4Show($data['email']);
				$this->reserve['info'] = $myts->makeTareaData4Show($data['info']);
				$this->reserve['status'] = intval($data['status']);
				$this->reserve['confirm'] = $myts->makeTboxData4Show($data['confirm']);
				if ($data['status'] == 1)
				{
					$this->reserve['status_word'] = '<hr />'._MD_RESERVED.'<br />'._MD_XMOBILE_APPROVED;
				}
				else
				{
					$this->reserve['status_word'] = '<hr />'._MD_RESERVED.'<br />'._MD_XMOBILE_NOTAPPROVED;
				}
			}
			$this->reserve_status = 2;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'checkReserveStatus', $this->reserve_status);
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getReserveList($eid)
	{
		$myts =& MyTextSanitizer::getInstance();

		$option_data = $this->getEventOption($eid);

		if (count($option_data) <= 0)
		{
			return false;
		}


		$show_reserve = intval($this->utils->getGet('show_reserve', 0));
		if ($show_reserve == 0)
		{
			$extra = '';
			if (!is_null($this->category_id))
			{
//				$extra .= $this->item_cid_fld.'='.$this->item_cid;
				$extra .= $this->category_id_fld.'='.$this->category_id;
			}
			$extra .= '&eid='.$eid.'&show_reserve=1';
			$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
			$reserve_list = '<hr /><a href="'.$baseUrl.'">'._MD_RESERV_LIST.'</a>';
		}


		if ($option_data['reservation'] && !empty($option_data['reserved']))
		{
			$list = '<hr />('._MD_XMOBILE_RESERVATION_LIST.')<br />';
			$opt_array = explode("\n",$option_data['optfield']);

			$reserve_data = $this->getReserve($eid);
			$count = 0;
			if (count($reserve_data) > 0)
			{
				foreach($reserve_data as $reserve)
				{
					$info_array = explode("\n",$reserve['info']);
					$info = $this->explodeInfo($info_array, $opt_array);
					if ($info != '')
					{
						$list .= $myts->makeTboxData4Show($info).'<br />';
						$count++;
					}
				}
			}

			if ($count == 0)
			{
				return false;
			}
			else
			{
				if ($show_reserve == 0)
				{
					return $reserve_list;
				}
				else
				{
					return $list;
				}
			}
		}
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getReserve($eid,$rvid=null,$uid=null)
	{
		$reserve_data = array();
		$sql = 'SELECT rvid,eid,exid,uid,rdate,email,info,status,confirm FROM '.$this->db->prefix('eguide_reserv').' WHERE eid='.$eid.' AND status=1';
		if (!is_null($rvid))
		{
			$sql .= ' AND rvid='.$rvid;
		}
		if (!is_null($uid))
		{
			$sql .= ' AND uid='.$uid;
		}
		$sql .= ' ORDER BY rdate';

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getReserve sql', $sql);
		if (!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'getReserve db error', $this->db->error());
		}
		$ret_n = $this->db->getRowsNum($ret);
		if ($ret_n > 0)
		{
			while($row = $this->db->fetchArray($ret))
			{
				$reserve_data[] = $row;
			}
		}

		return $reserve_data;
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getEventOption($eid)
	{
		$opt_data = '';
		$sql = 'SELECT eid,reservation,strict,autoaccept,notify,persons,reserved,closetime,optfield FROM '.$this->db->prefix('eguide_opt').' WHERE eid='.$eid;
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getEventOption sql', $sql);
		if (!$ret = $this->db->query($sql))
		{
			$this->utils->setDebugMessage(__CLASS__, 'getEventOption db error', $this->db->error());
			return false;
		}
		$ret_n = $this->db->getRowsNum($ret);
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getEventOption Count', $ret_n);
		if ($ret_n <= 0)
		{
			return false;
		}

		$opt_data = $this->db->fetchArray($ret);

		return $opt_data;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function explodeInfo($info_array, $opt_array)
	{

		if (count($info_array) <= 0 || count($opt_array) <= 0)
		{
			return false;
		}

		$info_list = '';
		for($i=0; $i<count($opt_array); $i++)
		{
			if (preg_match("/^!([\S|\s]*)$/",$opt_array[$i],$match))
			{
				if (preg_match("/^".$match[1]."/",$info_array[$i]))
				{
					$info_list .= $info_array[$i].' , ';
				}
			}
		}
		$info_list = preg_replace("/^([\S|\s]*)\s,\s$/","$1",$info_list);
		return $info_list;
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////
	function getFormOption($option_str,$posted_option=null)
	{
		if ($option_str == '')
		{
			return false;
		}

		$form_option_array = array();
		$option_array = preg_split('/\n/',$option_str);
		$j = 0;

		for($i=0; $i<count($option_array); $i++)
		{
			if ($option_array[$i] != '')
			{
				$line_array[$j] = $option_array[$i];
				$j++;
			}
		}

		for($i=0; $i<count($line_array); $i++)
		{
			$data1_array = split(',',$line_array[$i]);
			$n = count($data1_array);
			$form_option_array[$i]['need'] = 0;
			$form_option_array[$i]['show'] = 0;
			$temp_html = '';

			if (preg_match('/\*$/',$data1_array[0]))
			{
				$form_option_array[$i]['need'] = 1;
			}

			if (preg_match('/^!/',$data1_array[0]))
			{
				$form_option_array[$i]['show'] = 1;
			}

			$posted_value = '';
			if (is_array($posted_option))
			{
				if (array_key_exists($i,$posted_option))
				{
					$posted_value = htmlspecialchars($posted_option[$i], ENT_QUOTES);
				}
			}

			if ($n > 1)
			{
				if (preg_match('/checkbox/i',$data1_array[1]))
				{
					$type = 'checkbox';
				}
				elseif (preg_match('/select/i',$data1_array[1]))
				{
					$type = 'select';
				}
				elseif (preg_match('/radio/i',$data1_array[1]))
				{
					$type = 'radio';
				}
				elseif (preg_match('/hidden/i',$data1_array[1]))
				{
					$type = 'hidden';
				}
				else
				{
					$type = 'text';
				}
			}
			else
			{
				$type = 'text';
			}

			$title = preg_replace('/^!([\D|\d]*)/','[$1]', $data1_array[0]);

			$name = 'opt'.$i;

			switch ($type)
			{
				case 'text':

					$this->utils->setDebugMessage(__CLASS__, '_MD_NAME', _MD_NAME);
					$this->utils->setDebugMessage(__CLASS__, 'title', $title);
					$title = preg_replace('/^[\[?]([\D|\d]*)[\*?][\]?]$/','$1',$title);
					$this->utils->setDebugMessage(__CLASS__, 'title', $title);

					if (preg_match(_MD_NAME, $title))
					{
						$member_handler =& xoops_gethandler('member');
//						$user =& $member_handler->getUser($this->sessionHandler->getUid());
						$user =& $this->sessionHandler->getUser();
						if (is_object($user))
						{
							$posted_value = htmlspecialchars($user->getVar('name'), ENT_QUOTES);
						}
					}

					$size = '';
					$comment = '';
					if (count($data1_array) > 1)
					{
						for($j=0; $j<count($data1_array); $j++)
						{
							if (preg_match('/\#/i',$data1_array[$j]))
							{
								$comment.= $data1_array[$j];
							}
							elseif (preg_match('/size/i',$data1_array[$j]))
							{
								$size = $data1_array[$j];
								$temp_array = split('=',$size);
								$check_size = $temp_array[1];
								$check_size = preg_replace('/ /','',$check_size);
								if ($check_size > 14)
								{
									$size = 'size="14"';
								}
								unset($temp_array);unset($check_size);

								if ($size > 14)
								{
									$size = 14;
								}
							}
						}
					}

					if (isset($comment))
					{
						$comment = str_replace('#','',$comment);
					}
					else
					{
						$comment = '';
					}

					$form_option_array[$i]['title'] = $title;
					$form_option_array[$i]['value'] = '<input type="text" name="'.$name.'" value="'.$posted_value.'" '.$size.' />&nbsp;'.$comment;
					unset($title);unset($name);unset($size);unset($comment);
					break;

				case 'hidden':

//					$form_option_array[$i]['title'] = $title;
					if (count($data1_array) > 2)
					{
						$value = $data1_array[2];
						$form_option_array[$i]['value'] = '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
					}
					break;

				case 'radio':

					if (count($data1_array) > 2)
					{
						$comment = '';
						for($j=2; $j<count($data1_array); $j++)
						{
							if (preg_match('/\#/i',$data1_array[$j]))
							{
								$comment .= str_replace('#','',$data1_array[$j]);
							}
							else
							{
								if (ereg('\+',$data1_array[$j]))
								{
									$checked = 'checked';
									$val = str_replace('+','',$data1_array[$j]);
								}
								else
								{
									$checked = '';
									$val = $data1_array[$j];
								}
								$temp_html .= '<input type="radio" name="'.$name.'" value="'.$val.'" '.$checked.' />'.$val;
								unset($val);
							}
						}
						$form_option_array[$i]['title'] = $title;
						$form_option_array[$i]['value'] = $temp_html.'&nbsp;'.$comment;
						unset($temp_html);unset($comment);
					}
					else
					{
						$form_option_array[$i]['title'] = '';
						$form_option_array[$i]['value'] = '';
					}
					unset($title);unset($name);
					break;

				case 'checkbox':

					if (count($data1_array) > 2)
					{
						$comment = '';
						for($j=2; $j<count($data1_array); $j++)
						{
							if (preg_match('/\#/i',$data1_array[$j]))
							{
								$comment .= str_replace('#','',$data1_array[$j]);
							}
							else
							{
								if (ereg('\+',$data1_array[$j]))
								{
									$checked = 'checked';
									$val = str_replace('+','',$data1_array[$j]);
								}
								else
								{
									$checked = '';
									$val = $data1_array[$j];
								}
								$temp_html .= '<input type="checkbox" name="'.$name.'" value="'.$val.'" '.$checked.' />'.$val;
								unset($val);
							}
						}
						$form_option_array[$i]['title'] = $title;
						$form_option_array[$i]['value'] = $temp_html.'&nbsp;'.$comment;
						unset($temp_html);unset($comment);
					}
					else
					{
						$form_option_array[$i]['title'] = '';
						$form_option_array[$i]['value'] = '';
					}
					unset($title);unset($name);
					break;

				case 'select':

					if (count($data1_array) > 2)
					{
						$temp_html .= '<select name="'.$name.'">';
						for($j=2; $j<count($data1_array); $j++)
						{
							if (ereg('\+',$data1_array[$j]))
							{
								$checked = 'selected';
								$val = str_replace('+','',$data1_array[$j]);
							}
							else
							{
								$checked = '';
								$val = $data1_array[$j];
							}
							$temp_html .= '<option value="'.$val.'" '.$checked.'>'.$val.'</option>';
							unset($val);
						}
						$temp_html .= '</select>';
						$form_option_array[$i]['title'] = $title;
						$form_option_array[$i]['value'] = $temp_html;
						unset($temp_html);
					}
					else
					{
						$form_option_array[$i]['title'] = '';
						$form_option_array[$i]['value'] = '';
					}
					unset($title);unset($name);
					break;
			}
			unset($data1_array);
		}

		return $form_option_array;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getReservationForm($eid,$posted_option=null)
	{
		$myts =& MyTextSanitizer::getInstance();
		$entry_type = htmlspecialchars($this->utils->getGetPost('entry_type', 'new'), ENT_QUOTES);
		$rvid = intval($this->utils->getGetPost('rvid', 0));
		$exid = intval($this->utils->getGetPost('exid', 0));
//		$uid = $this->sessionHandler->getUid();
		$rdate = time();
		$user =& $this->sessionHandler->getUser();
		$key = $myts->makeTboxData4Save($this->utils->getGet('key', ''));
		$this->setBaseUrl();

/*
		if ($this->moduleConfig['has_confirm'] && (count($items) || !$this->moduleConfig['member_only']))
		{
			$op = 'confirm';
		}
		else
		{
			$op = 'order';
		}
*/
		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
//		$baseUrl = preg_replace('/&amp;/i','&',$baseUrl);

		$form = '<hr />';
		$form .= '<form action="'.$baseUrl.'" method="post">';
		$form .= '<div>';
		$form .= $this->ticket->getTicketHtml();
		$form .= '<input type="hidden" name="op" value="reserve" />';
		$form .= '<input type="hidden" name="entry_type" value="'.$entry_type.'" />';
		$form .= '<input type="hidden" name="HTTP_REFERER" value="'.$this->baseUrl.'" />';
		$form .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
		$form .= '<input type="hidden" name="eid" value="'.$eid.'" />';
		$form .= '<input type="hidden" name="exid" value="'.$exid.'" />';
		$form .= '<input type="hidden" name="rvid" value="'.$rvid.'" />';

		if ($entry_type == 'delete')
		{
			$form .= _MD_RESERV_CANCEL.'<br />';
			$form .= '<input type="hidden" name="key" value="'.$key.'" />';
			$form .= '<input type="submit" name="submit" value="'._MD_CANCEL.'" />&nbsp;';
			$form .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
			$form .= '</div>';
			$form .= '</form>';
		}
		elseif ($entry_type == 'new')
		{
			$form .= _MD_RESERV_FORM.'<br />';
			$form .= _MD_EMAIL.'*:';
			if (is_object($user))
			{
				$email = $user->getVar('email');
				$form .= $email.'<br />';
				$form .= '<input type="hidden" name="email" value="'.$email.'" />';
			}
			else
			{
				if (isset($_POST['email']))
				{
					$email = $myts->stripSlashesGPC($_POST['email']);
				}
				else
				{
					$email = '';
				}
				$form .= '<input type="text" name="email" value="'.$email.'" size="14" /><br />';
			}

			$option_data = $this->getEventOption($eid);
			$option_array = $this->getFormOption($option_data['optfield'],$posted_option);
			$show_note_1 = false;
			$show_note_2 = false;
			foreach($option_array as $option)
			{
				if (array_key_exists('title',$option))
				{
					if ($option['title'] != '')
					{
						$form .= $option['title'].':';
					}
				}
				if (array_key_exists('value',$option))
				{
					$form .= $option['value'].'<br />';
				}
				if ($option['need'])
				{
					$show_note_1 = true;
				}
				if ($option['show'])
				{
					$show_note_2 = true;
				}
			}

			$form .= '<input type="submit" name="submit" value="'._MD_RESERVATION.'" />&nbsp;';
//			$form .= '<input type="submit" name="cancel" value="'._CANCEL.'" />';
			$form .= '</div>';
			$form .= '</form>';

			if ($show_note_1 == true)
			{
				$form .= _MD_ORDER_NOTE1.'<br />';
			}
			if ($show_note_2 == true)
			{
				$form .= _MD_ORDER_NOTE2;
			}
		}

		return $form;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function saveReservation($entry_type)
	{
		$myts =& MyTextSanitizer::getInstance();

		if (isset($_POST['cancel']))
		{
			$extra = 'eid='.$this->item_id;
			$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
//			$baseUrl = preg_replace("/&amp;/","&",$baseUrl);
//			$baseUrl = XMOBILE_URL.'/?act=plugin&view=default&plg=eguide&sess='.$this->sessionHandler->getSessionID();
			header('Location: '.$baseUrl);
			exit();
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'saveReservation entry_type', $entry_type);

		//チケットの確認
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
			return _MD_XMOBILE_TICKET_ERROR;
		}

		$body = '';
		$option_data = $this->getEventOption($this->item_id);

		if ($entry_type == 'delete')// 予約の削除
		{
			$eid = intval($this->utils->getPost('eid', 0));
			$exid = intval($this->utils->getPost('exid', 0));
			$strict = intval($option_data['strict']);
			$persons = intval($option_data['persons']);
			$rvid = intval($this->utils->getPost('rvid', 0));
			$key = $myts->makeTboxData4Save($this->utils->getPost('key', ''));

			$sql = 'DELETE FROM '.$this->db->prefix('eguide_reserv').' WHERE rvid='.$rvid.' AND confirm='.$key;
			if (!$ret = $this->db->query($sql))
			{
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'saveReservation sql', $sql);
				$this->utils->setDebugMessage(__CLASS__, 'saveReservation db error', $this->db->error());
				$body .= _MD_XMOBILE_DELETE_FAILED;
			}
			else
			{
				$body .= _MD_XMOBILE_DELETE_SUCCESS;
				$this->decrementReserve($eid, $exid, $strict, $persons);
			}
			return $body;
		}
		elseif ($entry_type == 'new')// 予約の登録
		{
			if ($this->moduleConfig['member_only'] && !is_object($this->sessionHandler->getUser()))
			{
				return _MD_RESERV_NEEDLOGIN;
			}

			// stop reservation or limit over
			if (empty($option_data['reservation']))
			{
				return _MD_RESERV_STOP;
			}

			$ret = $this->checkReservationForm($option_data);
			if ($ret['errs'] != '')
			{
				$body .= $ret['errs'];
				$body .= $this->getReservationForm($this->item_id,$ret['opt']);
				return $body;
			}

			$eid = intval($this->utils->getPost('eid', 0));
			$exid = intval($this->utils->getPost('exid', 0));
			$uid = $this->sessionHandler->getUid();
			$rdate = time();
			$email = $myts->makeTboxData4Save($this->utils->getPost('email', ''));
			$option_data = $this->getEventOption($eid);
			$info = $myts->makeTareaData4Save($this->getInfoValues($option_data['optfield']));
			$strict = intval($option_data['strict']);
			$persons = intval($option_data['persons']);
			$status = intval($option_data['autoaccept']);
			$notify = intval($option_data['notify']);
			$confirm = $myts->makeTboxData4Save($this->utils->getPost('confirm', rand(10000,99999)));

			$sql = "INSERT INTO ".$this->db->prefix('eguide_reserv')." (eid,exid,uid,rdate,email,info,status,confirm) VALUES ($eid,$exid,$uid,$rdate,'$email','$info',$status,'$confirm')";
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'saveReservation insert reserve sql', $sql);
			if (!$ret = $this->db->query($sql))
			{
				// debug
				$this->utils->setDebugMessage(__CLASS__, 'saveReservation db error', $this->db->error());
			}
			else
			{
				$rvid = $this->db->getInsertId();
//				$sql = 'SELECT e.eid,e.uid,e.edate,e.title,e.summary,e.body,r.rvid,r.exid,r.rdate,r.email,r.info,r.status,r.confirm FROM '.$this->db->prefix('eguide').' e INNER JOIN '.$this->db->prefix('eguide_reserv').' r ON e.eid=r.eid WHERE r.rvid='.$rvid;
				$sql = 'SELECT e.eid,e.uid,e.edate,e.title,e.summary,e.body,o.notify,r.rvid,r.exid,r.rdate,r.email,r.info,r.status,r.confirm FROM '.$this->db->prefix('eguide').' e INNER JOIN '.$this->db->prefix('eguide_opt').' o ON e.eid=o.eid INNER JOIN '.$this->db->prefix('eguide_reserv').' r ON e.eid=r.eid WHERE r.rvid='.$rvid;
				if (!$ret = $this->db->query($sql))
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'saveReservation select reserve sql', $sql);
					$this->utils->setDebugMessage(__CLASS__, 'saveReservation db error', $this->db->error());
				}
				$reserve_data = $this->db->fetchArray($ret);
				if ($this->orderNotify($reserve_data))
				{
					$this->incrementReserve($eid, $exid, $strict, $persons);
//					$body .= _MD_RESERVATION.'<br />';
					$body .= $this->utils->getDateLong($reserve_data['edate']).' '.$myts->makeTboxData4Show($reserve_data['title']).'<br />';
					$body .= _MD_RESERV_ACCEPT.'<br />';
					$body .= _MD_RESERV_CONF.'<br />';
					$body .= _MD_EMAIL.':'.$email.'<br />';
//					$patterns = array('/\n/','/!/','/\*/');
//					$patterns = array('/!/','/\*/');
//					$replacements = '';
//					$info = preg_replace($patterns, $replacements,$myts->makeTboxData4Show($info));
//					$info = preg_replace("/\n/","<br />",$info);
					$body .= preg_replace("/\n/","<br />",$myts->makeTboxData4Show($info));
//					$body .= _MD_DUP_REGISTER;
				}
				else
				{
					$sql = 'DELETE FROM '.$this->db->prefix('eguide_reserv').' WHERE rvid='.$rvid;
//					$this->db->query($sql);
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'saveReservation delete reserve sql', $sql);
					$body .= _MD_SEND_ERR.'<br />';
				}
			}
			return $body;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getInfoValues($optfield)
	{
		$myts =& MyTextSanitizer::getInstance();

		if ($optfield == '')
		{
			return false;
		}

//		$opt_array = preg_split('/[\n\r]/',$optfield);
		$opt_array = preg_split('/\n/',$optfield);

		$info = '';
		$info_array = array();
		$i = 0;
		foreach($opt_array as $opt_ele)
		{
			$opt = preg_split('/,/',$opt_ele);
			$input_opt = $this->utils->getPost('opt'.$i,'');

			if ($opt[0] != '')
			{
				$name = trim(preg_replace('/^!?([\S|\s][^\*]*)\*?$/','$1',$opt[0]));
				array_push($info_array,$myts->makeTboxData4Save($name).': '.$myts->makeTboxData4Save($input_opt));
				$i++;

				// debug
				$this->utils->setDebugMessage(__CLASS__, 'getInfoValues opt_ele', $opt_ele.':'.$input_opt);
			}
		}
//		$info = preg_replace('/\n$/','',$info);
		$info = implode("\n",$info_array);
		return $info;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function checkReservationForm($option_data)
	{
		$myts =& MyTextSanitizer::getInstance();

		$option_array = explode("\n",$option_data['optfield']);
		$ret = array();
		$ret['errs'] = '';

		// order duplicate check
		if (!$this->moduleConfig['member_only'])
		{
			$email = '';
			if (isset($_POST['email']))
			{
				$email = $myts->stripSlashesGPC($_POST['email']);
			}
			if ($email != '')
			{
				if (!preg_match('/^[\w\-_\.]+@[\w\-_\.]+$/', $email))
				{
					$ret['errs'] .= _MD_EMAIL.": ".htmlspecialchars($email, ENT_QUOTES)." - "._MD_MAIL_ERR.'<br />';
				}
				$email4sql = $myts->makeTboxData4Save(strtolower($email));
//				$result = $this->db->query('SELECT rvid FROM '.$this->db->prefix('eguide_reserv').' WHERE eid='.$this->item_id.' AND exid='.$exid.' AND email='.$email4sql);
				$result = $this->db->query('SELECT rvid FROM '.$this->db->prefix('eguide_reserv').' WHERE eid='.$this->item_id.' AND email='.$email4sql);
			}
			else
			{
				$ret['errs'] .= sprintf(_MD_EMAIL,_MD_XMOBILE_MUST_INPUT).'<br />';
			}
		}
		else
		{
			$user =& $this->sessionHandler->getUser();
			if (is_object($user))
			{
//				$result = $this->db->query('SELECT rvid FROM '.$this->db->prefix('eguide_reserv').' WHERE eid='.$this->item_id.' AND exid='.$exid.' AND uid='.$user->getVar('uid'));
				$result = $this->db->query('SELECT rvid FROM '.$this->db->prefix('eguide_reserv').' WHERE eid='.$this->item_id.' AND uid='.$user->getVar('uid'));
				$email = $user->getVar('email');
			}
			else
			{
				$ret['errs'] .= _MD_RESERV_NEEDLOGIN.'<br />';
			}
		}

		if ($result && $this->db->getRowsNum($result))
		{
			$ret['errs'] .= _MD_EMAIL.':'.htmlspecialchars($email, ENT_QUOTES).'-'._MD_MAIL_ERR.'<br />';
		}
		// checking is there any seat?
		$num = 1;			// how many orders?
		$nlab = $this->moduleConfig['label_persons'];
		if ($nlab && isset($vals[$nlab]))
		{
			$num = intval($vals[$nlab]);
			if ($num<1) $num = 1;
		}

		if ($option_data['strict'])
		{
			if ($option_data['persons'] <= $option_data['reserved'])
			{
				$ret['errs'] .= _MD_RESERV_FULL.'<br />';
			}
			elseif ($option_data['persons'] < ($option_data['reserved']+$num))
			{
				$ret['errs'] .= sprintf($nlab._MD_RESERV_TOMATCH, $num,$option_data['persons']-$option_data['reserved']).'<br />';
			}
		}

		if (count($option_array) > 0)
		{
			for($i=0; $i<count($option_array); $i++)
			{
				$option = trim($this->utils->getPost('opt'.$i, ''));
//				if (preg_match('/^!\s*/', $option_array[$i],$match) && $option == '')
				if (preg_match("/^[!]?(\S*)\*[,]*/", $option_array[$i],$match) && $option == '')
				{
					$item = preg_replace('/^!\s*/', '', $option_array[$i]);
					$ret['errs'] .= sprintf($match[1],_MD_XMOBILE_MUST_INPUT).'<br />';
					
				}
				$ret['opt'][$i] = $myts->makeTboxData4Save($option);
			}
		}

		return $ret;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function incrementReserve($eid, $exid, $strict, $persons, $value=1)
	{
		if ($exid)
		{
			$sql = 'UPDATE '.$this->db->prefix('eguide_extent').' SET reserved=reserved+'.$value.' WHERE exid='.$exid;
		}
		else
		{
			$sql = 'UPDATE '.$this->db->prefix('eguide_opt').' SET reserved=reserved+'.$value.' WHERE eid='.$eid;
		}

		if ($strict)
		{
			$sql .= ' AND reserved<='.($persons - $value);
		}

		$res = $this->db->query($sql);
		return $res && $this->db->getAffectedRows();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function decrementReserve($eid, $exid, $strict, $persons, $value=1)
	{
		if ($exid)
		{
			$sql = 'UPDATE '.$this->db->prefix('eguide_extent').' SET reserved=reserved-'.$value.' WHERE exid='.$exid;
		}
		else
		{
			$sql = 'UPDATE '.$this->db->prefix('eguide_opt').' SET reserved=reserved-'.$value.' WHERE eid='.$eid;
		}

//		if ($strict)
//		{
//			$sql .= ' AND reserved<='.($persons - $value);
//		}

		$res = $this->db->query($sql);
		return $res && $this->db->getAffectedRows();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getTemplateDir($filename='')
	{
		global $xoopsConfig;
		$lang = $xoopsConfig['language'];
//		$dir = dirname(__FILE__).'/language/%s/mail_template/%s';
		$dir = XOOPS_ROOT_PATH.'/modules/eguide/language/%s/mail_template/%s';
		$path = sprintf($dir,$lang,$filename);
		if (file_exists($path))
		{
			$path = sprintf($dir,$lang,'');
		}
		else
		{
			$path = sprintf($dir,'english','');
		}
		return $path;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function orderNotify($reserve_data)
	{
		global $xoopsConfig;

		$myts =& MyTextSanitizer::getInstance();

		$poster =& new XoopsUser($reserve_data['uid']);
		$user =& $this->sessionHandler->getUser();
		$rvid = intval($reserve_data['rvid']);
		$eid = intval($reserve_data['eid']);
		$exid = intval($reserve_data['exid']);
		$edate = $this->utils->getDateLong($reserve_data['edate']);
		$title = strip_tags($reserve_data['title']);
		$summary = strip_tags($reserve_data['summary']);
		$email = trim($reserve_data['email']);
		$info = strip_tags($reserve_data['info'])."\n";
		$status = intval($reserve_data['status']);
		$confirm = intval($reserve_data['confirm']);
		$notify = intval($reserve_data['notify']);
		$extra = 'eid='.$eid;
		$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),null,$extra);
//		$baseUrl = preg_replace("/&amp;/","&",$baseUrl);

		if (!checkEmail($email))
		{
			trigger_error('Invalid MailAddress');
			$extra = 'eid='.$eid;
			$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),$this->sessionHandler->getSessionID(),$extra);
			$this->controller->render->redirectHeader(_MD_XMOBILE_INVALIDMAIL,5,$baseUrl);
			exit();
		}

		$tplfilename = $status ? 'accept.tpl' : 'order.tpl';
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplateDir($this->getTemplateDir($tplfilename));
		$xoopsMailer->setTemplate($tplfilename);
		$xoopsMailer->assign('EVENT_URL', $baseUrl);

		if ($this->moduleConfig['member_only'])
		{
			$uinfo = sprintf("%s: %s (%s)\n",_MD_UNAME,$user->getVar('uname'),$user->getVar('name'));
			$xoopsMailer->setToUsers($user);
		}
		else
		{
			$uinfo = '';
		}
		if ($email) $uinfo .= sprintf("%s: %s\n", _MD_EMAIL, $email);

		$xoopsMailer->assign('TITLE', $edate.$title);
		$xoopsMailer->assign('RVID', $rvid);
		$xoopsMailer->assign('CANCEL_KEY', $confirm);
		$extra = 'op=reserve&entry_type=delete&eid='.$eid.'&rvid='.$rvid.'&key='.$confirm;
		$cancelUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'detail',$this->controller->getPluginState(),null,$extra);
//		$cancelUrl = preg_replace("/&amp;/","&",$cancelUrl);
		$xoopsMailer->assign('CANCEL_URL', $cancelUrl);
		$xoopsMailer->assign('INFO', $uinfo.$info);
		$xoopsMailer->assign('SUMMARY', $summary);

		if ($notify && is_object($poster))
		{
			$xoopsMailer->setToUsers($poster);
/*
			if (!in_array($this->moduleConfig['notify_group'], $poster->groups()))
			{
				$xoopsMailer->setToUsers($poster);
			}
			$member_handler =& xoops_gethandler('member');
			$notify_group = $member_handler->getGroup($this->moduleConfig['notify_group']);
			$xoopsMailer->setToGroups($notify_group);
*/
		}

		$xoopsMailer->setSubject(_MD_SUBJECT.' - '.$title);
		$xoopsMailer->setToEmails($email);
		$xoopsMailer->setFromEmail($poster->getVar('email'));
		$xoopsMailer->setFromName($xoopsConfig['sitename'].' - '._MD_FROM_NAME);

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'orderNotify Subject', _MD_SUBJECT.' - '.$title);
		$this->utils->setDebugMessage(__CLASS__, 'orderNotify ToEmail', $email);
		$this->utils->setDebugMessage(__CLASS__, 'orderNotify FromEmail', $poster->getVar('email'));
		$this->utils->setDebugMessage(__CLASS__, 'orderNotify FromName', _MD_FROM_NAME);

		return $xoopsMailer->send();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// コメント用リンクの取得
	function getCommentLink($id)
	{
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/Comments.class.php';
		$xmobile_comment =& new XmobileComments($this->controller,$this,$id,$this->category_id,$this->itemDetailPageNavi->getStart());
		$comment_link = $xmobile_comment->makeCommentLink();
		if ($comment_link)
		{
			$com_count = $xmobile_comment->com_count;
			$this->updateCommentCount($id, $com_count);
			return $comment_link;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>