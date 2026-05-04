/* ClanSphere Cookie Banner */
(function(){
  if (document.cookie.split('; ').find(function(r) { return r.startsWith('cookie_ok=') })) return;
  var b = document.createElement('div');
  b.innerHTML = '<div style="background:#1a1a1a;color:#ccc;border-bottom:2px solid #494;padding:8px 20px;display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:13px;flex-wrap:wrap;font-family:sans-serif;"><span>Diese Website verwendet <strong>ausschliesslich technisch notwendige Cookies</strong> (Session, Login, Sprache). Keine Tracking-Cookies. <a href="?mod=static&amp;action=view&amp;id=1" style="color:#8b8;text-decoration:underline;">Datenschutzerklaerung</a></span><button onclick="document.cookie=\'cookie_ok=1;path=/;max-age=31536000;SameSite=Lax\';this.parentNode.parentNode.remove()" style="background:#363;color:#cfc;border:1px solid #494;padding:5px 15px;cursor:pointer;font-size:12px;text-transform:uppercase;white-space:nowrap;">Verstanden</button></div>';
  document.body.insertBefore(b, document.body.firstChild);
})();
