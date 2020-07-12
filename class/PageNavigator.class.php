<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobilePageNavigator
{
	var $total = 0;
	var $perpage = 0;
	var $page_start = 0;
	var $start_name;
	var $url = '';
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function XmobilePageNavigator($total, $items_perpage, $extra_arg='')
	function XmobilePageNavigator($total, $items_perpage, $start_name='start', $extra_arg='')
	{
		$this->total = intval($total);
		$this->perpage = intval($items_perpage);
		$this->start_name = trim($start_name);
		$this->url = $extra_arg.'&amp;'.$this->start_name.'=';
		$this->setStart();

		global $xmobileControl;
		$xmobileControl->utils->setDebugMessage(__CLASS__, 'page_start', $this->page_start);
		$xmobileControl->utils->setDebugMessage(__CLASS__, 'total', $this->total);
		$xmobileControl->utils->setDebugMessage(__CLASS__, 'perpage', $this->perpage);
//		$xmobileControl->utils->setDebugMessage(__CLASS__, 'start_name', $this->start_name);
//		$xmobileControl->utils->setDebugMessage(__CLASS__, 'url', $this->url);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderNavi($offset = 2)
	{
		$ret = '';

		if ($this->total <= $this->perpage)
		{
			return $ret;
		}
		$total_pages = ceil($this->total / $this->perpage);

		if ($total_pages > 1)
		{
			$prev = $this->page_start - $this->perpage;

//			$ret .= '<hr />';
			if ($prev >= 0)
			{
//				$ret .= '<a href="'.$this->url.$prev.'"><<</a> ';
				$ret .= '<a href="'.$this->url.$prev.'">&lt;&lt;</a> ';
			}
			$counter = 1;
			$current_page = intval(floor(($this->page_start + $this->perpage) / $this->perpage));

			while($counter <= $total_pages)
			{
				if ($counter == $current_page)
				{
					$ret .= '<b>('.$counter.')</b> ';
				}
				elseif (($counter > $current_page-$offset && $counter < $current_page + $offset ) || $counter == 1 || $counter == $total_pages)
				{
					if ($counter == $total_pages && $current_page < $total_pages - $offset)
					{
//						$ret .= '... ';
						$ret .= '..';
					}

					$ret .= '<a href="'.$this->url.(($counter - 1) * $this->perpage).'">'.$counter.'</a> ';

					if ($counter == 1 && $current_page > 1 + $offset)
					{
//						$ret .= '... ';
						$ret .= '..';
					}
				}
				$counter++;
			}

			$next = $this->page_start + $this->perpage;

			if ($this->total > $next)
			{
//				$ret .= '<a href="'.$this->url.$next.'">>></a> ';
				$ret .= '<a href="'.$this->url.$next.'">&gt;&gt;</a> ';
			}
		}

		return $ret;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setStart()
	{
		if (isset($_GET[$this->start_name]))
		{
			$this->page_start = intval($_GET[$this->start_name]);
		}
		elseif (isset($_POST[$this->start_name]))
		{
			$this->page_start = intval($_POST[$this->start_name]);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getStart()
	{
		return $this->page_start;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getPerpage()
	{
		return $this->perpage;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>