<?php
// メインメニュー画面
//
if (!defined('XOOPS_ROOT_PATH')) exit();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
class DefaultAction extends XmobileAction
{
	var $template = 'xmobile_default.html';
	var $showBacktoMain = 0;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function DefaultAction()
	{
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setTitle()
	{
		$this->controller->render->setTitle(_MD_XMOBILE_MAIN_MENU);
		$this->controller->render->template->assign('page_title',_MD_XMOBILE_MAIN_MENU);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getDefaultView()
	{
		global $xoopsModuleConfig;

		$session_id = $this->sessionHandler->getSessionId();
		$uid = $this->sessionHandler->getUid();
		$user =& $this->sessionHandler->getUser();

		// メインメニューを表示
		if ($xoopsModuleConfig['access_level'] == 1 && $uid == 0)
		{
			$this->controller->render->template->assign('message',_MD_XMOBILE_MEMBERS_ONLY);
		}
		else
		{
			$module_handler =& xoops_gethandler('module');
			$criteria = new CriteriaCompo(new Criteria('isactive', 1));
			$criteria->add(new Criteria('weight',0,'<>'));
			$criteria->setSort('weight');
			$modules =& $module_handler->getObjects($criteria);

			if (is_array($modules))
			{
				$mainmenu = array();
				$module_count = 0;
				foreach($modules as $module)
				{
					$mid = $module->getVar('mid');
					$name = $module->getVar('name');
					$dirname = $module->getVar('dirname');
					// 利用可能なモジュールを表示
					if (in_array($dirname, $xoopsModuleConfig['modules_can_use']))
					{
						if ($dirname != 'Analyzer' && $dirname != 'logcounterx')
						{
							// モジュールのグループアクセス権限チェック
							if ($this->utils->getModulePerm($user, $mid))
							{
								$module_count++;
								$mainmenu[$module_count]['url'] =  $this->utils->getLinkUrl('plugin',null,$dirname,$session_id);
								$mainmenu[$module_count]['title'] = $name;
							}
						}
					}
				}
				if ($module_count == 0)
				{
					$this->controller->render->template->assign('message',_MD_XMOBILE_NO_DATA);
				}
				else
				{
					$this->controller->render->template->assign('show_menu',true);
					$this->controller->render->template->assign('mainmenu',$mainmenu);
				}
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>
