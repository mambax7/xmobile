<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
require_once XOOPS_ROOT_PATH.'/class/xoopstree.php';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileTree extends XoopsTree
{
	var $id = null;
	var $pid = null;
	var $title = null;
	var $order = null;
	var $criteria = null;
	var $limit = 0;
	var $page_start = 0;

	var $utils;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function XmobileTree($table_name, $id_name, $pid_name, $title_name=null, $order=null, $criteria=null)
	function XmobileTree($table_name, $id_name, $pid_name, $title_name=null, $order=null)
	{
		$myts =& MyTextSanitizer::getInstance();
		XoopsTree::XoopsTree($table_name, $id_name, $pid_name);

		$this->title = $myts->addSlashes($title_name);
		$this->order = $myts->addSlashes($order);
//		if (is_object($criteria))
//		{
//			$this->criteria =& $criteria;
//		}

		global $xmobileControl;
		$this->utils =& $xmobileControl->utils;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setCriteria($criteria)
	{
		if (is_object($criteria))
		{
			$this->criteria =& $criteria;
			$this->limit = $this->criteria->getLimit();
			$this->page_start = $this->criteria->getStart();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getTitileById($id)
	{
		$myts =& MyTextSanitizer::getInstance();

		$id = intval($id);
		$sql = sprintf('SELECT %s FROM %s WHERE %s = %d', $this->title, $this->table, $this->id, $id);

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getTitileById sql', $sql);

		$result = $this->db->query($sql);
		$count = $this->db->getRowsNum($result);
		if ($count == 1)
		{
			list($title) = $this->db->fetchRow($result);
			return $myts->makeTboxData4Show($title);
		}
		else
		{
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getTitileLinkById($id, $funcURL)
	{
		$myts =& MyTextSanitizer::getInstance();

		$id = intval($id);
		$sql = sprintf('SELECT %s FROM %s WHERE %s = %d', $this->title, $this->table, $this->id, $id);

		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'getTitileById sql', $sql);

		$result = $this->db->query($sql);
		$count = $this->db->getRowsNum($result);
		if ($count == 1)
		{
			list($title) = $this->db->fetchRow($result);
			$title = $myts->makeTboxData4Show($title);
			$title_link = '<a href="'.$funcURL.'&amp;'.$this->id.'='.$id.'">'.$title.'</a>';
			return $title_link;
		}
		else
		{
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getFirstChildCount($sel_id)
	{
		$sel_id = intval($sel_id);
		$count = 0;
		$this->limit = 0;
		$this->page_start = 0;

		$sql = 'SELECT '.$this->id.' FROM '.$this->table;
		if (!is_null($this->pid))
		{
			$sql .= ' WHERE '.$this->pid.'='.$sel_id;
		}

		$result = $this->db->query($sql,$this->limit,$this->page_start);
		$count = $this->db->getRowsNum($result);

		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'getFirstChildCount sql', $sql);
//		$this->utils->setDebugMessage(__CLASS__, 'getFirstChildCount count', $count);

		return $count;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getFirstChildId($sel_id)
	{
		$sel_id = intval($sel_id);
		$idarray = array();
		$count = 0;

		$sql = 'SELECT '.$this->id.' FROM '.$this->table;
		if (!is_null($this->pid))
		{
			$sql .= ' WHERE '.$this->pid.'='.$sel_id;
		}
		if (!is_null($this->order))
		{
			$sql .= ' ORDER BY '.$this->order;
		}

		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'getFirstChildId sql', $sql);

		$result = $this->db->query($sql,$this->limit,$this->page_start);
		$count = $this->db->getRowsNum($result);
		if ($count == 0)
		{
			return false;
		}
//		while(list($id) = $this->db->fetchRow($result))
		while ($row = $this->db->fetchArray($result))
		{
//			array_push($idarray, $id);
			array_push($idarray, intval($row[$this->id]));
		}
		return $idarray;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getFirstChild($sel_id)
	{
		$myts =& MyTextSanitizer::getInstance();

		$sel_id = intval($sel_id);
		$arr = array();
		$count = 0;

		$sql = 'SELECT * FROM '.$this->table;
		if (!is_null($this->pid))
		{
			$sql .= ' WHERE '.$this->pid.'='.$sel_id;
		}
		if (!is_null($this->order))
		{
			$sql .= ' ORDER BY '.$this->order;
		}

		$result = $this->db->query($sql,$this->limit,$this->page_start);
		$count = $this->db->getRowsNum($result);

		if ($count == 0)
		{
			return false;
		}
		while($row = $this->db->fetchArray($result))
		{
			$arr[] = $row;
		}

		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'getFirstChild sql', $sql);
//		$this->utils->setDebugMessage(__CLASS__, 'getFirstChild count', $count);

		return $arr;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getAllChildId($sel_id, $idarray = array())
	{
		$sel_id = intval($sel_id);
		$count = 0;
		$this->limit = 0;
		$this->page_start = 0;

		$sql = 'SELECT '.$this->id.' FROM '.$this->table;
		if (!is_null($this->pid))
		{
			$sql .= ' WHERE '.$this->pid.'='.$sel_id;
		}

		$result = $this->db->query($sql);
		$count = $this->db->getRowsNum($result);
		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'getAllChildId sql', $sql);

		if ($count == 0)
		{
			return $idarray;
		}
//		while (list($r_id) = $this->db->fetchRow($result))
		while ($row = $this->db->fetchArray($result))
		{
			$r_id = intval($row[$this->id]);
			array_push($idarray, $r_id);
			$idarray = $this->getAllChildId($r_id, $idarray);
		}
		return $idarray;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getChildTreeArray($sel_id=0,$parray=array(),$r_prefix='',$criteria=null)
	{
		$sel_id = intval($sel_id);
		$count = 0;

		$sql = 'SELECT * FROM '.$this->table;
		if (!is_null($this->pid))
		{
			$sql .= ' WHERE '.$this->pid.'='.$sel_id;
		}
		if (is_object($criteria))
		{
			$criteria_str = $criteria->render();
			if ($criteria_str != '')
			{
				$sql .= ' AND '.$criteria_str;
			}
		}
		if (!is_null($this->order))
		{
			$sql .= ' ORDER BY '.$this->order;
		}

		// debug
//		$this->utils->setDebugMessage(__CLASS__, 'getChildTreeArray sql', $sql);

		$result = $this->db->query($sql,$this->limit,$this->page_start);
		$count = $this->db->getRowsNum($result);

		if ($count == 0)
		{
			return $parray;
		}
//		$p_title = $this->getTitileById($sel_id);
		while($row = $this->db->fetchArray($result))
		{
//			if ($p_title != '')
//			{
//				$row['prefix'] = $p_title.':'.$r_prefix;
//			}
//			else
//			{
//				$row['prefix'] = $r_prefix.':';
//			}

			$row['prefix'] = $r_prefix.'.';
			if (!is_null($this->pid))
			{
				array_push($parray, $row);
				$parray = $this->getChildTreeArray($row[$this->id],$parray,$row['prefix'],$criteria);
			}
		}

		return $parray;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getNicePathFromId($sel_id, $funcURL, $prefix='-', $path='')
	{
		$myts =& MyTextSanitizer::getInstance();
		$sel_id = intval($sel_id);

		$sql = 'SELECT '.$this->pid.','.$this->title.' FROM '.$this->table;
		if (!is_null($this->pid))
		{
			$sql .= ' WHERE '.$this->id.'='.$sel_id;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getNicePathFromId sql', $sql);

		$result = $this->db->query($sql);
		if ($this->db->getRowsNum($result) == 0)
		{
			return $path;
		}
		list($parentid,$name) = $this->db->fetchRow($result);
		$name = $myts->makeTboxData4Show($name);
//		$path = '<a href="'.$funcURL.'&amp;'.$this->id.'='.$sel_id.'">'.$name.'</a>';
		$path = '<a href="'.$funcURL.'&amp;'.$this->id.'='.$sel_id.'">'.$name.'</a><br />'.$path;
		if ($parentid == 0)
		{
			return $path;
		}
		$path = $this->getNicePathFromId($parentid, $funcURL, $prefix, $prefix.$path);
		return $path;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeMySelBox($preset_id=0, $none=0, $onchange='', $criteria=null)
	{
		$myts =& MyTextSanitizer::getInstance();
		$cat_sel_box = '';
		$cat_sel_box .= '<select name="'.$this->id.'"';

		if ($onchange != '')
		{
			$cat_sel_box .= ' onchange="'.$onchange.'"';
		}
		$cat_sel_box .= '>';

		if (!is_null($this->pid))
		{
			$sql = 'SELECT '.$this->id.', '.$this->title.' FROM '.$this->table.' WHERE '.$this->pid.'=0';
		}
		else
		{
			$sql = 'SELECT '.$this->id.', '.$this->title.' FROM '.$this->table;
		}

		if (is_object($criteria))
		{
			$criteria_str = $criteria->render();
			if ($criteria_str != '')
			{
				$sql .= ' AND '.$criteria_str;
			}
		}
		if (!is_null($this->order))
		{
			$sql .= ' ORDER BY '.$this->order;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'makeMySelBox sql', $sql);

		if (!$result = $this->db->query($sql))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'makeMySelBox error', $this->db->error());
		}

		if ($none)
		{
			$cat_sel_box .= '<option value="0">----</option>';
		}

		while($data = $this->db->fetchArray($result))
		{
			$sel = '';
			if ($data[$this->id] == $preset_id)
			{
				$sel = ' selected="selected"';
			}
			$cat_sel_box .= '<option value="'.$data[$this->id].'"'.$sel.'>'.$myts->makeTboxData4Show($data[$this->title]).'</option>';
			$sel = '';
	//		$arr = $this->getChildTreeArray($data[$this->id],$this->order_col);
			$parray = array();
			$arr = $this->getChildTreeArray($data[$this->id],$parray,null,$criteria);
			foreach($arr as $option)
			{
				$option['prefix'] = str_replace('.','-',$option['prefix']);
				$catpath = $option['prefix'].'&nbsp;'.$myts->makeTboxData4Show($option[$this->title]);
				if ($option[$this->id] == $preset_id)
				{
					$sel = ' selected="selected"';
				}
				$cat_sel_box .= '<option value="'.$option[$this->id].'"'.$sel.'>'.$catpath.'</option>';
				$sel = '';
			}
		}
		$cat_sel_box .= '</select>';
		return $cat_sel_box;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// retrun array for render selectbox
	function getAllTreeArray($criteria=null)
	{
		$myts =& MyTextSanitizer::getInstance();

		if (!is_null($this->pid))
		{
			$sql = 'SELECT '.$this->id.', '.$this->title.' FROM '.$this->table.' WHERE '.$this->pid.'=0';
		}
		else
		{
			$sql = 'SELECT '.$this->id.', '.$this->title.' FROM '.$this->table;
		}

		if (is_object($criteria))
		{
			$criteria_str = $criteria->render();
			if ($criteria_str != '')
			{
				$sql .= ' AND '.$criteria_str;
			}
		}
		if (!is_null($this->order))
		{
			$sql .= ' ORDER BY '.$this->order;
		}

		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getAllTreeArray sql', $sql);

		if (!$result = $this->db->query($sql))
		{
			// debug
			$this->utils->setDebugMessage(__CLASS__, 'getAllTreeArray error', $this->db->error());
		}

		$treeArray = array();

		while($data = $this->db->fetchArray($result))
		{
			$treeArray[$data[$this->id]] = $myts->makeTboxData4Show($data[$this->title]);
	//		$arr = $this->getChildTreeArray($data[$this->id],$this->order_col);
			$parray = array();
			$arr = $this->getChildTreeArray($data[$this->id],$parray,null,$criteria);
			foreach($arr as $option)
			{
				$treeArray[$option[$this->id]] = $myts->makeTboxData4Show($option[$this->title]);
			}
		}

		return $treeArray;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
