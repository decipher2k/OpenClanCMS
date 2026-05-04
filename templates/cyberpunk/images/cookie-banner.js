/* ClanSphere Cookie Banner */
document.addEventListener('DOMContentLoaded', function() {
  if (document.cookie.split('; ').find(function(r) { return r.startsWith('cookie_ok=') })) return;
  var b = document.createElement('div');
  b.id = 'cookie-banner';
  b.innerHTML = '<div style="background:#111114;border-bottom:1px solid #006622;padding:0.8rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;font-size:0.8rem;flex-wrap:wrap;color:#c0c0c0;font-family:system-ui,sans-serif;"><span>Diese Website verwendet <strong style="color:#00cc3a">ausschliesslich technisch notwendige Cookies</strong> (Session, Login, Sprache). Keine Tracking- oder Marketing-Cookies. <a href="?mod=static&amp;action=view&amp;id=1" style="color:#b8942e">Datenschutzerklaerung</a></span><button onclick="document.cookie=\'cookie_ok=1;path=/;max-age=31536000;SameSite=Lax\';this.parentNode.parentNode.remove()" style="background:#006622;color:#00cc3a;border:1px solid #00cc3a;padding:0.3rem 1rem;cursor:pointer;font-size:0.75rem;text-transform:uppercase;white-space:nowrap;border-radius:2px;">Verstanden</button></div>';
  document.body.insertBefore(b, document.body.firstChild);
});
