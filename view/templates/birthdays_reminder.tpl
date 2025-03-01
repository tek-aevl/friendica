{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{if $count}}
<div id="birthday-notice" class="birthday-notice fakelink {{$classtoday}}" onclick="openClose('birthday-wrapper');">{{$event_reminders}} ({{$count}})</div>
<div id="birthday-wrapper" style="display: none;"><div id="birthday-title">{{$event_title}}</div>
<div id="birthday-title-end"></div>
{{foreach $events as $event}}
<div class="birthday-list" id="birthday-{{$event.id}}"> <a href="{{$event.link}}">{{$event.title}}</a> {{$event.date}} </div>
{{/foreach}}
</div>
{{/if}}

