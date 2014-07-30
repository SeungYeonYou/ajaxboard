<?php

/**
 * @class  ajaxboardAdminView
 * @author 이즈야 (contact@ajaxboard.co.kr)
 * @brief  AJAXBoard module admin view class.
 **/

class ajaxboardAdminView extends ajaxboard
{
	function init()
	{
		$oModuleModel = getModel('module');
		$oAjaxboardModel = getModel('ajaxboard');
		$module_srl = Context::get('module_srl');
		
		if (!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}
		if ($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if (!$module_info)
			{
				Context::set('module_srl', '');
			}
			else
			{
				$oModuleModel->syncModuleToSite($module_info);
				$module_info->mid_list = explode('|@|', $module_info->mid_list);
				$module_info->notify_list = explode('|@|', $module_info->notify_list);
				$this->module_info = $module_info;
				Context::set('module_info', $module_info);
			}
		}
		if ($module_info && $module_info->module != 'ajaxboard')
		{
			return $this->stop('msg_invalid_request');
		}
		
		$module_config = $oAjaxboardModel->getConfig();
		$module_category = $oModuleModel->getModuleCategories();
		
		if (is_array($this->order_target))
		{
			foreach($this->order_target as $key)
			{
				$order_target[$key] = Context::getLang($key);
			}
		}
		$order_target['list_order'] = Context::getLang('regdate');
		$order_target['update_order'] = Context::getLang('last_update');
		
		Context::set('module_config', $module_config);
		Context::set('module_category', $module_category);
		Context::set('order_target', $order_target);
		Context::set('socket_io_version', self::socket_io_version);
		
		$security = new Security();
		$security->encodeHTML('module_info.');
		$security->encodeHTML('module_config.');
		$security->encodeHTML('module_category..');
		
		$this->setTemplatePath(sprintf('%stpl/', $this->module_path));
	}
	
	function dispAjaxboardAdminContent()
	{
		$args = new stdClass();
		$args->sort_index = 'module_srl';
		$args->list_count = 20;
		$args->page_count = 10;
		$args->page = Context::get('page');
		$args->module_category_srl = Context::get('module_category_srl');
		
		$search_target = Context::get('search_target');
		$search_keyword = Context::get('search_keyword');
		
		switch ($search_target)
		{
			case 'mid':
				$args->mid = $search_keyword;
				break;
			case 'browser_title':
				$args->browser_title = $search_keyword;
				break;
		}
		
		$oModuleModel = getModel('module');
		
		$output = executeQueryArray('ajaxboard.getAjaxboardList', $args);
		$oModuleModel->syncModuleToSite($output->data);
		
		$skin_list = $oModuleModel->getSkins($this->module_path);
		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');
		
		Context::set('page', $output->page);
		Context::set('total_page', $output->total_page);
		Context::set('total_count', $output->total_count);
		Context::set('skin_list', $skin_list);
		Context::set('mskin_list', $mskin_list);
		Context::set('layout_list', $layout_list);
		Context::set('mlayout_list', $mlayout_list);
		Context::set('ajaxboard_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		
		$oModuleAdminModel = getAdminModel('module');
		$selected_manage_content = $oModuleAdminModel->getSelectedManageHTML($this->xml_info->grant, array('tab1'=>1, 'tab3'=>1));
		Context::set('selected_manage_content', $selected_manage_content);
		
		$security = new Security();
		$security->encodeHTML('ajaxboard_list..browser_title', 'ajaxboard_list..mid');
		$security->encodeHTML('skin_list..title', 'mskin_list..title');
		$security->encodeHTML('layout_list..title', 'layout_list..layout');
		$security->encodeHTML('mlayout_list..title', 'mlayout_list..layout');
		
		$this->setTemplateFile('index');
	}
	
	function dispAjaxboardAdminConfig()
	{
		$this->setTemplateFile('insert_config');
	}
	
	function dispAjaxboardAdminInsertAjaxboard()
	{
		$this->dispAjaxboardAdminAjaxboardInfo();
	}
	
	function dispAjaxboardAdminDeleteAjaxboard()
	{
		$module_srl = Context::get('module_srl');
		
		if (!$module_srl)
		{
			return $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAjaxboardAdminContent'));
		}
		
		$security = new Security();
		$security->encodeHTML('module_info..module', 'module_info..mid');
		
		$this->setTemplateFile('delete_ajaxboard');
	}
	
	function dispAjaxboardAdminServerInfo()
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$oAjaxboardModel->loadDefaultComponents('admin');
		$this->setTemplateFile('server_info');
	}
	
