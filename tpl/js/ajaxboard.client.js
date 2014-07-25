/**
 * AJAXBoard XE Module Javascript
 * Copyright (C) 아약스보드. All rights reserved.
 **/

(function($)
{
	/* Major class of AJAXBoard. */
	AJAXBoard = xe.createApp("AJAXBoard",
	{
		init: function(request_uri, current_url, current_mid)
		{
			var that = this;
			
			this.request_uri = request_uri;
			this.current_url = current_url;
			this.current_mid = current_mid;
			this.timeout     = 1000;
			this.triggers    = {
				before : [],
				after  : []
			};
		},
		connect: function()
		{
			if (!(this.token && this.server_url)) return this;
			
			var query = buildQuery({
				token      : this.token,
				mid        : this.current_mid,
				member_srl : this.member_srl
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
				that.triggerCall("events.connect", "before");
				
				that.socket.on("notice", function(message)
				{
					that.triggerCall("events.notice", "before", [message]);
				});
				that.socket.on("noticeOfServer", function(message)
				{
					that.triggerCall("events.noticeOfServer", "before", [message]);
				});
				that.socket.on("insertDocument", function(target_srl)
				{
					var oDocument = that.getDocumentHandler(target_srl);
					
					oDocument.done(function(response, status, xhr)
					{
						if (that.notify_list[response.mid] && !(that.member_srl && that.member_srl == response.member_srl))
						{
							that.triggerCall("events.notifyDocument", "before", [
								response.document_srl,
								response.title,
								response.content,
								response.nickname
							]);
						}
						that.triggerCall("events.insertDocument", "before", [
							response.document_srl
						]);
					});
				});
				that.socket.on("deleteDocument", function(target_srl)
				{
					that.triggerCall("events.deleteDocument", "before", [target_srl]);
				});
				that.socket.on("documentVoteUp", function(target_srl)
				{
					var oDocument = that.getDocumentHandler(target_srl);
					
					oDocument.done(function(response, status, xhr)
					{
						that.triggerCall("events.documentVoteUp", "before", [
							response.document_srl,
							response.voted_count
						]);
					});
				});
				that.socket.on("documentVoteDown", function(target_srl)
				{
					var oDocument = that.getDocumentHandler(target_srl);
					
					oDocument.done(function(response, status, xhr)
					{
						that.triggerCall("events.documentVoteDown", "before", [
							response.document_srl,
							response.blamed_count
						]);
					});
				});
				that.socket.on("insertComment", function(target_srl)
				{
					var oComment = that.getCommentHandler(target_srl);
					
					oComment.done(function(response, status, xhr)
					{
						if (that.member_srl && that.member_srl != response.member_srl && that.member_srl == response.parent_srl)
						{
							that.triggerCall("events.notifyComment", "before", [
								response.document_srl,
								response.comment_srl,
								response.content,
								response.nickname
							]);
						}
						that.triggerCall("events.insertComment", "before", [
							response.document_srl,
							response.comment_srl
						]);
					});
				});
				that.socket.on("deleteComment", function(target_srl)
				{
					that.triggerCall("events.deleteComment", "before", [target_srl]);
				});
				that.socket.on("commentVoteUp", function(target_srl)
				{
					var oComment = that.getCommentHandler(target_srl);
					
					oComment.done(function(response, status, xhr)
					{
						that.triggerCall("events.commentVoteUp", "before", [
							response.document_srl,
							response.comment_srl,
							response.voted_count
						]);
					});
				});
				that.socket.on("commentVoteDown", function(target_srl)
				{
					var oComment = that.getCommentHandler(target_srl);
					
					oComment.done(function(response, status, xhr)
					{
						that.triggerCall("events.commentVoteDown", "before", [
							response.document_srl,
							response.comment_srl,
							response.blamed_count
						]);
					});
				});
				
				that.triggerCall("events.connect", "after");
			});
			this.socket.on("error", function(reason)
			{
				try {console.error("Unable to socket.io: %s", reason)}
				catch(e) {}
				
				that.triggerCall("events.error", "after");
			});
			
			return this;
		},
		insertTrigger: function(name, hook, callback)
		{
			if ($.isArray(this.triggers[hook]))
			{
				var trigger = {
					name     : name,
					callback : callback
				};
				this.triggers[hook].push(trigger);
			}
			else
			{
				return false;
			}
			return true;
		},
		getTriggers: function(name, hook)
		{
			var triggers = [];
			
			if (!$.isArray(this.triggers[hook]))
			{
				return triggers;
			}
			
			this.triggers[hook].sort(function(primary, secondary)
			{
				if (primary.name == secondary.name)
				{
					return 0;
				}
				else if (secondary.name == name)
				{
					return 1;
				}
				return -1;
			});
			
			for (var i = 0; i < this.triggers[hook].length; i++)
			{
				var trigger = this.triggers[hook][i];
				
				if (trigger.name == name)
				{
					triggers.push(trigger.callback);
				}
				else
				{
					break;
				}
			}
			
			return triggers;
		},
		triggerCall: function(name, hook, params)
		{
			var triggers = this.getTriggers(name, hook);
			
			for (var i = 0; i < triggers.length; i++)
			{
				var callback = triggers[i];
				
				if ($.isFunction(callback))
				{
					if (callback.apply(this, params || []) === false)
					{
						return false;
					}
				}
			}
			return true;
		},
		procAjax: function(request_uri, module, act, params, type, data_type)
		{
			this.triggerCall("procAjax", "before", [request_uri, module, act, params, type, data_type]);
			
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
			
			this.triggerCall("procAjax", "after", [params]);
			
			return $.ajax(params);
		},
		startAjax: function()
		{
			this.triggerCall("startAjax", "before");
			
			var waiting_obj = $(".wfsr");
			
			if (this.use_wfsr && show_waiting_message && waiting_obj.length)
			{
				var timeout_id = waiting_obj.data("timeout_id");
				if (timeout_id)
				{
					clearTimeout(timeout_id);
				}
				
				waiting_obj.css("opacity", "").html(waiting_message).show();
			}
			this.triggerCall("startAjax", "after");
			
			return this;
		},
		stopAjax: function()
		{
			this.triggerCall("stopAjax", "before");
			
			var waiting_obj = $(".wfsr");
			
			if (this.use_wfsr && show_waiting_message && waiting_obj.length)
			{
				waiting_obj.hide().css("opacity", 0);
			}
			this.triggerCall("stopAjax", "after");
			
			return this;
		},
		getPagesHandler: function(params)
		{
			return this.procAjax(this.current_url, null, null, params, "GET", "html");
		},
		getWholeVariablesHandler: function()
		{
			return this.procAjax(this.current_url, "ajaxboard", "getAjaxboardWholeVariables", {mid: this.current_mid}, "POST", "json");
		},
		getDocumentHandler: function(document_srl)
		{
			return this.procAjax(this.current_url, "ajaxboard", "getAjaxboardDocument", {document_srl: document_srl}, "POST", "json");
		},
		getCommentHandler: function(comment_srl)
		{
			return this.procAjax(this.current_url, "ajaxboard", "getAjaxboardComment", {comment_srl: comment_srl}, "POST", "json");
		},
		setWholeVariables: function()
		{
			var that = this;
			var ajax = this.getWholeVariablesHandler();
			
			ajax.done(function(response, status, xhr)
			{
				$.extend(xe.lang, response.lang);
				that.module_path  = response.module_path;
				that.module_srl   = response.module_srl;
				that.member_srl   = response.member_srl;
				that.document_srl = response.document_srl;
				that.notify_list  = response.notify_list;
				that.use_wfsr     = response.use_wfsr == "Y" ? true : false;
				that.timeout      = response.timeout;
				that.token        = response.token;
				that.server_url   = response.server_url;
				that.skin_info    = response.skin_info;
			})
			.fail(function(xhr, status, error)
			{
				try {console.error("%s: %s, %o", status, error, xhr)}
				catch(e) {}
			});
			
			return ajax;
		},
		deleteComment: function(url, comment_srl)
		{
			this.startAjax();
			this.triggerCall("deleteComment", "before", [url, comment_srl]);
			
			var that = this;
			var ajax = this.getCommentHandler(comment_srl);
			
			ajax.done(function(response, status, xhr)
			{
				if (response.is_granted)
				{
					if (confirm(xe.lang.msg_delete_comment))
					{
						var params = {
							mid         : that.current_mid,
							comment_srl : comment_srl
						};
						exec_xml("board", "procBoardDeleteComment", params, completeDeleteComment, ["error", "message", "mid", "document_srl", "page"]);
					}
				}
				else if (response.is_exists)
				{
					if (confirm(xe.lang.msg_password_required))
					{
						location.href = url;
					}
				}
				else
				{
					that.triggerCall("deleteComment.alreadyDeleted", "after", [comment_srl]);
				}
			})
			.fail(function(xhr, status, error)
			{
				try {console.error("%s: %s, %o", status, error, xhr)}
				catch(e) {}
			})
			.always(function()
			{
				that.stopAjax();
			});
			
			this.triggerCall("deleteComment", "after", [url, comment_srl]);
			
			return this;
		},
		clearCommentEditor: function()
		{
			$("input[name='comment_srl']").val("");
			
			// XpressEditor
			if ($("div.xpress-editor").length)
			{
				var seq              = $("div.xpress_xeditor_editing_area_container").attr("id").split("-")[3];
				var uploadFileObj    = $("#uploaded_file_list_" + seq + " option");
				var uploadPreviewObj = $("#preview_uploaded_" + seq);
				
				$("#editor_iframe_" + seq).contents().find("body").html("");
				
				if (uploadFileObj.length)
				{
					uploadedFiles = [];
					uploadFileObj.remove();
					uploadPreviewObj.empty();
					uploaderSettings[seq].uploadTargetSrl = "";
				}
			}
			// XE TextEditor
			if ($("div.xeTextEditor").length)
			{
				$("div.xeTextEditor textarea").val("");
			}
			// TextyleEditor
			$("div.textyleEditor button.del").trigger("click");
			
			this.triggerCall("clearCommentEditor", "after");
			
			return this;
		},
		scrollToComment: function(type, animate_time, indicator)
		{
			this.triggerCall("scrollToComment", "before", [type, animate_time, indicator]);
			
			var pos, obj = $(indicator);
			if (!obj.length)
			{
				return this;
			}
			
			switch (type)
			{
				case 1:
					pos = obj.offset().top;
					break;
				case 2:
					pos = obj.offset().top + obj.outerHeight() - ($(window).height() / 2);
					break;
				case 3:
					pos = obj.offset().top + obj.outerHeight();
					break;
				default:
					try {console.error("error: Invalid type.")}
					catch(e) {}
					return this;
			}
			$("html, body").stop().animate({scrollTop: pos}, animate_time * 1000, "easeInOutExpo");
			
			this.triggerCall("scrollToComment", "after", [type, animate_time, indicator]);
			
			return this;
		}
	});
})(jQuery);

jQuery(function($)
{
	/* Create instance. */
	oAJAXBoard = new AJAXBoard(request_uri, current_url, current_mid);
	/* Register AJAXBoard to XE App. */
	xe.registerApp(oAJAXBoard);
});