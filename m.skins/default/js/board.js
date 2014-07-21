/**
 * AJAXBoard XE Module Javascript
 * Copyright (C) 아약스보드. All rights reserved.
 **/

(function($)
{
	AJAXBoardDocPlugin = xe.createPlugin("AJAXBoardDocPlugin",
	{
		init: function(parent)
		{
			parent.registerPlugin(this);
			
			var triggers = [
				[ "clearCommentEditor",    "after",  this.triggerClearCommentEditor ],
				[ "events.connect",        "after",  this.triggerConnect            ],
				[ "events.insertComment",  "before", this.triggerInsertComment      ],
				[ "events.deleteComment",  "before", this.triggerDeleteComment      ],
				[ "events.insertDocument", "before", this.triggerDispDocumentList   ],
				[ "events.deleteDocument", "before", this.triggerDispDocumentList   ],
				[ "events.insertComment",  "before", this.triggerDispDocumentList   ],
				[ "events.deleteComment",  "before", this.triggerDispDocumentList   ]
			];
			
			for (var i = 0; i < triggers.length; i++)
			{
				parent.insertTrigger(triggers[i][0], triggers[i][1], triggers[i][2]);
			}
		},
		triggerClearCommentEditor: function()
		{
			$("textarea#rText").val("");
		},
		triggerConnect: function()
		{
			var that = this;
			
			if (typeof completeInsertComment == "function")
			{
				completeInsertComment = function(obj)
				{
					that.clearCommentEditor();
				}
			}
			if (typeof completeDeleteComment == "function")
			{
				completeDeleteComment = function(obj)
				{
				}
			}
			if (typeof loadPage == "function")
			{
				oldLoadPage = loadPage;
				
				loadPage = function(document_srl, page)
				{
					oAJAXBoard.current_url = oAJAXBoard.current_url.setQuery("document_srl", document_srl).setQuery("cpage", page);
					oldLoadPage(document_srl, page);
				}
			}
			$("div.bd").on("click", "span.auth a.de", function()
			{
				var $this = $(this);
				var url = $this.attr("href");
				if (url.indexOf("#") > -1)
				{
					url = url.substring(0, url.indexOf("#"));
				}
				
				var act = url.getQuery("act");
				var comment_srl = url.getQuery("comment_srl");
				if (comment_srl && act == "dispBoardDeleteComment")
				{
					oAJAXBoard.deleteComment(url, comment_srl);
					return false;
				}
			})
			.on("click", "div.pn a.prev, div.pn a.next", function()
			{
				var $this = $(this);
				var url = $this.attr("href");
				if (url.indexOf("#") > -1)
				{
					url = url.substring(0, url.indexOf("#"));
				}
				
				oAJAXBoardDocPlugin.dispDocumentListByPage(url.getQuery("page"));
				return false;
			});
		},
		triggerInsertComment: function(document_srl, comment_srl)
		{
			if ($("div.bd").length && this.document_srl == document_srl)
			{
				oAJAXBoardDocPlugin.dispComment();
			}
		},
		triggerDeleteComment: function(comment_srl)
		{
			if ($("div.bd").length && $("div[class^='comment_" + comment_srl + "']").length)
			{
				oAJAXBoardDocPlugin.dispComment();
			}
		},
		triggerDispDocumentList: function()
		{
			oAJAXBoardDocPlugin.dispDocumentList();
		},
		dispComment: function()
		{
			var $body = $("div.bd");
			if (!$body.length)
			{
				return false;
			}
			if (!$("h3#clb").length)
			{
				$("div#skip_co").after('<div class="hx h3"><h3 id="clb">댓글 <em>[1]</em></h3><button type="button" class="tg tgr" title="open/close"></button></div>');
			}
			
			var url = oAJAXBoard.current_url;
			return loadPage(url.getQuery("document_srl"), url.getQuery("cpage"));
		},
		dispDocumentList: function(args)
		{
			var $body = $("div.bd");
			if (!$body.length)
			{
				return false;
			}
			
			this.oApp.startAjax();
			
			var that = this;
			var ajax = this.oApp.getPagesHandler(args);
			
			ajax.done(function(response, status, xhr)
			{
				response = $("<div>").append($.parseHTML(response)).find("div.bd");
				
				var $body = $("div.bd");
				var $header = $body.children("div.hx").children("h2");
				var $content = $body.children("ul.lt");
				var $footer = $body.children("div.pn");
				
				var header = response.children("div.hx").children("h2").html();
				var content = response.children("ul.lt").html();
				var footer = response.children("div.pn").html();
				
				$content.html(content);
				$footer.html(footer);
			})
			.fail(function(xhr, status, error)
			{
				try {console.error("%s: %s, %o", status, error, xhr)}
				catch(e) {}
			})
			.always(function()
			{
				that.oApp.stopAjax();
			});
			
			return ajax;
		},
		dispDocumentListByPage: function(page)
		{
			var that = this;
			
			return this.dispDocumentList({page: page}).done(function(response, status, xhr)
			{
				that.oApp.current_url = that.oApp.current_url.setQuery("page", page);
			});
		}
	});
})(jQuery);

jQuery(function($)
{
	oAJAXBoardDocPlugin = new AJAXBoardDocPlugin(oAJAXBoard);
});