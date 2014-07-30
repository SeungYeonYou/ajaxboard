<?php

/**
 * @class  ajaxboardModel
 * @author 이즈야 (contact@ajaxboard.co.kr)
 * @brief  AJAXBoard module model class.
 **/

class ajaxboardModel extends ajaxboard
{
	function init()
	{
	}
	
	function getConfig()
	{
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('ajaxboard');
		if (!$module_config->timeout) $module_config->timeout = 1000;
		
		$this->module_config = $module_config;
		
		return $this->module_config;
	}
	
	function getSkin($skin = NULL, $skin_type = 'P', $site_srl = 0)
	{
		if ($skin == '/USE_DEFAULT/')
		{
			$oModuleModel = getModel('module');
			$skin = $oModuleModel->getModuleDefaultSkin('ajaxboard', $skin_type, $site_srl, false);
		}
		
		return $skin;
	}
	
	function getSkinVars($module_srl)
	{
		$oModuleModel = getModel('module');
		$skin_vars = $oModuleModel->getModuleSkinVars($module_srl);
		
		return $skin_vars;
	}
	
	function getMobileSkinVars($module_srl)
	{
		$oModuleModel = getModel('module');
		$mskin_vars = $oModuleModel->getModuleMobileSkinVars($module_srl);
		
		return $mskin_vars;
	}
	
	function arrangeSkinVars($skin_vars)
	{
		$res = new stdClass();
		if (!$skin_vars)
		{
			return NULL;
		}
		foreach ($skin_vars as $key=>$val)
		{
			$res->{$key} = $val->value;
		}
		
		return $res;
	}
	
	function getModulesInfo($column_list = array())
	{
		$module_srls = false;
		
		$oCacheHandler = CacheHandler::getInstance('object', NULL, true);
		if ($oCacheHandler->isSupport())
		{
			$object_key = 'module_ajaxboard_module_srls';
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$module_srls = $oCacheHandler->get($cache_key);
		}
		if ($module_srls === false)
		{
			$output = executeQueryArray('ajaxboard.getAllAjaxboard', NULL, array('module_srl'));
			$modules_info = $output->data;
			$module_srls = array();
			foreach ($modules_info as $module_info)
			{
				$module_srls[] = $module_info->module_srl;
			}
			if ($oCacheHandler->isSupport())
			{
				$oCacheHandler->put($cache_key, $module_srls);
			}
		}
		
		$oModuleModel = getModel('module');
		$modules_info = $oModuleModel->getModulesInfo($module_srls, $column_list);
		
		return $modules_info;
	}
	
	function getLinkedModuleInfoByModuleSrl($module_srl)
	{
		if (!$module_srl)
		{
			return NULL;
		}
		
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		
		return $this->getLinkedModuleInfoByMid($module_info->mid);
	}
	
	function getLinkedModuleInfoByMid($mid)
	{
		if (!($mid && preg_match('/^[a-z][a-z0-9_]+$/i', $mid)))
		{
			return NULL;
		}
		
		$module_info = false;
		
		$oCacheHandler = CacheHandler::getInstance('object', NULL, true);
		if ($oCacheHandler->isSupport())
		{
			$object_key = 'module_ajaxboard_linked_info:' . $mid;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$module_info = $oCacheHandler->get($cache_key);
		}
		if ($module_info === false)
		{
			$modules_info = $this->getModulesInfo();
			if (!$modules_info)
			{
				return NULL;
			}
			foreach ($modules_info as $val)
			{
				$mid_list = explode('|@|', $val->mid_list);
				if (in_array($mid, $mid_list))
				{
					$module_info = $val;
					break;
				}
			}
			if ($module_info === false)
			{
				$module_info = NULL;
			}
			if ($oCacheHandler->isSupport())
			{
				$oCacheHandler->put($cache_key, $module_info);
			}
		}
		
		return $module_info;
	}
	
	function getNotifyModuleInfoByDocumentSrl($document_srl)
	{
		if (!$document_srl)
		{
			return NULL;
		}
		
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
		
		return $this->getNotifyModuleInfoByMid($module_info->mid);
	}
	
