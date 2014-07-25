<?php

/**
 * @class  ajaxboardController
 * @author 이즈야 (contact@ajaxboard.co.kr)
 * @brief  AJAXBoard module controller class.
 **/

class ajaxboardController extends ajaxboard
{
	function init()
	{
	}
	
	function emitEvent($args = array())
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$module_config = $oAjaxboardModel->getConfig();
		
		$fields = array_merge($args, array(
			'token'       => $module_config->token,
			'private_key' => $module_config->private_key,
		));
		$request_config = array(
			'ssl_verify_peer' => false
		);
		
		$request_url = $module_config->server_url;
		$timeout = $module_config->timeout / 1000;

		$buff = FileHandler::getRemoteResource(
			$request_url,
			NULL,
			$timeout,
			'POST',
			'application/x-www-form-urlencoded',
			array(),
			array(),
			$fields,
			$request_config
		);
		
		return $buff;
	}
	
	function insertNotify($config)
	{
		if (!$config)
		{
			$config = new stdClass();
		}
		if (!($config->type && $config->target_srl))
		{
			return new Object();
		}
		
		$config->notify_srl = getNextSequence();
		$output = executeQuery('ajaxboard.insertNotify', $config);
		return $output;
	}
	
	function deleteNotify($config)
	{
		if (!$config)
		{
			$config = new stdClass();
		}
		
		$output = executeQuery('ajaxboard.deleteNotify', $config);
		return $output;
	}
	
	function updateNotifyNotified($config)
	{
		if (!$config)
		{
			$config = new stdClass();
		}
		
		$output = executeQueryArray('ajaxboard.updateNotifyNotified', $config);
		return $output;
	}
	
	function triggerAfterInsertDocument(&$obj)
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$modules_info = $oAjaxboardModel->getLinkedModuleInfoByMid($obj->mid);
		$notify_info = $oAjaxboardModel->getNotifyModuleInfoByMid($obj->mid);
		if ($modules_info || $notify_info)
		{
			$config = new stdClass();
			$config->type = 'insertDocument';
			$config->target_srl = $obj->document_srl;
			$this->insertNotify($config);
		}
		
		return new Object();
	}
	
	function triggerAfterDeleteDocument(&$obj)
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$module_info = $oAjaxboardModel->getLinkedModuleInfoByModuleSrl($obj->module_srl);
		if ($module_info)
		{
			$config = new stdClass();
			$config->type = 'deleteDocument';
			$config->target_srl = $obj->document_srl;
			$this->insertNotify($config);
		}
		
		return new Object();
	}
	
	function triggerAfterUpdateVotedDocument(&$obj)
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$module_info = $oAjaxboardModel->getLinkedModuleInfoByModuleSrl($obj->module_srl);
		if ($module_info)
		{
			$config = new stdClass();
			$config->type = $obj->update_target == 'voted_count' ? 'documentVoteUp' : 'documentVoteDown';
			$config->target_srl = $obj->document_srl;
			$this->insertNotify($config);
		}
		
		return new Object();
	}
	
	function triggerAfterInsertComment(&$obj)
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$modules_info = $oAjaxboardModel->getModulesInfo();
		if ($modules_info)
		{
			$config = new stdClass();
			$config->type = 'insertComment';
			$config->target_srl = $obj->comment_srl;
			$this->insertNotify($config);
		}
		
		return new Object();
	}
	
	function triggerAfterDeleteComment(&$obj)
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$module_info = $oAjaxboardModel->getLinkedModuleInfoByModuleSrl($obj->module_srl);
		if ($module_info)
		{
			$config = new stdClass();
			$config->type = 'deleteComment';
			$config->target_srl = $obj->comment_srl;
			$this->insertNotify($config);
		}
		
		return new Object();
	}
	
	function triggerAfterUpdateVotedComment(&$obj)
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$module_info = $oAjaxboardModel->getLinkedModuleInfoByModuleSrl($obj->module_srl);
		if ($module_info)
		{
			$config = new stdClass();
			$config->type = $obj->update_target == 'voted_count' ? 'commentVoteUp' : 'commentVoteDown';
			$config->target_srl = $obj->comment_srl;
			$this->insertNotify($config);
		}
		
		return new Object();
	}
	
	function triggerAfterModuleHandlerProc(&$oModule)
	{
		$oModuleModel = getModel('module');
		$oAjaxboardModel = getModel('ajaxboard');
		
		if ($oModule->mid)
		{
			$module_info = $oAjaxboardModel->getLinkedModuleInfoByMid($oModule->mid);
		}
		if ($module_info)
		{
			$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
			if ($grant->service)
			{
				$tmp = Context::get('grant');
				       Context::set('grant', $grant);
			}
			else
			{
				return new Object();
			}
			if (Mobile::isFromMobilePhone() && $oModule->module_info->use_mobile == 'Y')
			{
				if ($module_info->use_module_mobile == 'Y')
				{
					$oAjaxboardModel->loadSkinComponents($module_info->mskin, 'm.skins', $module_info->site_srl);
				}
			}
			else if ($module_info->use_module_pc == 'Y')
			{
				$oAjaxboardModel->loadSkinComponents($module_info->skin, 'skins', $module_info->site_srl);
			}
			Context::set('grant', $tmp);
		}
		
		return new Object();
	}
	
	function triggerAfterModuleObjectProc(&$oModule)
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$notify = $oAjaxboardModel->getAllNotify();
		
		$config = new stdClass();
		$config->notify_srls = $oAjaxboardModel->arrangeNotify($notify);
		if ($config->notify_srls)
		{
			$this->updateNotifyNotified($config);
		}
		foreach ($notify as $val)
		{
			$args = array(
				'type'       => $val->type,
				'target_srl' => $val->target_srl
			);
			$this->emitEvent($args);
		}
		
		return new Object();
	}
	
	function triggerAfterMemberMenu(&$obj)
	{
		$member_srl = Context::get('target_srl');
		$logged_info = Context::get('logged_info');
		
		if ($logged_info->member_srl != $member_srl && $logged_info->is_admin == 'Y')
		{
			$oMemberModel = getModel('member');
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			if (!($member_info && $member_info->member_srl))
			{
				return new Object();
			}
			
			$oMemberController = getController('member');
			$oMemberController->addMemberPopupMenu(getUrl('', 'module', 'ajaxboard', 'act', 'dispAjaxboardAdminSendPushPopup', 'receiver_srl', $member_srl), 'cmd_send_push_notifications', '', 'popup');
		}
		
		return new Object();
	}
}

/* End of file ajaxboard.controller.php */
/* Location: ./modules/ajaxboard/ajaxboard.controller.php */