<form class="cp-login" method="post" action="{form:navlogin}">
  <fieldset style="border: 0; padding: 0">
    <input type="hidden" name="uri" value="{link:uri}" />
    <input type="text" name="nick" value="{login:nick}" onfocus="if(this.value=='Nick') this.value=''" onblur="if(this.value=='')this.value='Nick'" maxlength="40" size="20" />
    <input type="password" name="password" value="{login:password}" onfocus="if(this.value=='Pass') this.value=''" onblur="if(this.value=='')this.value='Pass'" maxlength="40" size="20" />
    <label><input type="checkbox" name="cookie" value="1" /> {lang:cookie}</label>
    <input type="submit" name="login" value="{lang:submit}" />
  </fieldset>
</form>
<div class="cp-login-links">
  <a href="{url:users_register}">{icon:kuser} {lang:register}</a>
  <a href="{url:users_sendpw}">{icon:password} {lang:sendpw}</a>
</div>
