<div class="cp-user-menu">
<a href="{url:users_home}">{lang:home}</a>
{if:messages}
  <a href="{url:messages_inbox}">{lang:messages} (<span id="cs_messages_navmsgs">{messages:new}</span>)</a>
{stop:messages}
<a href="{url:users_settings}">{lang:settings}</a>
{if:more}
  {if:contact}<a href="{url:contact_manage}">{lang:contact} (<span id="cs_contact_navmsgs">{contact:new}</span>)</a>{stop:contact}
  {if:joinus}<a href="{url:joinus_manage}">{lang:joinus} ({joinus:joinus_count})</a>{stop:joinus}
  {if:fightus}<a href="{url:fightus_manage}">{lang:fightus} ({fightus:fightus_count})</a>{stop:fightus}
  {if:boardreport}<a href="{url:board_reportlist}">{lang:boardreports} ({boardreport:boardreport_count})</a>{stop:boardreport}
  {if:admin}<a href="{url:clansphere_admin}">{lang:admin}</a>{stop:admin}
  {if:system}<a href="{url:clansphere_system}">{lang:system}</a>{stop:system}
  {if:panel}<a href="{link:panel}">{lang:panel}</a>{stop:panel}
{stop:more}
<a href="{url:users_logout}">{lang:logout}</a>
</div>