	function dispAjaxboardAdminSendPush()
	{
		$oMemberAdminModel = getAdminModel('member');
		$oMemberModel = getModel('member');
		
		$member_config = $oMemberModel->getMemberConfig();
		$group_list = $oMemberModel->getGroups();
		$member_list = $oMemberAdminModel->getMemberList();
		
		$filter = Context::get('filter_type');
		switch ($filter)
		{
			case 'super_admin':
				Context::set('filter_type_title', Context::getLang('cmd_show_super_admin_member'));
				break;
			case 'site_admin':
				Context::set('filter_type_title', Context::getLang('cmd_show_site_admin_member'));
				break;
			default:
				Context::set('filter_type_title', Context::getLang('cmd_show_all_member'));
		}
		if ($member_list->data)
		{
			foreach ($member_list->data as $key=>$member)
			{
				$member_list->data[$key]->group_list = $oMemberModel->getMemberGroups($member->member_srl, 0);
			}
		}
		
		$memberIdentifiers = array('email_address'=>'email_address', 'user_id'=>'user_id', 'nick_name'=>'nick_name');
		$usedIdentifiers = array();
		
		if (is_array($member_config->signupForm))
		{
			foreach ($member_config->signupForm as $signupItem)
			{
				if (!count($memberIdentifiers))
				{
					break;
				}
				if (in_array($signupItem->name, $memberIdentifiers) && ($signupItem->required || $signupItem->isUse))
				{
					unset($memberIdentifiers[$signupItem->name]);
					$usedIdentifiers[$signupItem->name] = Context::getLang($signupItem->name);
				}
			}
		}
		
		Context::set('total_count', $member_list->total_count);
		Context::set('total_page', $member_list->total_page);
		Context::set('page', $member_list->page);
		Context::set('group_list', $group_list);
		Context::set('member_list', $member_list->data);
		Context::set('usedIdentifiers', $usedIdentifiers);
		Context::set('page_navigation', $member_list->page_navigation);
		
		$security = new Security();
		$security->encodeHTML('group_list..', 'member_list..user_name', 'member_list..nick_name', 'member_list..group_list..');
		
		$this->setTemplateFile('send_push');
	}
	
	function dispAjaxboardAdminSendPushPopup()
	{
		if (!Context::get('is_logged'))
		{
			return $this->stop('msg_not_logged');
		}
		
		$logged_info = Context::get('logged_info');
		$notice = Context::get('notice');
		$receiver_srl = Context::get('receiver_srl');
		
		if (!$notice && !$receiver_srl)
		{
			return $this->stop('msg_invalid_request');
		}
		if ($receiver_srl)
		{
			$oMemberModel = getModel('member');
			$receiver_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
			if (!$receiver_info)
			{
				return $this->stop('msg_not_exists_member');
			}
			$receiver_info->group_list = implode(', ', $receiver_info->group_list);
			Context::set('receiver_info', $receiver_info);
		}
		
		$oEditorModel = getModel('editor');
		$option = new stdClass();
		$option->primary_key_name = 'receiver_srl';
		$option->content_key_name = 'message';
		$option->allow_fileupload = FALSE;
		$option->enable_autosave = FALSE;
		$option->enable_default_component = TRUE;
		$option->enable_component = FALSE;
		$option->resizable = FALSE;
		$option->disable_html = TRUE;
		$option->height = 300;
		$editor = $oEditorModel->getEditor($logged_info->member_srl, $option);
		
		Context::set('editor', $editor);
		
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('send_push_popup');
	}
	
	function dispAjaxboardAdminAjaxboardInfo()
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$mid_list = $oAjaxboardModel->getUsableMidList($this->module_info->module_srl, NULL, array('module', 'mid', 'browser_title'));
		
		$oModuleModel = getModel('module');
		$notify_list = $oModuleModel->getMidList(NULL, array('module', 'mid', 'browser_title'));
		$skin_list = $oModuleModel->getSkins($this->module_path);
		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');
		
		Context::set('mid_list', $mid_list);
		Context::set('notify_list', $notify_list);
		Context::set('skin_list', $skin_list);
		Context::set('mskin_list', $mskin_list);
		Context::set('layout_list', $layout_list);
		Context::set('mlayout_list', $mlayout_list);
		
		$security = new Security();
		$security->encodeHTML('mid_list.');
		$security->encodeHTML('skin_list..title', 'mskin_list..title');
		$security->encodeHTML('layout_list..title', 'layout_list..layout');
		$security->encodeHTML('mlayout_list..title', 'mlayout_list..layout');
		
		$this->setTemplateFile('insert_ajaxboard');
	}
	
	function dispAjaxboardAdminGrantInfo()
	{
		$module_srl = Context::get('module_srl');
		if (!$module_srl)
		{
			return $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAjaxboardAdminContent'));
		}
		
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($module_srl, $this->xml_info->grant);
		
		Context::set('grant_content', $grant_content);
		
		$this->setTemplateFile('grant_info');
	}
	
	function dispAjaxboardAdminSkinInfo()
	{
		$module_srl = Context::get('module_srl');
		if (!$module_srl)
		{
			return $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAjaxboardAdminContent'));
		}
		
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($module_srl);
		
		Context::set('skin_content', $skin_content);
		
		$this->setTemplateFile('skin_info');
	}
	
	function dispAjaxboardAdminMobileSkinInfo()
	{
		$module_srl = Context::get('module_srl');
		if (!$module_srl)
		{
			return $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAjaxboardAdminContent'));
		}
		
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($module_srl);
		
		Context::set('skin_content', $skin_content);
		
		$this->setTemplateFile('skin_info');
	}
}

/* End of file ajaxboard.admin.view.php */
/* Location: ./modules/ajaxboard/ajaxboard.admin.view.php */
