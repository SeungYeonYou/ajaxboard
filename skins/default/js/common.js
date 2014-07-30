/**
 * AJAXBoard XE Module Javascript
 * Copyright (C) 아약스보드. All rights reserved.
 **/

(function($)
{
	AJAXBoardCommonPlugin = xe.createPlugin("AJAXBoardCommonPlugin",
	{
		init: function(parent)
		{
			parent.registerPlugin(this);
			
			var triggers = [
				[ "events.connect",        "after",  this.triggerConnect        ],
				[ "events.notice",         "before", this.triggerNotice         ],
				[ "events.noticeOfServer", "before", this.triggerNotice         ],
				[ "events.notifyDocument", "before", this.triggerNotifyDocument ],
				[ "events.notifyComment",  "before", this.triggerNotifyComment  ]
			];
			
			for (var i = 0; i < triggers.length; i++)
			{
				parent.insertTrigger(triggers[i][0], triggers[i][1], triggers[i][2]);
			}
		},
		triggerConnect: function()
		{
			if (!this.skin_info.push_notify_text)
			{
				this.skin_info.push_notify_text = "관리자가 <strong>%MESSAGE%</strong>(이)라고 알림을 보냈습니다.";
			}
			if (!this.skin_info.document_notify_text)
			{
				this.skin_info.document_notify_text = "<strong>새 글이 있습니다.</strong><p>%TITLE% - %NICKNAME%</p>";
			}
			if (!this.skin_info.comment_notify_text)
			{
				this.skin_info.comment_notify_text = "<strong>새 댓글이 있습니다.</strong><p>%CONTENT% - %NICKNAME%</p>";
			}
		},
		triggerNotice: function(message)
		{
			var push_notify_text = this.skin_info.push_notify_text || "";
			var text = push_notify_text.replace(/%MESSAGE%/g, message);
			
			noty({
				type    : "warning",
				text    : text,
				timeout : 30000
			});
		},
		triggerNotifyDocument: function(document_srl, title, content, nickname)
		{
			var document_notify_text = this.skin_info.document_notify_text || "";
			var text = document_notify_text.replace(/%DOCUMENT_SRL%/g, document_srl).replace(/%TITLE%/g, title).replace(/%CONTENT%/g, content).replace(/%NICKNAME%/g, nickname);
			
			noty({
				layout    : "bottomLeft",
				text      : text,
				timeout   : 30000
			});
		},
		triggerNotifyComment: function(document_srl, comment_srl, content, nickname)
		{
			var comment_notify_text = this.skin_info.comment_notify_text || "";
			var text = comment_notify_text.replace(/%DOCUMENT_SRL%/g, document_srl).replace(/%COMMENT_SRL%/g, comment_srl).replace(/%CONTENT%/g, content).replace(/%NICKNAME%/g, nickname);
			
			noty({
				layout    : "bottomLeft",
				type      : "information",
				text      : text,
				timeout   : 30000
			});
		}
	});
})(jQuery);

jQuery(function($)
{
	oAJAXBoardCommonPlugin = new AJAXBoardCommonPlugin(oAJAXBoard);
});