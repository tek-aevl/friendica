{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{$live_update nofilter}}
{{foreach $threads as $thread}}
<div id="tread-wrapper-{{$thread.id}}" class="tread-wrapper panel toplevel_item">
    {{foreach $thread.items as $item}}
        {{if $item.comment_firstcollapsed}}
			<div class="hide-comments-outer">
				<span id="hide-comments-total-{{$thread.id}}" class="hide-comments-total">{{$thread.num_comments}}</span>
				<span id="hide-comments-{{$thread.id}}" class="hide-comments fakelink" onclick="showHideComments({{$thread.id}});">{{$thread.hide_text}}</span>
			</div>
			<div id="collapsed-comments-{{$thread.id}}" class="collapsed-comments" style="display: none;">
        {{/if}}
        {{if $item.comment_lastcollapsed}}</div>{{/if}}

        {{include file="{{$item.template}}"}}

    {{/foreach}}
</div>
{{/foreach}}

{{if !$update}}
<div id="conversation-end"></div>
    {{if $dropping}}
<div id="item-delete-selected" class="fakelink" onclick="deleteCheckedItems();">
	<div id="item-delete-selected-icon" class="icon drophide" title="{{$dropping}}"
	     onmouseover="imgbright(this);" onmouseout="imgdull(this);"></div>
	<div id="item-delete-selected-desc">{{$dropping}}</div>
</div>
<div id="item-delete-selected-end"></div>
    {{/if}}
{{/if}}
