<div class="cp-user-menu">
<a href="{url:users_home}">{icon:home} {lang:home}</a>
{if:messages}
  <a href="{url:messages_inbox}">{icon:inbox} {lang:messages} (<span id="cs_messages_navmsgs">{messages:new}</span>)</a>
{stop:messages}
<a href="{url:users_settings}">{icon:looknfeel} {lang:settings}</a>
{if:more}
  {if:contact}<a href="{url:contact_manage}">{icon:kontact} {lang:contact} (<span id="cs_contact_navmsgs">{contact:new}</span>)</a>{stop:contact}
  {if:admin}<a href="{url:clansphere_admin}">{icon:view_text} {lang:admin}</a>{stop:admin}
  {if:system}<a href="{url:clansphere_system}">{icon:package_system} {lang:system}</a>{stop:system}
  {if:panel}<a href="{link:panel}">{icon:view_choose} {lang:panel}</a>{stop:panel}
{stop:more}
<a href="{url:users_logout}">{icon:exit} {lang:logout}</a>
</div>
