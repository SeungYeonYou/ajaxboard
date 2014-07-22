<?php

/**
 * @class  ajaxboardAdminModel
 * @author 이즈야 (contact@ajaxboard.co.kr)
 * @brief  AJAXBoard module admin model class.
 **/

class ajaxboardAdminModel extends ajaxboard
{
	function init()
	{
	}
	
	function getAjaxboardAdminVariables()
	{
		$oAjaxboardModel = getModel('ajaxboard');
		$module_config   = $oAjaxboardModel->getConfig();
		
		$lang = new stdClass();
		$lang->msg_connected = Context::getLang('msg_connected');
		$lang->msg_connection_failed = Context::getLang('msg_connection_failed');
		
		$result = new stdClass();
		$result->lang       = $lang;
		$result->timeout    = $module_config->timeout;
		$result->token      = $module_config->token;
		$result->server_url = $module_config->server_url;
		
		$this->adds($result);
	}
}

/* End of file ajaxboard.admin.model.php */
/* Location: ./modules/ajaxboard/ajaxboard.admin.model.php */