	function getNotifyModuleInfoByMid($mid)
	{
		if (!($mid && preg_match('/^[a-z][a-z0-9_]+$/i', $mid)))
		{
			return NULL;
		}
		
		$modules_info = false;
		
		$oCacheHandler = CacheHandler::getInstance('object', NULL, true);
		if ($oCacheHandler->isSupport())
		{
			$object_key = 'module_ajaxboard_notify_info:' . $mid;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$modules_info = $oCacheHandler->get($cache_key);
		}
		if ($modules_info === false)
		{
			$module_srls = array();
			$target_modules_info = $this->getModulesInfo();
			foreach ($target_modules_info as $val)
			{
				$notify_list = explode('|@|', $val->notify_list);
				if (in_array($mid, $notify_list))
				{
					$module_srls[] = $val->module_srl;
				}
			}
			
			$oModuleModel = getModel('module');
			$modules_info = $oModuleModel->getModulesInfo($module_srls);
			if ($oCacheHandler->isSupport())
			{
				$oCacheHandler->put($cache_key, $modules_info);
			}
		}
		
		return $modules_info;
	}
	
	function getNotify($config)
	{
		if (!$config)
		{
			$config = new stdClass();
		}
		
		$output = executeQueryArray('ajaxboard.getNotify', $config);
		$notify_info = $output->data;
		if (!$notify_info)
		{
			$notify_info = array();
		}
		
		return $notify_info;
	}
	
	function getAllNotify($notified = 'N')
	{
		$args = new stdClass();
		$args->notified = $notified;
		
		$output = executeQueryArray('ajaxboard.getAllNotify', $args);
		$notify_list = $output->data;
		if (!$notify_list)
		{
			$notify_list = array();
		}
		
		return $notify_list;
	}
	
	function arrangeNotify($notify = array())
	{
		$result = array();
		foreach ($notify as $val)
		{
			$result[] = $val->notify_srl;
		}
		
		return $result;
	}
	
	function getUsableMidList($module_srl = 0, $args = NULL, $column_list = array())
	{
		$oModuleModel = getModel('module');
		$mid_list = $oModuleModel->getMidList($args, $column_list);
		$modules_info = $this->getModulesInfo();
		
		if (!$modules_info)
		{
			return NULL;
		}
		foreach ($modules_info as $module_info)
		{
			if ($module_info->module_srl == $module_srl)
			{
				continue;
			}
			$module_info->mid_list = array_fill_keys(explode('|@|', $module_info->mid_list), true);
			$mid_list = array_diff_key($mid_list, $module_info->mid_list);
		}
		
		return $mid_list;
	}
	
	function getSummary($text, $strlen = 0, $endstr = '...')
	{
		if ($text && $strlen > 0)
		{
			$text = cut_str(trim(strip_tags(nl2br($text))), $strlen, $endstr);
		}
		return $text;
	}
	
	function loadDefaultComponents($target = 'client')
	{
		$module_config = $this->getConfig();
		
		$files = array(
			array(sprintf('%stpl/js/libs/intrinsic.function.js', $this->module_path), 'head', NULL, 0),
			array(sprintf('%stpl/js/ajaxboard.%s.js', $this->module_path, $target), 'head', NULL, 0)
		);
		
		if (Mobile::isFromMobilePhone())
		{
			$files[] = array('./common/js/jquery.js', 'head', NULL, -111000);
			$files[] = array('./common/js/xe.js', 'head', NULL, -110000);
		}
		if ($module_config->use_cdn == 'Y')
		{
			$files[] = array(sprintf('///cdn.socket.io/socket.io-%s.js', self::socket_io_version), 'head', NULL, -100000);
		}
		else
		{
			$files[] = array(sprintf('%stpl/js/libs/socket.io.js', $this->module_path), 'head', NULL, -100000);
		}
		
		foreach ($files as $file)
		{
			Context::loadFile($file);
		}
	}
	
	function loadSkinComponents($skin = NULL, $dir = 'skins', $site_srl = 0)
	{
		$this->loadDefaultComponents();
		
		$skin = $this->getSkin($skin, $dir === 'skins' ? 'P' : 'M', $site_srl);
		$template_path = sprintf('%s%s/%s/', $this->module_path, $dir, $skin);
		$template_file = 'common';
		
		$oTemplate = TemplateHandler::getInstance();
		$output = $oTemplate->compile($template_path, $template_file);
		
		Context::addHtmlFooter($output);
		
		return $output;
	}
	
