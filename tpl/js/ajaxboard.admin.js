/**
 * AJAXBoard XE Module Javascript
 * Copyright (C) 아약스보드. All rights reserved.
 **/

(function($)
{
	AJAXBoardAdmin = xe.createApp("AJAXBoardAdmin",
	{
		init: function(request_uri)
		{
			this.request_uri = request_uri;
		},
		connect: function()
		{
			if (!(this.token && this.server_url)) return this;
			
			var query = buildQuery({
				token   : this.token,
				respond : true
			});
			
			this.socket = io(this.server_url,
			{
				query: query,
				reconnectionAttempts: 10
			});
			
			return this.bindListeners();
		},
		bindListeners: function()
		{
			if (!this.socket) return this;
			
			var that = this;
			
			this.socket.on("connect", function()
			{
				$("#ajaxboard #status").html(xe.lang.msg_connected);
			});
			this.socket.on("error", function(reason)
			{
				$("#ajaxboard #status").html(reason);
			});
			this.socket.on("reconnect_error", function()
			{
				$("#ajaxboard #status").html(xe.lang.msg_connection_failed);
			});
			this.socket.on("reconnect_failed", function()
			{
				$("#ajaxboard #status").html(xe.lang.msg_connection_failed);
			});
			
			return this;
		},
		procAjax: function(request_uri, module, act, params, type, data_type)
		{
			type      = type      ? type.toUpperCase()      : type = "GET";
			data_type = data_type ? data_type.toLowerCase() : data_type = "html";
			
			var content_type;
			
			switch (data_type)
			{
				case "html":
					content_type = "text/html";
					break;
				case "json":
				case "jsonp":
					content_type = "application/json";
					break;
				case "xml":
					content_type = "application/xml";
					break;
				default:
					content_type = "text/plain";
					break;
			}
			
			params     = $.isPlainObject(params) ? params : {};
			params.mid = this.current_mid;
			
			if (module) params.module = module;
			if (act)    params.act    = act;
			
			params = {
				url         : request_uri,
				type        : type,
				dataType    : data_type,
				contentType : content_type,
				data        : params,
				global      : false,
				timeout     : this.timeout
			};
			
			return $.ajax(params);
		},
		getWholeVariablesHandler: function()
		{
			return this.procAjax(this.request_uri, "ajaxboard", "getAjaxboardAdminVariables", null, "POST", "json");
		},
		setWholeVariables: function()
		{
			var that = this;
			var ajax = this.getWholeVariablesHandler();
			
			ajax.done(function(response, status, xhr)
			{
				$.extend(xe.lang, response.lang);
				that.timeout    = response.timeout;
				that.server_url = response.server_url;
				that.token      = response.token;
			})
			.fail(function(xhr, status, error)
			{
				try {console.error("%s: %s, %o", status, error, xhr)}
				catch(e) {}
			});
			
			return ajax;
		}
	});
})(jQuery);

jQuery(function($)
{
	/* Create instance. */
	oAJAXBoardAdmin = new AJAXBoardAdmin(request_uri);
	/* Register AJAXBoard to XE App. */
	xe.registerApp(oAJAXBoardAdmin);
	/* Connect to the server. */
	oAJAXBoardAdmin.setWholeVariables().done(function()
	{
		oAJAXBoardAdmin.connect();
	});
});