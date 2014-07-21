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
				[ "events.connect",        "after",  this.triggerConnect          ],
				[ "events.insertComment",  "before", this.triggerInsertComment    ],
				[ "events.deleteComment",  "before", this.triggerDeleteComment    ],
				[ "events.insertDocument", "before", this.triggerDispDocumentList ],
				[ "events.deleteDocument", "before", this.triggerDispDocumentList ],
				[ "events.insertComment",  "before", this.triggerDispDocumentList ],
				[ "events.deleteComment",  "before", this.triggerDispDocumentList ]
			];
			
			for (var i = 0; i < triggers.length; i++)
			{
				parent.insertTrigger(triggers[i][0], triggers[i][1], triggers[i][2]);
			}
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
			$("div#comment").on("click", "p.action a.delete", function()
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
			.on("click", "div.pagination [href]", function()
			{
				var $this = $(this);
				var url = $this.attr("href");
				if (url.indexOf("#") > -1)
				{
					url = url.substring(0, url.indexOf("#"));
				}
				
				var cpage = url.getQuery("cpage");
				oAJAXBoardDocPlugin.dispCommentsByCpage(cpage > 1 && $this.hasClass("direction") ? "" : cpage);
				return false;
			});
			$("div.list_footer").on("click", "div.pagination [href]", function()
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
			if ($("div#comment").length && this.document_srl == document_srl)
			{
				oAJAXBoardDocPlugin.dispComment();
			}
		},
		triggerDeleteComment: function(comment_srl)
		{
			if ($("div#comment").length && $("#comment_" + comment_srl).length)
			{
				oAJAXBoardDocPlugin.dispComment();
			}
		},
		triggerDispDocumentList: function()
		{
			oAJAXBoardDocPlugin.dispDocumentList();
		},
		dispComment: function(args)
		{
			var $body = $("div#comment");
			if (!$body.length)
			{
				return false;
			}
			
			this.oApp.startAjax();
			
			var that = this;
			var ajax = this.oApp.getPagesHandler(args);
			
			ajax.done(function(response, status, xhr)
			{
				response = $("<div>").append($.parseHTML(response)).find("div#comment");
				
				var $body = $("div#comment");
				var $header = $body.children("div.fbHeader");
				var $content = $body.children("ul.fbList");
				var $pagination = $body.children("div.pagination");
				
				var header = response.children("div.fbHeader").html();
				var content = response.children("ul.fbList").html();
				var pagination = response.children("div.pagination").html();
				
				if ($content.length)
				{
					if (!content)
					{
						$content.remove();
					}
				}
				else
				{
					$header.after($('<ul class="fbList">'));
				}
				if ($pagination.length)
				{
					if (!pagination)
					{
						$pagination.remove();
					}
				}
				else
				{
					if (pagination)
					{
						$content.after($('<div class="pagination">'));
					}
				}
				
				$header = $body.children("div.fbHeader");
				$content = $body.children("ul.fbList");
				$pagination = $body.children("div.pagination");
				
				$header.html(header);
				$content.html(content);
				$pagination.html(pagination);
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
		dispCommentsByCpage: function(cpage)
		{
			var that = this;
			
			return this.dispComment({cpage: cpage}).done(function(response, status, xhr)
			{
				that.oApp.current_url = that.oApp.current_url.setQuery("cpage", cpage);
			});
		},
		dispDocumentList: function(args)
		{
			var $body = $("div.board");
			if (!$body.length)
			{
				return false;
			}
			
			this.oApp.startAjax();
			
			var that = this;
			var ajax = this.oApp.getPagesHandler(args);
			
			ajax.done(function(response, status, xhr)
			{
				response = $("<div>").append($.parseHTML(response)).find("div.board");
				
				var $body = $("div.board");
				var $content = $body.children("div.board_list");
				var $footer = $body.children("div.list_footer").children("div.pagination");
				
				var content = response.children("div.board_list").html();
				var footer = response.children("div.list_footer").children("div.pagination").html();
				
				if ($footer.length)
				{
					if (!footer)
					{
						$footer.remove();
					}
				}
				else
				{
					if (footer)
					{
						$("div.list_footer").prepend('<div class="pagination">');
					}
				}
				
				$footer = $body.children("div.list_footer").children("div.pagination");
				
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