	function getAjaxboardWholeVariables()
	{
		$mid = Context::get('mid');
		$document_srl = Context::get('document_srl');
		$logged_info = Context::get('logged_info');
		
		$module_config = $this->getConfig();
		$module_info = $this->getLinkedModuleInfoByMid($mid);
		if (!$module_info)
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		$oModuleModel = getModel('module');
		$origin_module_info = $oModuleModel->getModuleInfoByMid($mid);
		
		$lang = new stdClass();
		$lang->msg_delete_comment = Context::getLang('msg_delete_comment');
		$lang->msg_password_required = Context::getLang('msg_password_required');
		
		$result = new stdClass();
		$result->lang         = $lang;
		$result->module_path  = $this->module_path;
		$result->module_srl   = $module_info->module_srl;
		$result->member_srl   = $logged_info->member_srl;
		$result->document_srl = $document_srl;
		$result->notify_list  = array_fill_keys(explode('|@|', $module_info->notify_list), true);
		$result->use_wfsr     = $module_info->use_wfsr;
		$result->timeout      = $module_config->timeout;
		$result->token        = $module_config->token;
		$result->server_url   = $module_config->server_url;
		
		if (Mobile::isFromMobilePhone() && $origin_module_info->use_mobile == 'Y')
		{
			if ($module_info->use_module_mobile == 'Y')
			{
				$result->skin_info = $this->arrangeSkinVars($this->getMobileSkinVars($module_info->module_srl));
			}
		}
		else if ($module_info->use_module_pc == 'Y')
		{
			$result->skin_info = $this->arrangeSkinVars($this->getSkinVars($module_info->module_srl));
		}
		
		$this->adds($result);
	}
	
	function getAjaxboardDocument()
	{
		$document_srl = Context::get('document_srl');
		
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($oDocument->get('module_srl'));
		
		$args = new stdClass();
		$args->is_exists     = $oDocument->isExists();
		$args->is_granted    = $oDocument->isGranted();
		$args->is_accessible = $oDocument->isAccessible();
		$args->module_srl    = $oDocument->get('module_srl');
		$args->document_srl  = $oDocument->get('document_srl');
		$args->member_srl    = $oDocument->getMemberSrl();
		$args->title         = $this->getSummary($oDocument->getTitleText(), 30);
		$args->content       = $this->getSummary($oDocument->getContentText(), 30);
		$args->nickname      = $this->getSummary($oDocument->getNickName(), 12);
		$args->voted_count   = $oDocument->get('voted_count');
		$args->blamed_count  = $oDocument->get('blamed_count');
		$args->mid           = $module_info->mid;
		
		$this->adds($args);
	}
	
	function getAjaxboardComment()
	{
		$comment_srl = Context::get('comment_srl');
		
		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl);
		
		if (!$oComment->get('parent_srl'))
		{
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($oComment->get('document_srl'));
			$oComment->add('parent_srl', $oDocument->get('member_srl'));
		}
		
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($oComment->get('module_srl'));
		
		$args = new stdClass();
		$args->is_exists     = $oComment->isExists();
		$args->is_granted    = $oComment->isGranted();
		$args->is_accessible = $oComment->isAccessible();
		$args->module_srl    = $oComment->get('module_srl');
		$args->parent_srl    = $oComment->get('parent_srl');
		$args->document_srl  = $oComment->get('document_srl');
		$args->comment_srl   = $oComment->get('comment_srl');
		$args->member_srl    = $oComment->getMemberSrl();
		$args->content       = $this->getSummary($oComment->getContentText(), 30);
		$args->nickname      = $this->getSummary($oComment->getNickName(), 12);
		$args->voted_count   = $oComment->get('voted_count');
		$args->blamed_count  = $oComment->get('blamed_count');
		$args->mid           = $module_info->mid;
		
		$this->adds($args);
	}
	
	function triggerAfterModuleListInSitemap(&$obj)
	{
		array_push($obj, 'ajaxboard');
	}
}

/* End of file ajaxboard.model.php */
/* Location: ./modules/ajaxboard/ajaxboard.model.php */