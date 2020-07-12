<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileXoopsPollPlugin extends XmobilePlugin
{
	function XmobileXoopsPollPlugin()
	{
		// call parent constructor
		XmobilePlugin::XmobilePlugin();

		// define object elements
		$this->initVar('poll_id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('question', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('description', XOBJ_DTYPE_TXTAREA, '', true);
		$this->initVar('user_id', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('start_time', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('end_time', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('votes', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('voters', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('multiple', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('display', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('weight', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('mail_status', XOBJ_DTYPE_INT, '0', true);
		$this->initVar('voteadd', XOBJ_DTYPE_INT, '0', true);

		// define primary key
		$this->setKeyFields(array('poll_id'));
		$this->setAutoIncrementField('poll_id');
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileXoopsPollPluginHandler extends XmobilePluginHandler
{
	var $template = 'xmobile_xoopspoll.html';
	var $moduleDir = 'xoopspoll';
	var $itemTableName = 'xoopspoll_desc';

	var $item_id_fld = 'poll_id';
	var $item_title_fld = 'question';
	var $item_description_fld = 'description';
	var $item_order_fld = 'weight';
	var $item_date_fld = 'start_time';
	var $item_uid_fld = 'user_id';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileXoopsPollPluginHandler($db)
	{
		XmobilePluginHandler::XmobilePluginHandler($db);
//		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/gtickets.php';
		$this->ticket = new XoopsGTicket;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setItemCriteria()
	{
		$this->item_criteria =& new CriteriaCompo();
		$this->item_criteria->add(new Criteria('display', 0, '<>'));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		parent::getListView();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 記事詳細・コメント・編集用リンクの取得
// ただし、戻り値はオブジェクトではなくHTML
	function getItemDetail()
	{
		$myts =& MyTextSanitizer::getInstance();

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getItemDetail criteria', $this->item_criteria->render());
		// 一意のidではなくcriteriaで検索する為、オブジェクトの配列が返される
		if (!$itemObjectArray = $this->getObjects($this->item_criteria))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getItemDetail Error', $this->getErrors());
		}

		if (count($itemObjectArray) == 0) // 表示するデータ無し
		{
			$this->controller->render->template->assign('lang_no_item_list',_MD_XMOBILE_NO_DATA);
			return false;
		}

		$itemObject = $itemObjectArray[0];

		if (!is_object($itemObject))
		{
			return false;
		}

		$detail4html = '';
		$this->item_id = $itemObject->getVar($this->item_id_fld);
		$title = $itemObject->getVar($this->item_title_fld);
		$description = $itemObject->getVar($this->item_description_fld);
		$start_time = $itemObject->getVar('start_time');
		$end_time = $itemObject->getVar('end_time');
		$multiple = $itemObject->getVar('multiple');
		$user =& $this->sessionHandler->getUser();
		if (is_object($user))
		{
			$uid = $user->getVar('uid');
		}
		else
		{
			$uid = 0;
		}

		$detail4html .= $this->getCatPathFromId();
		$detail4html .= _MD_XMOBILE_ITEM_DETAIL.'<br />';
		$detail4html .= _MD_XMOBILE_TITLE.$title.'<hr />';
		if ($description != '')
		{
			$detail4html .= $description.'<br />';
		}

		if (($start_time <= time()) && ($end_time >= time()))
		{
		// 投票
			if (is_object($user))
			{
				$sql = "SELECT log_id FROM ".$this->db->prefix('xoopspoll_log')." WHERE poll_id=".$this->item_id." AND user_id=".$uid;
			}
			else
			{
				$sql = "SELECT log_id FROM ".$this->db->prefix('xoopspoll_log')." WHERE poll_id=".$this->item_id." AND user_id=".$uid." AND ip='".$_SERVER['REMOTE_ADDR']."'";
			}
			$poll_log_count = 0;
			$ret = $this->db->query($sql);
			$poll_log_count = $this->db->getRowsNum($ret);

			if ($poll_log_count == 0)
			{
				$sql = "SELECT option_id,option_text,option_count FROM ".$this->db->prefix('xoopspoll_option')." WHERE poll_id=".$this->item_id;
				$ret = $this->db->query($sql);
				if ($ret)
				{
					$select_html = '';
					while($row = $this->db->fetchArray($ret))
					{
						$option_id = intval($row['option_id']);
						$option_text = $myts->makeTboxData4Show($row['option_text']);

						if ($multiple)
						{
							$select_html .= '<input type="checkbox" name="poll_select[]" value="'.$option_id.'" />'.$option_text.'<br />';
						}
						else
						{
							$select_html .= '<input type="radio" name="poll_select" value="'.$option_id.'" />'.$option_text.'<br />';
						}
					}
				}
				$baseUrl = $this->utils->getLinkUrl($this->controller->getActionState(),'confirm',$this->controller->getPluginState(),$this->sessionHandler->getSessionID());
				$baseUrl = preg_replace('/&amp;/i','&',$baseUrl);
				$detail4html .= _MD_XMOBILE_CHOICES;
				$detail4html .= '<form action="'.$baseUrl.'" method="post">';
				$detail4html .= '<div class="form">';
				$detail4html .= $select_html.'<br />';
				$detail4html .= '<input type="submit" name="submit" value="'._MD_XMOBILE_VOTE.'" />';
				$detail4html .= $this->ticket->getTicketHtml();
				$detail4html .= '<input type="hidden" name="HTTP_REFERER" value="'.$this->baseUrl.'" />';
				$detail4html .= '<input type="hidden" name="'.session_name().'" value="'.session_id().'" />';
				$detail4html .= '<input type="hidden" name="multiple" value="'.$multiple.'" />';
				$detail4html .= '<input type="hidden" name="poll_id" value="'.$this->item_id.'" />';
				$detail4html .= '</div>';
				$detail4html .= '</form>';
			}
			else
			{
				$detail4html .= _MD_XMOBILE_ALREADYVOTED.'<br />';
			}
			$detail4html .= sprintf(_MD_XMOBILE_VOTE_END,$this->utils->getDateLong($end_time).' '.$this->utils->getTimeLong($end_time)).'<br />';
			$detail4html .= _MD_XMOBILE_WAIT_VOTE_END.'<br />';
		}
		else
		{
		// 集計結果
			$detail4html .= sprintf(_MD_XMOBILE_VOTE_ALREADY_END,$this->utils->getDateLong($end_time).' '.$this->utils->getTimeLong($end_time)).'<br />';
			$detail4html .= _MD_XMOBILE_VOTE_RESULTS.'<br />';

			$sql = "SELECT SUM(option_count) AS total FROM ".$this->db->prefix('xoopspoll_option')." WHERE poll_id=".$this->item_id." GROUP BY poll_id";

			$ret = $this->db->query($sql);
			$data = $this->db->fetchArray($ret);
			$option_total = $data['total'];

			$sql = "SELECT option_id,option_text,option_count FROM ".$this->db->prefix('xoopspoll_option')." WHERE poll_id=".$this->item_id." ORDER BY option_count DESC";
			$ret = $this->db->query($sql);
			if ($ret)
			{
				$detail4html .= '-------------------<br />';
				while($row = $this->db->fetchArray($ret))
				{
					$detail4html .= $myts->makeTboxData4Show($row['option_text']).'&nbsp;&nbsp;'.sprintf(_MD_XMOBILE_VOTE_COUNT,$row['option_count']).'<br />';
					if ($option_total != 0)
					{
						$temp_ratio = round(($row['option_count'] / $option_total) * 1000) / 10;
						$bar_ratio = round(($row['option_count'] / $option_total) * 10);
						$detail4html .= '&nbsp;('.$temp_ratio.'%)&nbsp;';
						$detail4html .= str_repeat ('*',$bar_ratio).'<br />';
					}
				$detail4html .= '-------------------<br />';
				}
			}
		}
		return $detail4html;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getConfirmView()
	{
		$this->setBaseUrl();
		$this->setItemParameter();
		$user =& $this->sessionHandler->getUser();
		$uid = $this->sessionHandler->getUid();

		$multiple = intval($this->utils->getPost('multiple', 0));
		$poll_select = $this->utils->getPost('poll_select', 0);
		$poll_id = $this->utils->getPost('poll_id', 0);

		$detail4html = '';
		//チケットの確認
		if (!$ticket_check = $this->ticket->check(true,'',false))
		{
			$detail4html = _MD_XMOBILE_TICKET_ERROR;
			return $detail4html;
			exit();
		}


		$err_ret = 0;
		if ($multiple == 0)
		{
			if ($poll_select == '') $err_ret = -1;
		}
		else
		{
			if (count($poll_select) == 0) $err_ret = -1;
		}

		if ($err_ret == -1)
		{
			$detail4html .= _MD_XMOBILE_NOT_CHOICED;
		}
		else
		{
			if (is_object($user))
			{
				$sql = "SELECT log_id FROM ".$this->db->prefix('xoopspoll_log')." WHERE poll_id=".$poll_id." AND user_id=".$uid;
			}
			else
			{
				$sql = "SELECT log_id FROM ".$this->db->prefix('xoopspoll_log')." WHERE poll_id=".$poll_id." AND user_id=".$uid." AND ip='".$_SERVER['REMOTE_ADDR']."'";
			}
			$poll_log_count = 0;
			$ret = $this->db->query($sql);
			$poll_log_count = $this->db->getRowsNum($ret);

			if ($poll_log_count == 0)
			{
				if ($multiple == 0 || !is_array($poll_select))
				{
//					$op_id = intval($poll_select[0]);
					$op_id = intval($poll_select);
				}
				else
				{
					for($i=0; $i<count($poll_select); $i++)
					{
						$check_val_array[$i] = intval($poll_select[$i]);
					}
				}


				$sql_upd = "UPDATE ".$this->db->prefix('xoopspoll_desc')." SET votes = votes + 1,voters = voters + 1 WHERE poll_id = $poll_id";
				if (!$ret = $this->db->query($sql_upd))
				{
					// debug
					$this->utils->setDebugMessage(__CLASS__, 'update poll_option error', $this->db->error());
				}

				$tb_option_name = $this->db->prefix('xoopspoll_option');
				$tb_log_name = $this->db->prefix('xoopspoll_log');

				if ($multiple)
				{
					foreach($check_val_array as $op_id)
					{
						$sql_upo = "UPDATE ".$this->db->prefix('xoopspoll_option')." SET option_count = option_count+1 WHERE poll_id = $poll_id AND option_id = $op_id";
						if (!$result_upo = $this->db->query($sql_upo))
//						if (!$result_upo = $this->db->queryF($sql_upo))
						{
							// debug
							$this->utils->setDebugMessage(__CLASS__, 'update poll_option error', $this->db->error());
						}
						$local_time = time();
						$sql_upl = "INSERT INTO ".$this->db->prefix('xoopspoll_log')." (poll_id,option_id,ip,user_id,time) VALUES(".$poll_id.",".$op_id.",'".$_SERVER['REMOTE_ADDR']."',".$uid.",".$local_time.")";
//						if (!$result_upl = $this->db->queryF($sql_upl))
						if (!$result_upl = $this->db->query($sql_upl))
						{
							// debug
							$this->utils->setDebugMessage(__CLASS__, 'insert poll_log error', $this->db->error());
						}
					}
				}
				else
				{
					$sql_upo = "UPDATE ".$this->db->prefix('xoopspoll_option')." SET option_count = option_count+1 WHERE poll_id = ".$poll_id." AND option_id = ".$op_id;
					if (!$result_upo = $this->db->query($sql_upo))
//					if (!$result_upo = $this->db->queryF($sql_upo))
					{
						// debug
						$this->utils->setDebugMessage(__CLASS__, 'update poll_option error', $this->db->error());
					}
					$local_time = time();
					$sql_upl = "INSERT INTO ".$this->db->prefix('xoopspoll_log')." (poll_id,option_id,ip,user_id,time) VALUES(".$poll_id.",".$op_id.",'".$_SERVER['REMOTE_ADDR']."',".$uid.",".$local_time.")";
					if (!$result_upl = $this->db->query($sql_upl))
//					if (!$result_upl = $this->db->queryF($sql_upl))
					{
						// debug
						$this->utils->setDebugMessage(__CLASS__, 'insert poll_log error', $this->db->error());
					}
				}

				$detail4html .= _MD_XMOBILE_THANKS_FOR_VOTE;
			}
			else
			{
				$detail4html .= _MD_XMOBILE_CANT_VOTE_TWICE;
			}
		}

		$this->controller->render->template->assign('item_detail',$detail4html);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// コメント用リンクの取得
	function getCommentLink($id)
	{
		include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/Comments.class.php';
		$xmobile_comment =& new XmobileComments($this->controller,$this,$id,0,0);
		$comment_link = $xmobile_comment->makeCommentLink();
		return $comment_link;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
